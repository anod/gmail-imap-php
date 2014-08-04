<?php
namespace Anod\Gmail;
/**
 * Extendz Zend Imap with debug messages
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 *
 */
class Imap extends \Zend\Mail\Protocol\Imap {
	private $debug = false;
	/**
	 * @param bool $debug
	 */
	public function __construct($debug = false) {
		parent::__construct();
		$this->debug = (bool)$debug;
	}

	/**
	 * @param string $command
	 * @param array $tokens
	 * @param null $tag
	 */
	public function sendRequest($command, $tokens = array(), &$tag = null)
	{
		parent::sendRequest($command, $tokens, $tag);
		if ($this->debug) {
			echo $tag.' '.$command.' '.implode(' ', $tokens).PHP_EOL;
		}
	}

	/**
	 * @return string
	 */
	protected function _nextLine() {
		$line = parent::_nextLine();
		if ($this->debug) {
			echo "    ".trim($line).PHP_EOL;
		}
		return $line;
	}
}
class ImapException extends \Exception {};