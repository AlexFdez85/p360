<?php
// p360/includes/functions.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// RUTAS CORRECTAS a tus fuentes de PHPMailer
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

/**
 * Envía un correo. Intenta SMTP y, si falla, usa mail().
 */
function sendEmail(string $toEmail, string $toName, string $subject, string $bodyHtml): bool
{
    // 1) Primero intenta SMTP
    $mail = new PHPMailer(true);
    try {
        // Debug para log (0 = off, 2 = debug completo)
        $mail->SMTPDebug  = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("[PHPMailer debug level $level] $str");
        };

        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_USER, 'Portal Grupo FERRO');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = strip_tags($bodyHtml);

        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer SMTP falló: " . $e->getMessage());
        // 2) Si falla por SMTP, intenta mail()
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Portal Grupo FERRO <" . SMTP_USER . ">\r\n";

        return mail($toEmail, $subject, $bodyHtml, $headers);
    }
}

