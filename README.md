GMAIL IMAP API in PHP
=============================

Wrapper library above Gmail IMAP API.

## Features

The library extends Zend Imap Library, this way it provides all basic IMAP functionality.
In addition it provides simple Gmail specific API for:
 * OAUTH2 Authentication
 * get UID of the message
 * work with GMail labels: retrive, apply, remove
 * Getting Gmail thread id
 * Utility to convert Gmail message id representation: from big int to hex and opposite
 * Archive message

## TODO

* move to inbox
* mark as read/unread

## Requirements

Composer
  * Zend Imap Library

## Usage example

```php
<?php
// \Anod\Gmail\Math::bchexdec("FMfcgxwGCkZzGTlZHvpgXBHPvqzkLKtC") == "1430824723191418813"
$msgId = "1430824723191418813";
$email = "alex.gavrishev@gmail.com";

$protocol = new \Anod\Gmail\Imap(true /* debug */);
$gmail = new \Anod\Gmail\Gmail($protocol);
$gmail->setId("Example App", "0.1", "Alex Gavrishev", "alex.gavrishev@gmail.com");

$gmail->connect();
$gmail->authenticate($email, $token);
$gmail->sendId();
$gmail->selectInbox();
$uid = $gmail->getUID($msgId);

$gmail->applyLabel($uid, "Very Important"); // Apply label to a message with specific UID
$gmail->removeLabel($uid, "Not Important"); // Remove label to a message with specific UID

$message = $gmail->getMessageData($uid); // Retrieve message content
$details = array(
    'subject' => $message->getHeader('subject', 'string'),
    'body' =>  $message->getContent(),
    'from' => $message->getHeader('from', 'string'),
    'to' => $message->getHeader('to', 'string'),
    'thrid' => \Anod\Gmail\Math::bcdechex($message->getThreadId()),
    'labels' => $message->getLabels()
);

$gmail->archive($uid); // Archive the message
```

Example of fetching token with local server `php -S localhost:8000`

```php
<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Based on https://developers.google.com/identity/protocols/OAuth2
$clientId = Clien Id from google console;
$clientSecret = Client secret from google console;
$redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

$client = new Google_Client([
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri
]);
// Scope for IMAP access
$client->addScope("https://mail.google.com/");
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['gmail_token'] = $token;

    // redirect back to the example
    header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
    exit;
}

if (!empty($_SESSION['gmail_token'])) {
    $client->setAccessToken($_SESSION['gmail_token']);
    if ($client->isAccessTokenExpired()) {
        unset($_SESSION['gmail_token']);
    }
} else {
    $authUrl = $client->createAuthUrl();
}

if ($authUrl) {
    $authUrl = $client->createAuthUrl();
    header("Location: $authUrl");
    exit;
}

$token = $client->getAccessToken()['access_token'];
echo "Token: $token\n\n";
```

## Author

Alex Gavrishev, 2013

## License

Library is licensed under the MIT license.
