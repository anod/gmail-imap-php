GMAIL IMAP API
=============================

Wrapper above Gmail IMAP API

## Requirements

Zend Imap Library

## Usage example

```php
<?php
require_once __DIR__.'/../vendor/autoload.php';

$email = "youremail@gmail.com";
$token = "ya29.AHES6ZQHcyLp0lxEIgdqUf0zaTi9grxCXlyPT6-Ejg85wSSXFBa28Pvh";

$msgId = "1429570359846677735";  
//$msgId = \Anod\Gmail\Math::bchexdec("13d6daab0816ace7"); // == "1429570359846677735"


$debug = true;
$protocol = new \Anod\Gmail\Imap(debug);
$gmail = new \Anod\Gmail\Gmail($protocol);
$gmail->setId("Example App","0.1","Alex Gavrishev","alex.gavrishev@gmail.com");

$gmail->connect();
$gmail->authenticate($email, $token);
$gmail->sendId();
$gmail->selectInbox();
$uid = $gmail->getUID($msgId);

$gmail->applyLabel($uid, "MyLabel"); //Apply label to a message with specific UID

$message = $gmail->getMessageData($uid); //Retrieve message content
$details = array(
	'subject' => $this->message->getHeader('subject', 'string'),
	'body' =>  $this->message->getContent(),
	'from' => $this->message->getHeader('from', 'string'),
	'to' => $this->message->getHeader('to', 'string'),
	'thrid' => \Anod\Gmail\Math::bcdechex($this->message->getHeader('x-gm-thrid', 'string'))
);

$gmail->archive($uid); //Archive the message


```

## Author

Alex Gavrishev, 2013

## License

Library is licensed under the MIT license.