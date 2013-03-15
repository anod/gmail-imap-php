<?php
namespace Anod;

class GmailTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * @dataProvider providerGetRawMessageUID
	 */
	public function testGetRawMessageUID($response, $expected) {
		$protocol = $this->getMock('\Zend\Mail\Protocol\Imap', array('requestAndResponse'));
		
		$protocol
			->expects($this->once())
			->method('requestAndResponse')
			->will($this->returnValue($response))
		;
		
		$gmail = new Gmail($protocol);
		$actual = $gmail->getRawMessageUID("53559");
		
		$this->assertEquals($expected, $actual);
		
	}
	
	public function providerGetRawMessageUID() {
		
		$body = "--BODY--";
		$header = "--HEADER--";
		
		$response = array(
			array(
				"9","FETCH",
				array(
					"UID","53559","RFC822.TEXT",$body,
					"RFC822.HEADER",$header,
					"FLAGS",
					array("\Seen")
      			)
 			)	
		);
		
		$expected = array(
			"UID" => "53559",
			"RFC822.TEXT" => $body,
			"RFC822.HEADER" => $header,
			"FLAGS" => array("\Seen")
		);
		
		return array(
			array($response, $expected)
		);
	}
	
}

