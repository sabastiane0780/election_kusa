<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Autoload the Composer dependencies
require 'vendor/autoload.php'; // This will automatically load PHPMailer and other dependencies

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Use Gmail's SMTP server or another provider
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';  // Replace with your email
        $mail->Password   = 'your-email-app-password';  // Replace with your app password (not Gmail password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender and Recipient
        $mail->setFrom('your-email@gmail.com', 'KUSA Voting System');
        $mail->addAddress($to);  // Recipient's email address

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message); // Format message with line breaks

        // Send email
        if ($mail->send()) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        return "Mailer Error: " . $mail->ErrorInfo;
    }
}
?>
