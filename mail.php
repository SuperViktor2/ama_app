<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

function posaljiMail($email, $naslov, $poruka)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'mail.amastudio.hr';
        $mail->SMTPAuth = true;

        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Timeout = 10;

        $mail->setFrom(
            '_mainaccount@amastudio.hr',
            'Ama App'
        );

        $mail->addAddress($email);

        // Email content
        $mail->isHTML(false);
        $mail->Subject = $naslov;
        $mail->Body = $poruka;

        $mail->send();

        return true;

    } catch (Exception $e) {

        echo "Greška pri slanju: " . $mail->ErrorInfo;

        return false;
    }
}