<?php
/**
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 */
namespace Anod\Gmail;

/**
 * Given an open IMAP connection, attempts to authenticate with OAuth2.
 * @see https://developers.google.com/gmail/imap/xoauth2-protocol
 */
class OAuth
{
    /**
     * @var \Zend\Mail\Protocol\Imap
     */
    private $protocol;
    
    /**
     *
     * @param \Zend\Mail\Protocol\Imap $protocol
     */
    public function __construct(\Zend\Mail\Protocol\Imap $protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * Given an open IMAP connection, attempts to authenticate with OAuth2.
     *
     * @param string $email is a Gmail address.
     * @param string $accessToken is a valid OAuth 2.0 access token for the given email address.
     *
     * @throws OAuthException
     * @returns bool true on successful authentication, false otherwise.
     */
    public function authenticate($email, $accessToken)
    {
        $authenticateParams = array(
            'XOAUTH2', $this->constructAuthString($email, $accessToken)
        );
        $this->protocol->sendRequest('AUTHENTICATE', $authenticateParams);
        while (true) {
            $response = "";
            $is_plus = $this->protocol->readLine($response, '+', true);
            if ($is_plus) {
                error_log("got an extra server challenge: ".base64_decode($response));
                // Send empty client response.
                $this->protocol->sendRequest('');
            } else {
                if (preg_match('/^NO /i', $response) || preg_match('/^BAD /i', $response)) {
                    throw new OAuthException('Authentication failure: '.$response);
                } elseif (preg_match("/^OK /i", $response)) {
                    return true;
                } else {
                    // Some untagged response, such as CAPABILITY
                }
            }
        }
        return false;
    }

    /**
     * Builds an OAuth2 authentication string for the given email address and access
     * token.
     * @param string $email
     * @param string $accessToken
     * @return string
     */
    private function constructAuthString($email, $accessToken)
    {
        return base64_encode("user=$email\1auth=Bearer $accessToken\1\1");
    }
}

// phpcs:ignore
class OAuthException extends \Exception { };
