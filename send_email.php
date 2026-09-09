<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendPasswordResetEmail($email, $token) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'YOUR_EMAIL@gmail.com';
        $mail->Password = 'YOUR_GMAIL_APP_PASSWORD';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('YOUR_EMAIL@gmail.com', 'Library Management System');
        $mail->addAddress($email);

        $resetLink = 'http://localhost/Library-System/reset_password.php?token=' . urlencode($token);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "Click the link below to reset your password:<br><br><a href='$resetLink'>Reset Password</a>";
        $mail->AltBody = "Click the link below to reset your password: $resetLink";

        $mail->send();

        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>