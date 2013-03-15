GMAIL IMAP API
=============================

Usage example:


```php
<?php
require_once __DIR__.'/../vendor/autoload.php';

$email = "youremail@gmail.com";
$token = "ya29.AHES6ZQHcyLp0lxEIgdqUf0zaTi9grxCXlyPT6-Ejg85wSSXFBa28Pvh";

$msgId = "1429570359846677735";  
//$msgId = \Anod\Math::bchexdec("13d6daab0816ace7"); // == "1429570359846677735"


$debug = true;
$protocol = new \Anod\Imap(debug);
$gmail = new \Anod\Gmail($protocol);
$gmail->setId("Example App","0.1","Alex Gavrishev","alex.gavrishev@gmail.com");

$gmail->connect();
$gmail->authenticate(email, token);
$gmail->sendId();
$gmail->selectInbox();
$uid = $gmail->getUID($msgId);

$gmail->applyLabel($uid, "MyLabel");
$message = $gmail->getMessageData($uid);
$details = array(
	'subject' => $this->message->getHeader('subject', 'string'),
	'body' =>  $this->message->getContent(),
	'from' => $this->message->getHeader('from', 'string'),
	'to' => $this->message->getHeader('to', 'string'),
	'thrid' => \Anod\Math::bcdechex($this->message->getHeader('x-gm-thrid', 'string'))
);
$gmail->archive($uid);


```