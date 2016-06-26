<?php

require_once './vendor/autoload.php';
require_once './src/Bitmarshals/Mail/SimpleMailer.php';
$config = require_once './config/config.php';

use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Bitmarshals\Mail\SimpleMailer;

$senderEmail;
$senderName;
$emailBody;

$recipientEmail = $config['simple_mailer']['recipient']['email'];

$emailSubject = '[Contact Us] - ' . $config['domain_name'];


$request = new Request(false);
$response = new Response();

// ensure it's a post response and isXmlHttpRequest
if (!$request->isPost()) {
    $response->setStatusCode(400);
    $response->setContent(json_encode(['status' => $response->getStatusCode(),
        'title' => $response->getReasonPhrase()]));
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
}

// capture information sent in
$senderEmail = $request->getPost('sender_email');
$senderName = $request->getPost('sender_name');
$emailBody = $request->getPost('email_body');

// check that the email is valid
$emailValidator = new Zend\Validator\EmailAddress();
if (!$emailValidator->isValid(trim($request->getPost('sender_email')))) {
    $response->setStatusCode(422);
    $response->setContent(json_encode(["detail" => "Failed Validation",
        "status" => $response->getStatusCode(),
        "title" => $response->getReasonPhrase(),
        "validation_messages" => $emailValidator->getMessages()]));
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
}
try {
    $simpleMailer = new SimpleMailer($config);
    $emailMessage = $simpleMailer->generateEmailMessage($recipientEmail,
            $emailSubject, $senderName . ';' . $senderEmail . '; ' . $emailBody);
    // send email
    $simpleMailer->send($emailMessage);

    $response->setStatusCode(200);
    $response->setContent(json_encode(array("success" => "Your email has been sent")));
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
} catch (\Exception $ex) {
    $response->setStatusCode(500);
    $response->setContent(json_encode(["detail" => $ex->getMessage(),
        "status" => $response->getStatusCode(),
        "title" => $response->getReasonPhrase()]));
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
}