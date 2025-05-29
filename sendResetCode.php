<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    $stmt = $con->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $code = strtoupper(substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6));
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'mhmdmhmood27@gmail.com'; // Your email address
            $mail->Password = 'ohzqvbsftgyxdmfy';    // Your email password (or app-specific password)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('mhmdmhmood27@gmail.com', 'Pollify');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Pollify Reset Password Code';
            $mail->Body = "Your verification code is: <b>$code</b>. Please don't share it with anyone.";

            $mail->send();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Please try again later.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
