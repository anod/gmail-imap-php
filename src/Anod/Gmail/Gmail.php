<?php
namespace Anod\Gmail;
/**
 * 
 * TODO: moveToInbox, markAsRead/markAsUnread
 * 
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 * 
 * @see https://developers.google.com/google-apps/gmail/imap_extensions
 * 
 */
class Gmail extends \Zend\Mail\Storage\Imap {
	const GMAIL_HOST = 'imap.gmail.com';
	const GMAIL_PORT = '993';
	const USE_SSL = true;

	const MAILBOX_INBOX = 'INBOX';
	const MAILBOX_ALL = '[Gmail]/All Mail';
	
	/**
	 * @var \Anod\Gmail\OAuth
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
		$this->oauth = new OAuth($protocol);
	}
	
	/**
	 * 
	 * @param string $name
	 * @param string $version
	 * @param string $vendor
	 * @param string $contact
	 * @return \Anod\Gmail\Gmail
	 */
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
	 * @return \Anod\Gmail\Gmail
	 */
	public function setDebug($debug) {
		$this->debug = (bool)$debug;
		return $this;
	}
	/**
	 * @return mixed
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
	 * @return \Anod\Gmail\Gmail
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
	 * @return \Anod\Gmail\Gmail
	 */
	public function connect() {
		$this->protocol->connect(self::GMAIL_HOST, self::GMAIL_PORT, self::USE_SSL);
		return $this;
	}

	/**
	 * 
	 * @throws GmailException
	 * @return \Anod\Gmail\Gmail
	 */
	public function selectInbox() {
		$result = $this->protocol->select(self::MAILBOX_INBOX);
		if (!$result) {
			throw new GmailException("Cannot select ".self::MAILBOX_INBOX);
		}
		return $this;
	}
	
	/**
	 *
	 * @throws GmailException
	 * @return \Anod\Gmail\Gmail
	 */
	public function selectAllMail() {
		$result = $this->protocol->select(self::MAILBOX_ALL);
		if (!$result) {
			throw new GmailException("Cannot select ".self::MAILBOX_ALL);
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
		throw new GmailException("Cannot retrieve message uid. ".var_export($search_response, TRUE));
	}

	/**
	 * 
	 * @param int $uid
	 * @return bool
	 */
	public function archive($uid) {
		//1st verify that email in all mail folder
		$folder = $this->protocol->escapeString(self::MAILBOX_ALL);
		$copy_response = $this->protocol->requestAndResponse('UID COPY', array($uid, $folder), true);
		if ($copy_response) {
			//Flag as deleted in the current box
			return $this->removeMessageUID($uid);
		}
		return false;
	}
	
	/**
	 * Mark message as \Deleted by UID
	 * @param int $uid
	 * @return bool
	 */
	public function removeMessageUID($uid) {
		//Flag as deleted in the current box
		$items = array(\Zend\Mail\Storage::FLAG_DELETED);
		$itemList = $this->protocol->escapeList($items);
		$response = $this->protocol->requestAndResponse('UID STORE', array($uid, '+FLAGS', $itemList), true);
		return $this->protocol->expunge();
	}
	
	/**
	 * @param int $uid
	 * @param string $label
	 * @return null|bool|array tokens if success, false if error, null if bad request
	 */	
	public function applyLabel($uid, $label) {
		return $this->applyLabels($uid, array($label));
	}
	
	/**
	 * 
	 * @param int $uid
	 * @param array $labels <string>
	 * @return null|bool|array tokens if success, false if error, null if bad request
	 */
	public function applyLabels($uid, array $labels) {
		$this->storeLabels($uid, '+X-GM-LABELS', $labels);
	}

	/**
	 * 
	 * @param int $uid
	 * @param string $label
	 * @return null|bool|array tokens if success, false if error, null if bad request
	 */
	public function removeLabel($uid, $label) {
		return $this->removeLabels($uid, array($label));
	}
	
	/**
	 *
	 * @param int $uid
	 * @param array $labels <string>
	 * @return null|bool|array tokens if success, false if error, null if bad request
	 */
	public function removeLabels($uid, array $labels) {
		return $this->storeLabels($uid, '-X-GM-LABELS', $labels);
	}
	
	/**
	 * List all labels for message with $uid
	 * @param int $uid
	 * @throws GmailException
	 * @return array <string> labels
	 */
	public function getLabels($uid) {
		$itemList = $this->protocol->escapeList(array('X-GM-LABELS'));
		
		$fetch_response = $this->protocol->requestAndResponse('UID FETCH', array($uid, $itemList));
		if (!isset($fetch_response[0][2]) || !is_array($fetch_response[0][2]) || !isset($fetch_response[0][2][1])) {
			throw new GmailException("Cannot retrieve list of labels by uid. ".var_export($fetch_response, TRUE));
		}
		return $fetch_response[0][2][1];
	}
	
	/**
	 * 
	 * @param int $uid
	 * @throws GmailException
	 * @return array
	 */
	public function getMessageDataRaw($uid) {
		$items = array('FLAGS', 'RFC822.HEADER', 'RFC822.TEXT', 'X-GM-LABELS', 'X-GM-THRID');
		$itemList = $this->protocol->escapeList($items);
		
		$fetch_response = $this->protocol->requestAndResponse('UID FETCH', array($uid, $itemList));
		if (!isset($fetch_response[0][2]) || !is_array($fetch_response[0][2])) {
			throw new GmailException("Cannot retrieve message by uid. ".var_export($fetch_response, TRUE));
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
	 * @param int $uid
	 * @throws GmailException
	 * @return string
	 */
	public function getThreadId($uid) {
		$fetch_response = $this->protocol->requestAndResponse('UID FETCH', array($uid, 'X-GM-THRID'));
		if (!isset($fetch_response[0][2]) || !is_array($fetch_response[0][2]) || !isset($fetch_response[0][2][1])) {
			throw new GmailException("Cannot retrieve thread id by uid. ".var_export($fetch_response, TRUE));
		}
		return $fetch_response[0][2][1];
	}
	
	/**
	 * 
	 * @param int $uid
	 * @return \Zend\Mail\Storage\Message
	 */
	public function getMessageData($uid) {
		$data = $this->getMessageDataRaw($uid);
		
		$header = $data['RFC822.HEADER'];		
		$content = $data['RFC822.TEXT'];
		$threadId = $data['X-GM-THRID'];
		$labels = $data['X-GM-LABELS'];
		$flags = array();
		foreach ($data['FLAGS'] as $flag) {
			$flags[] = isset(static::$knownFlags[$flag]) ? static::$knownFlags[$flag] : $flag;
		}
		
		$msg = new Message(array(
			'handler' => $this,
			'id' => $uid,
			'headers' => $header,
			'content' => $content,
			'flags' => $flags
		));
		$msgHeaders = $msg->getHeaders();
		$msgHeaders->addHeaderLine('x-gm-thrid', $threadId);
		if ($labels) {
			foreach($labels AS $label) {
				$msgHeaders->addHeaderLine('x-gm-labels', $label);
			}
		}
		return $msg;
	}
	
	/**
	 *
	 * @param int $uid
	 * @param string $command
	 * @param array $labels
	 * @return null|bool|array tokens if success, false if error, null if bad request
	 */
	protected function storeLabels($uid, $command, array $labels) {
		$escapedLabels = array();
		foreach($labels AS $label) {
			$escapedLabels[] = $this->protocol->escapeString($label);
		}
	
		$labelsList = $this->protocol->escapeList($escapedLabels);
		$response = $this->protocol->requestAndResponse('UID STORE', array($uid, $command, $labelsList));
		return $response;
	}
	
}

class GmailException extends \Exception {};