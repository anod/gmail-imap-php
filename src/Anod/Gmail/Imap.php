<?php
namespace Anod\Gmail;
/**
 * 
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
	 * (non-PHPdoc)
	 * @see \Zend\Mail\Protocol\Imap::sendRequest()
	 */
	public function sendRequest($command, $tokens = array(), &$tag = null)
	{
		parent::sendRequest($command, $tokens, $tag);
		if ($this->debug) {
			echo $tag.' '.$command.' '.implode(' ', $tokens).PHP_EOL;
		}
	}
}
class ImapException extends \Exception {};