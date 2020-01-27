<?php
/**
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 */
namespace Anod\Gmail;

/**
 * Represents one message
 * @see https://developers.google.com/google-apps/gmail/imap_extensions
 */
class Message extends \Zend\Mail\Storage\Message
{
    
    /**
     * Attached labels
     * @return array <string>
     */
    public function getLabels()
    {
        if ($this->getHeaders()->get('x-gm-labels')) {
            return $this->getHeader('x-gm-labels', 'array');
        }
        return array();
    }
    
    /**
     * Thread Id of the message
     * @return string
     */
    public function getThreadId()
    {
        return $this->getHeader('x-gm-thrid', 'string');
    }
}
