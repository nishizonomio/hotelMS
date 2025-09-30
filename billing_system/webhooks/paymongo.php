<?php
/*require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../models/payment.php';
require_once __DIR__ . '/../models/paymentGateway.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';


$payload = file_get_contents("php://input");


$signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
$computedSignature = hash_hmac('sha256', $payload, $_ENV['PAYMONGO_SECRET_KEY'] ?? '');

if (empty($signatureHeader) || !hash_equals($computedSignature, $signatureHeader)) {
    http_response_code(400);
    echo "Invalid webhook signature";
    exit;
}


$event = json_decode($payload, true);
if (!$event || !isset($event['data'])) {
    http_response_code(400);
    echo "Invalid webhook payload";
    exit;
}

$data       = $event['data'];
$attributes = $data['attributes'] ?? [];
$type       = $data['type'] ?? 'unknown';
$gateway_id = $data['id'] ?? null;  
$status     = $attributes['status'] ?? 'pending';


$paymentModel      = new Payments();
$gatewayModel      = new PaymentGatewayTransactions();
$invoiceController = new InvoiceController();

if ($gateway_id) {
    $conn = (new Database())->getConnection();
    $stmt = $conn->prepare("SELECT payment_id, invoice_id FROM payments WHERE gateway_reference = ?");
    $stmt->bind_param("s", $gateway_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result) {
        $localPaymentId = $result['payment_id'];
        $invoice_id     = $result['invoice_id'];

        
        $amount = isset($attributes['amount']) ? $attributes['amount'] / 100 : 0;

        
        $paymentModel->updatePayment($localPaymentId, 'paymongo', $amount, $status);

        
        $gatewayModel->createTransaction(
            $localPaymentId,
            "PayMongo",
            $gateway_id,
            "200",
            "Webhook event: $type",
            $payload,
            $status
        );

        
        $invoiceController->updateInvoiceStatusFromPayments($invoice_id);
    }
}

http_response_code(200);
echo "Webhook processed"; */

// webhooks/test_paymongo.php

$payload = file_get_contents("php://input");
$signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
$secret = "your_webhook_secret_here"; // use the one from PayMongo dashboard

$expectedSignature = hash_hmac('sha256', $payload, $secret);

file_put_contents(
    __DIR__ . '/../logs/test_webhook.log',
    "==== New Webhook Event ====" . PHP_EOL .
        "Payload: " . $payload . PHP_EOL .
        "Signature Header: " . $signatureHeader . PHP_EOL .
        "Expected: " . $expectedSignature . PHP_EOL .
        "Match? " . (hash_equals($expectedSignature, $signatureHeader) ? "YES" : "NO") . PHP_EOL .
        PHP_EOL,
    FILE_APPEND
);

if (!hash_equals($expectedSignature, $signatureHeader)) {
    http_response_code(400);
    echo "Invalid signature";
    exit;
}

http_response_code(200);
echo "Webhook received and verified!";
