<?php
namespace Anod;
/**
 * 
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 *
 */
class Gmail extends \Zend\Mail\Storage\Imap {
	const GMAIL_HOST = 'imap.gmail.com';
	const GMAIL_PORT = '993';
	const USE_SSL = true;

	/**
	 * @var OAuth
	 */
	private $oauth;
	/**
	 * @var bool
	 */
	private $debug = false;
	/**
	 *
	 * @param \Zend\Mail\Protocol\Imap $imap
	 */
	public function __construct(\Zend\Mail\Protocol\Imap $protocol) {
		$this->protocol = $protocol;
		$this->oauth = new \OAuth($protocol);
	}
	
	public function setId($name, $version, $vendor, $contact) {
		$this->id =array(
			"name" , $name,
			"version" , $version,
			"vendor" , $vendor,
			"contact" , $contact	
		);
		return $this;
	}
	
	/**
	 * @param bool $debug
	 */
	public function setDebug($debug) {
		$this->debug = (bool)$debug;
		return $this;
	}
	/**
	 * @return void
	 */	
	public function sendId() {
		$escaped = array();
		foreach($this->id AS $value) {
			$escaped[] = $this->protocol->escapeString($value);
		}
		return $this->protocol->requestAndResponse('ID', array(
			$this->protocol->escapeList($escaped))
		);
	}
	
	/**
	 * 
	 * @param string $email
	 * @param string $accessToken
	 */
	public function authenticate($email, $accessToken) {
		$this->oauth->authenticate($email, $accessToken);
		return $this;
	}
	
	/**
	 * 
	 * @return \Zend\Mail\Protocol\Imap
	 */
	public function getProtocol() {
		return $this->protocol;
	}
	
	/**
	 *
	 * @return \GTD\Gmail
	 */
	public function connect() {
		$this->protocol->connect(self::GMAIL_HOST, self::GMAIL_PORT, self::USE_SSL);
		return $this;
	}

	/**
	 * 
	 * @throws GmailException
	 * @return \GTD\Gmail
	 */
	public function selectInbox() {
		$result = $this->protocol->select('INBOX');
		if (!$result) {
			throw new GmailException("Cannot select INBOX");
		}
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getUID($msgid) {
		$search_response = $this->protocol->requestAndResponse('UID SEARCH', array('X-GM-MSGID', $msgid));
		if (isset($search_response[0][1])) {
			return (int)$search_response[0][1];
		}
		throw new GmailException("Cannot retreieve message uid. ".var_export($search_response, TRUE));
	}

	/**
	 * 
	 * @param string $uid
	 */
	public function archive($uid) {
		//1st veryfi that email in all mail folder
		$folder = $this->protocol->escapeString("[Gmail]/All Mail");
		$copy_response = $this->protocol->requestAndResponse('UID COPY', array($uid, $folder), true);
		if ($copy_response) {
			//Flag as deleted in inbox
			$items = array('\Deleted');
			$itemList = $this->protocol->escapeList($items);
			$response = $this->protocol->requestAndResponse('UID STORE', array($uid, '+FLAGS', $itemList), true);
			return $response;
		}
		return null;
	}
	
	/**
	 * @param string $uid
	 * @param string $label
	 */	
	public function applyLabel($uid, $label) {
		$response = $this->protocol->requestAndResponse('UID STORE', array($uid, '+X-GM-LABELS', '('.$label.')'));
		return $response;
	}
	
	/**
	 * 
	 * @param string $uid
	 * @throws GmailException
	 * @return array
	 */
	public function getRawMessageData($uid) {
		$items = array('FLAGS', 'RFC822.HEADER', 'RFC822.TEXT');
		$itemList = $this->protocol->escapeList($items);
		
		$fetch_response = $this->protocol->requestAndResponse('UID FETCH', array($uid, $itemList));
		if (!isset($fetch_response[0][2]) || !is_array($fetch_response[0][2])) {
			throw new GmailException("Cannot retreieve message by uid. ".var_export($fetch_response, TRUE));
		}
		$response_count = count($fetch_response);
		$data = array();
		for($i = 0; $i < $response_count; $i++) {
			$tokens = $fetch_response[$i];
			// ignore other responses
			if ($tokens[1] != 'FETCH') {
				continue;
			}
			
			while (key($tokens[2]) !== null) {
				$data[current($tokens[2])] = next($tokens[2]);
				next($tokens[2]);
			}
		}
		return $data;
	}
	
	/**
	 * 
	 * @param string $uid
	 * @throws GmailException
	 * @return string
	 */
	public function getThreadId($uid) {
		$fetch_response = $this->protocol->requestAndResponse('UID FETCH', array($uid, 'X-GM-THRID'));
		if (!isset($fetch_response[0][2]) || !is_array($fetch_response[0][2]) || !isset($fetch_response[0][2][1])) {
			throw new GmailException("Cannot retreieve thread id by uid. ".var_export($fetch_response, TRUE));
		}
		return $fetch_response[0][2][1];
	}
	
	/**
	 * 
	 * @param string $uid
	 * @return \Zend\Mail\Storage\Message
	 */
	public function getMessageData($uid) {
		$data = $this->getRawMessageData($uid);
		$threadId = $this->getThreadId($uid);
		
		$header = $data['RFC822.HEADER'];		
		$content = $data['RFC822.TEXT'];
		$flags = array();
		foreach ($data['FLAGS'] as $flag) {
			$flags[] = isset(static::$knownFlags[$flag]) ? static::$knownFlags[$flag] : $flag;
		}
		$msg = new \Zend\Mail\Storage\Message(array(
			'handler' => $this,
			'id' => $uid,
			'headers' => $header,
			'content' => $content,
			'flags' => $flags
		));
		$msg->getHeaders()->addHeaderLine('x-gm-thrid', $threadId);
		return $msg;
	}
	
}

class GmailException extends \Exception {};