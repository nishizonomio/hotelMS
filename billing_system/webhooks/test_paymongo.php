<?php
// webhooks/test_paymongo.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';

// Get raw payload
$payload = file_get_contents("php://input");

// Get signature header (simulated PayMongo header)
$signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

// Compute HMAC with your webhook secret
$computedSignature = hash_hmac('sha256', $payload, $_ENV['PAYMONGO_WEBHOOK_SECRET']);

// Response
header('Content-Type: application/json');

if (hash_equals($computedSignature, $signatureHeader)) {
    echo json_encode([
        "valid"   => true,
        "message" => "✅ Signature valid. Webhook payload accepted.",
        "payload" => json_decode($payload, true)
    ]);
} else {
    echo json_encode([
        "valid"   => false,
        "message" => "❌ Invalid signature. Webhook rejected.",
        "received_signature" => $signatureHeader,
        "computed_signature" => $computedSignature,
        "payload" => json_decode($payload, true)
    ]);
}
