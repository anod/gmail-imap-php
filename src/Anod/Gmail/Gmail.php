<?php
declare(strict_types=1);
/**
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 */
namespace Anod\Gmail;

/**
 * @see https://developers.google.com/google-apps/gmail/imap_extensions
 */
class Gmail extends \Zend\Mail\Storage\Imap
{
    const GMAIL_HOST = 'imap.gmail.com';
    const GMAIL_PORT = 993;
    const USE_SSL = 'ssl';

    const MAILBOX_INBOX = 'INBOX';
    const MAILBOX_ALL = '[Gmail]/All Mail';
    const MAILBOX_DRAFTS = '[Gmail]/Drafts';
    const MAILBOX_IMPORTANT = '[Gmail]/Important';
    const MAILBOX_SENT = '[Gmail]/Sent Mail';
    const MAILBOX_SPAM = '[Gmail]/Spam';
    const MAILBOX_STARRED = '[Gmail]/Starred';
    const MAILBOX_TRASH = '[Gmail]/Trash';

    /**
     * @var \Anod\Gmail\OAuth
     */
    private $oauth;

    /**
     * @var array
     */
    protected $id;

    /**
     * @param \Zend\Mail\Protocol\Imap $protocol
     */
    public function __construct(\Zend\Mail\Protocol\Imap $protocol)
    {
        $this->init($protocol);
    }

    /**
     * Use init method to fix incapability with HHVM:
     * Fatal error: Declaration of Anod\Gmail\Gmail::__construct()
     * must be compatible with that of Zend\Mail\Storage\AbstractStorage::__construct()
     */
    protected function init(\Zend\Mail\Protocol\Imap $protocol)
    {
        $this->protocol = $protocol;
        $this->oauth = new OAuth($protocol);
    }
    
    public function setId(string $name, string $version, string $vendor, string $contact) : \Anod\Gmail\Gmail
    {
        $this->id =array(
            "name" , $name,
            "version" , $version,
            "vendor" , $vendor,
            "contact" , $contact
        );
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function sendId()
    {
        $escaped = array();
        foreach ($this->id as $value) {
            $escaped[] = $this->protocol->escapeString($value);
        }
        return $this->protocol->requestAndResponse(
            'ID',
            array(
            $this->protocol->escapeList($escaped))
        );
    }
    
    public function authenticate(string $email, string $accessToken): \Anod\Gmail\Gmail
    {
        $this->oauth->authenticate($email, $accessToken);
        return $this;
    }
    
    public function getProtocol(): \Zend\Mail\Protocol\Imap
    {
        return $this->protocol;
    }
    
    public function connect(): \Anod\Gmail\Gmail
    {
        $this->protocol->connect(self::GMAIL_HOST, self::GMAIL_PORT, self::USE_SSL);
        return $this;
    }

    /**
     * @throws GmailException
     */
    public function selectInbox(): \Anod\Gmail\Gmail
    {
        $result = $this->protocol->select(self::MAILBOX_INBOX);
        if (!$result) {
            throw new GmailException("Cannot select ".self::MAILBOX_INBOX);
        }
        return $this;
    }
    
    /**
     * @throws GmailException
     */
    public function selectAllMail(): \Anod\Gmail\Gmail
    {
        $result = $this->protocol->select(self::MAILBOX_ALL);
        if (!$result) {
            throw new GmailException("Cannot select ".self::MAILBOX_ALL);
        }
        return $this;
    }

    /**
     * @throws GmailException
     */
    public function getUID(string $msgid): int
    {
        $search_response = $this->protocol->requestAndResponse('UID SEARCH', ['X-GM-MSGID', $msgid]);
        if (isset($search_response[0][1])) {
            return (int)$search_response[0][1];
        }
        throw new GmailException("Cannot retrieve message uid. ".var_export($search_response, true));
    }

    public function archive(int $uid): bool
    {
        return $this->moveMessageUID($uid, self::MAILBOX_ALL);
    }

    public function trash(Int $uid): bool
    {
        return $this->moveMessageUID($uid, self::MAILBOX_TRASH);
    }
    
    public function moveMessageUID(int $uid, string $dest): bool
    {
        $copy_response = $this->copyMessageUID($uid, $dest);
        if ($copy_response) {
            //Flag as deleted in the current box
            return $this->removeMessageUID($uid);
        }
        return false;
    }

    public function copyMessageUID(int $uid, string $dest)
    {
        $folder = $this->protocol->escapeString($dest);
        return $this->protocol->requestAndResponse('UID COPY', array($uid, $folder), true);
    }

    /**
     * Apply flag to message by UID
     */
    public function setFlagsUID(int $uid, array $flags)
    {
        $itemList = $this->protocol->escapeList($flags);
        return $this->protocol->requestAndResponse('UID STORE', array($uid, '+FLAGS', $itemList), true);
    }
    
    /**
     * Mark message as \Deleted by UID
     * @return bool
     */
    public function removeMessageUID(int $uid): bool
    {
        //Flag as deleted in the current box
        $flags = array(\Zend\Mail\Storage::FLAG_DELETED);
        $this->setFlagsUID($uid, $flags);
        return $this->protocol->expunge();
    }
    
    /**
     * @param int $uid
     * @param string $label
     * @return null|bool|array tokens if success, false if error, null if bad request
     */
    public function applyLabel(int $uid, string $label)
    {
        return $this->applyLabels($uid, array($label));
    }
    
    /**
     * @param int $uid
     * @param array $labels <string>
     * @return null|bool|array tokens if success, false if error, null if bad request
     */
    public function applyLabels(int $uid, array $labels)
    {
        return $this->storeLabels($uid, '+X-GM-LABELS', $labels);
    }

    /**
     *
     * @param int $uid
     * @param string $label
     * @return null|bool|array tokens if success, false if error, null if bad request
     */
    public function removeLabel(int $uid, string $label)
    {
        return $this->removeLabels($uid, array($label));
    }
    
    /**
     *
     * @param int $uid
     * @param array $labels <string>
     * @return null|bool|array tokens if success, false if error, null if bad request
     */
    public function removeLabels($uid, array $labels)
    {
        return $this->storeLabels($uid, '-X-GM-LABELS', $labels);
    }
    
    /**
     * List all labels for message with $uid
     * @throws GmailException
     * @return array <string> labels
     */
    public function getLabels(int $uid)
    {
        $itemList = $this->protocol->escapeList(array('X-GM-LABELS'));
        
        $fetch_response = $this->protocol->requestAndResponse('UID FETCH', array($uid, $itemList));
        if (!isset($fetch_response[0][2]) || !is_array($fetch_response[0][2]) || !isset($fetch_response[0][2][1])) {
            throw new GmailException("Cannot retrieve list of labels by uid. ".var_export($fetch_response, true));
        }
        return $fetch_response[0][2][1];
    }
    
    /**
     * @throws GmailException
     */
    public function getMessageDataRaw(int $uid): array
    {
        $items = array('FLAGS', 'RFC822.HEADER', 'RFC822.TEXT', 'X-GM-LABELS', 'X-GM-THRID');
        $itemList = $this->protocol->escapeList($items);
        
        $fetch_response = $this->protocol->requestAndResponse('UID FETCH', array($uid, $itemList));
        if (!isset($fetch_response[0][2]) || !is_array($fetch_response[0][2])) {
            throw new GmailException("Cannot retrieve message by uid. ".var_export($fetch_response, true));
        }
        $response_count = count($fetch_response);
        $data = array();
        for ($i = 0; $i < $response_count; $i++) {
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
     * @throws GmailException
     */
    public function getThreadId(int $uid): string
    {
        $fetch_response = $this->protocol->requestAndResponse('UID FETCH', array($uid, 'X-GM-THRID'));
        if (!isset($fetch_response[0][2]) || !is_array($fetch_response[0][2]) || !isset($fetch_response[0][2][1])) {
            throw new GmailException("Cannot retrieve thread id by uid. ".var_export($fetch_response, true));
        }
        return $fetch_response[0][2][1];
    }
    
    public function getMessageData(int $uid): Message
    {
        $data = $this->getMessageDataRaw($uid);
        
        $header = $data['RFC822.HEADER'];
        $content = $data['RFC822.TEXT'];
        $threadId = $data['X-GM-THRID'];
        $labels = $data['X-GM-LABELS'];
        $flags = [];
        foreach ($data['FLAGS'] as $flag) {
            $flags[] = isset(static::$knownFlags[$flag]) ? static::$knownFlags[$flag] : $flag;
        }
        
        $msg = new Message([
            'handler' => $this,
            'id' => $uid,
            'headers' => $header,
            'content' => $content,
            'flags' => $flags
        ]);
        $msgHeaders = $msg->getHeaders();
        $msgHeaders->addHeaderLine('x-gm-thrid', $threadId);
        if ($labels) {
            foreach ($labels as $label) {
                $msgHeaders->addHeaderLine('x-gm-labels', $label);
            }
        }
        return $msg;
    }
    
    /**
     * @return null|bool|array tokens if success, false if error, null if bad request
     */
    protected function storeLabels(int $uid, string $command, array $labels)
    {
        $escapedLabels = array();
        foreach ($labels as $label) {
            $escapedLabels[] = $this->protocol->escapeString($label);
        }
    
        $labelsList = $this->protocol->escapeList($escapedLabels);
        $response = $this->protocol->requestAndResponse('UID STORE', array($uid, $command, $labelsList));
        return $response;
    }
}

// phpcs:ignore
class GmailException extends \Exception { };
