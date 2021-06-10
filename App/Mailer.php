<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class Mailer
{
    public function sendEmail()
    {
        $mail = new PHPMailer(true);
        try {
            $mail->SMPTPDebug = SMTP::DEBUG_SERVER;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = "armazenamento.eduardo@gmail.com";
            $mail->Password = getenv('MAIL_PASSWORD');
            $mail->Port = 587;

            $mail->setFrom('armazenamento.eduardo@gmail.com');
            $mail->addAddress('eduardo_vk@hotmail.com');
            $mail->isHTML(true);
            $mail->Subject = "Teste de e-mail!";
            $mail->Body = "Teste de e-mail PHP Mailer! <strong>by Eduardo</strong>";
            $mail->AltBody = "Teste de e-mail PHP Mailer! by Eduardo";

            if ($mail->send()) {
                return "E-mail enviado com sucesso!";
            } else {
                return "Erro ao enviar e-mail.";
            }
        } catch (Exception $e) {
            return "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        }
    }
}
