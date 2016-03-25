<?php

require_once './vendor/autoload.php';

$senderEmail;
$senderName;
$emailBody;

$recipientEmail = '';
$recipientName = '';

$emailSubject = '[Contact Us] - Your Domain';


$request = new Zend\Http\PhpEnvironment\Request(false);
$response = new Zend\Http\PhpEnvironment\Response();

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

    $email = new Zend\Mail\Message();
    $email->setBody('[' . $senderName . '] says; ' . $emailBody);
    $email->setFrom($senderEmail, $senderName);
    $email->addTo($recipientEmail, $recipientName);
    $email->setSubject($emailSubject);
    // cc the sender by default
    $email->addCc($senderEmail);

    $transport = new \Zend\Mail\Transport\Smtp();
    $options = new Zend\Mail\Transport\SmtpOptions(array(
        // Local client hostname
        'name' => 'localhost.yourdomain',
        // IP address or host name of the SMTP server via which to send messages
        'host' => '127.0.0.1',
        'connection_class' => 'login',
        'connection_config' => array(
            'username' => 'email@yourdomain',
            'password' => "your_password",
        ),
    ));

    $transport->setOptions($options);
    $transport->send($email);
    return $response->send();
} catch (\Exception $ex) {
    $response->setStatusCode(500);
    $response->setContent(json_encode(["detail" => $ex->getMessage(),
        "status" => $response->getStatusCode(),
        "title" => $response->getReasonPhrase()]));
    $response->getHeaders()->addHeaderLine('Content-Type: application/json');
    return $response->send();
}