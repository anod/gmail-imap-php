<?php
/**
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 */
namespace Anod\Gmail;

use PHPUnit\Framework\TestCase;

class GmailTest extends TestCase
{
    /**
     * @dataProvider providerGetRawMessageUID
     * @param $response
     * @param $expected
     * @throws GmailException
     */
    public function testGetRawMessageUID($response, $expected)
    {
        $protocol = $this->createMock(\Zend\Mail\Protocol\Imap::class);
        
        $protocol
            ->expects($this->once())
            ->method('requestAndResponse')
            ->will($this->returnValue($response))
        ;
        
        /** @var \Zend\Mail\Protocol\Imap $protocol */
        $gmail = new Gmail($protocol);
        $actual = $gmail->getMessageDataRaw("53559");
        
        $this->assertEquals($expected, $actual);
    }
    
    public function providerGetRawMessageUID()
    {
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
        
        $expected = [
            "UID" => "53559",
            "RFC822.TEXT" => $body,
            "RFC822.HEADER" => $header,
            "FLAGS" => array("\Seen")
        ];

        return [
            [$response, $expected]
        ];
    }
}
