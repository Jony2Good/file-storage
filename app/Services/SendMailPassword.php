<?php

namespace app\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SendMailPassword
{
    /**
     * @param string $email
     * @return void
     */
    public function sendMail(string $email): void
    {
        $config = require_once "../config/mail_settings.php";
        $mail = new PHPMailer();
        try {
            $mail->isSMTP();
            $mail->SMTPAuth = $config['smtp_auth'];
            $mail->SMTPSecure = $config['smtp_secure'];
            $mail->Port = $config['port'];
            $mail->Host = $config['host'];
            //Recipients
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->setFrom($config['from_email'], $config['name_app']);
            $mail->addAddress($email);
            //Content
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Subject = $config['subject'];
            $mail->Body = file_get_contents("../views/mail.php");
            if ($mail->send()) {
                http_response_code(200);
                echo json_encode(array("message" => 'Mail has been sent'));
            } else {
                http_response_code(400);
                echo json_encode(array("error" => "Error with sending mail"));
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo "Mail error: " . $e->getMessage();
        }
    }
}
