<?php
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$userMessage = $input['message'] ?? '';

if (!$userMessage) {
    echo json_encode(["reply" => "No input provided."]);
    exit;
}

$apiKey = "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";
$claudeEndpoint = "https://api.anthropic.com/v1/messages";

$payload = [
    "model" => "claude-3-opus-20240229",
    "max_tokens" => 1000,
    "messages" => [
        ["role" => "user", "content" => $userMessage]
    ]
];

$headers = [
    "Content-Type: application/json",
    "x-api-key: $apiKey",
    "anthropic-version: 2023-06-01"
];

$ch = curl_init($claudeEndpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["reply" => "cURL error: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);
$reply = $result['content'][0]['text'] ?? "No reply received.";

echo json_encode(["reply" => $reply]);
?>