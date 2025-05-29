<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$enteredCode = $data['code'] ?? '';
$storedCode = $_SESSION['reset_code'] ?? '';
$email = $_SESSION['reset_email'] ?? '';

if (!$email || !$storedCode) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired or invalid.']);
    exit;
}

if ($enteredCode === $storedCode) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => "The code entered doesn't match."]);
}
?>
