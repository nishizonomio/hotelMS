<?php
// === generate_signature.php ===
// Run: php generate_signature.php

// Replace with your PayMongo webhook secret (from dashboard)
$webhookSecret = "whsk_1fLzp18Rvb2XW4hW9bv7DiRF";

// Example webhook payload (you can modify this to test other cases)
$payload = json_encode([
    "data" => [
        "id" => "test_123",
        "type" => "checkout_session",
        "attributes" => [
            "status" => "paid"
        ]
    ]
], JSON_UNESCAPED_SLASHES);

// Compute HMAC SHA256
$signature = hash_hmac('sha256', $payload, $webhookSecret);

echo "Payload:\n$payload\n\n";
echo "Generated Signature:\n$signature\n\n";

// Example cURL command
echo "Use this command to test:\n";
echo "curl -X POST \"http://localhost/newBilling/webhooks/test_paymongo.php\" "
    . "-H \"Content-Type: application/json\" "
    . "-H \"Paymongo-Signature: $signature\" "
    . "-d '$payload'\n";
