<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class Mailer
{
    //faz o envio de email e salva log no bd
    public function sendEmail($body, $email, $subject)
    {
        $mail = new PHPMailer(true);
        try {
            $result = array('id_user' => null, 'error' => null, 'log' => '');
            $mail->SMPTPDebug = 2;
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = "tls";
            $mail->Username = getenv('MAIL');
            $mail->Password = getenv('MAIL_PASSWORD');
            $mail->Port = 587;

            $mail->setFrom(getenv('MAIL'));
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            if (!$mail->send()) { //se houve erro no envio
                $result["error"] = true;
                $result["log"] = "Erro ao enviar e-mail.";
            }
            return $result;
        } catch (Exception $e) {
            $result["error"] = true;
            $result["log"] = $mail->ErrorInfo;
            return $result;
        }
    }
}
