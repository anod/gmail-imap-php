<?php
declare(strict_types=1);
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
    public function getLabels(): array
    {
        if ($this->getHeaders()->get('x-gm-labels')) {
            return $this->getHeader('x-gm-labels', 'array');
        }
        return [];
    }
    
    /**
     * Thread Id of the message
     */
    public function getThreadId(): string
    {
        return $this->getHeader('x-gm-thrid', 'string');
    }
}
