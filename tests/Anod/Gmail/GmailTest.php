<?php
namespace Anod\Gmail;
/**
 * 
 * 
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 *
 */
class GmailTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider providerGetRawMessageUID
	 * @param $response
	 * @param $expected
	 * @throws GmailException
	 */
	public function testGetRawMessageUID($response, $expected) {
		$protocol = $this->getMock('\Zend\Mail\Protocol\Imap', array('requestAndResponse'));
		
		$protocol
			->expects($this->once())
			->method('requestAndResponse')
			->will($this->returnValue($response))
		;
		
		$gmail = new Gmail($protocol);
		$actual = $gmail->getMessageDataRaw("53559");
		
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

