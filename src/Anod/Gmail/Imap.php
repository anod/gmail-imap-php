<?php
declare(strict_types=1);
/**
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 */
namespace Anod\Gmail;

/**
 * Extendz Zend Imap with debug messages
 */
class Imap extends \Zend\Mail\Protocol\Imap
{
    private $debug = false;

    public function __construct(bool $debug = false)
    {
        parent::__construct();
        $this->debug = (bool)$debug;
    }

    public function sendRequest($command, $tokens = [], &$tag = null)
    {
        parent::sendRequest($command, $tokens, $tag);
        if ($this->debug) {
            echo $tag.' '.$command.' '.implode(' ', $tokens).PHP_EOL;
        }
    }

    protected function nextLine(): string
    {
        $line = parent::nextLine();
        if ($this->debug) {
            echo "    ".trim($line).PHP_EOL;
        }
        return $line;
    }
}

// phpcs:ignore
class ImapException extends \Exception { };
