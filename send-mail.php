<?php

require_once './vendor/autoload.php';
require_once './src/Bitmarshals/Mail/SimpleMailer.php';
$config = require_once './config/config.php';

use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Bitmarshals\Mail\SimpleMailer;

$clientEmail;
$clientName;
$emailBody;

$recipientEmail = $config['simple_mailer']['recipient']['email'];

$emailSubject = '[Contact Us] - ' . $config['domain_name'];


$request = new Request();
$response = new Response();

function redirect(Response $response, $url = '/')
{
    $response->getHeaders()->addHeaderLine('Location', $url);
    $response->setStatusCode(302);
    return $response->send();
}

$isXmlHttpRequest = $request->isXmlHttpRequest();

// ensure it's a post response and isXmlHttpRequest
if (!$request->isPost()) {
    if (!$isXmlHttpRequest) {
        return redirect($response, '/');
    }
    $response->setStatusCode(400);
    $response->setContent(json_encode(['status' => $response->getStatusCode(),
        'title' => $response->getReasonPhrase()]));
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
}

// capture information sent in
$clientEmail = $request->getPost('client_email');
$clientName = $request->getPost('client_name');
$emailBody = $request->getPost('email_body');

// check that the email is valid
$emailValidator = new Zend\Validator\EmailAddress();
if (!$emailValidator->isValid(trim($request->getPost('client_email')))) {
    $response->setStatusCode(422);
    $response->setContent(json_encode(["detail" => "Failed Validation",
        "status" => $response->getStatusCode(),
        "title" => $response->getReasonPhrase(),
        "validation_messages" => $emailValidator->getMessages()]));
    if (!$isXmlHttpRequest) {
        return redirect($response, '/');
    }
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
}
try {
    $simpleMailer = new SimpleMailer($config);
    $emailMessage = $simpleMailer->generateEmailMessage($recipientEmail,
            $emailSubject, $clientName . ';' . $clientEmail . '; ' . $emailBody);
    // send email
    $simpleMailer->send($emailMessage);

    $response->setStatusCode(200);
    $response->setContent(json_encode(array("success" => "Your email has been sent")));

    if (!$isXmlHttpRequest) {
        return redirect($response, '/');
    }
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
} catch (\Exception $ex) {
    $response->setStatusCode(500);
    $response->setContent(json_encode(["detail" => $ex->getMessage(),
        "status" => $response->getStatusCode(),
        "title" => $response->getReasonPhrase()]));
    if (!$isXmlHttpRequest) {
        return redirect($response, $url);
    }
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
}