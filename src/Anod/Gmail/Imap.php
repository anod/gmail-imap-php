<?php
declare(strict_types=1);
/**
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 */
namespace Anod\Gmail;

use Psr\Log\LoggerInterface;

/**
 * Extendz Zend Imap with debug messages
 */
class Imap extends \Zend\Mail\Protocol\Imap
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    public function sendRequest($command, $tokens = [], &$tag = null)
    {
        parent::sendRequest($command, $tokens, $tag);
        if ($this->logger) {
            $this->logger->debug($tag.' '.$command.' '.implode(' ', $tokens));
        }
    }

    protected function nextLine(): string
    {
        $line = parent::nextLine();
        if ($this->logger) {
            $this->logger->debug("    ".trim($line));
        }
        return $line;
    }
}

// phpcs:ignore
class ImapException extends \Exception { };
