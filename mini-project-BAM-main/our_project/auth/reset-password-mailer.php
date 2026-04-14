<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../tools/vendor/autoload.php';

function send_verification_email($user_email, $user_name, $reset_password_link) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aalumni00@gmail.com';      // Your email
        $mail->Password = 'hkni ipac sjlj mcoy';          // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('alu.admin@ucaconnect.com', 'UCA Connect');
        $mail->addAddress($user_email, $user_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset the password of Your UCA Connect Account';
        $mail->Body = "
            <h2>Hello again to UCA Connect, $user_name!</h2>
            <p>Click the link below to reset your password:</p>
            <a href='$reset_password_link'>Verify Email</a>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}