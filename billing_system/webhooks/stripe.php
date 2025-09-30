<?php
// webhooks/stripe.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../models/payment.php';
require_once __DIR__ . '/../models/paymentGateway.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit("Invalid payload");
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit("Invalid signature");
}

$paymentModel = new Payments();
$gatewayModel = new PaymentGatewayTransactions();
$invoiceController = new InvoiceController();

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        $payment_id = $session->id;
        $status = $session->payment_status; // "paid" or "unpaid"

        // Lookup local payment
        $conn = (new Database())->getConnection();
        $stmt = $conn->prepare("SELECT payment_id, invoice_id FROM payments WHERE gateway_reference = ?");
        $stmt->bind_param("s", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) {
            $localPaymentId = $result['payment_id'];
            $invoice_id = $result['invoice_id'];

            $paymentModel->updatePayment($localPaymentId, 'credit_card', $session->amount_total / 100, $status);

            $gatewayModel->createTransaction(
                $localPaymentId,
                "Stripe",
                $payment_id,
                "200",
                "Webhook event: checkout.session.completed",
                $payload,
                $status
            );

            $invoiceController->updateInvoiceStatusFromPayments($invoice_id);
        }
        break;
    default:
        // log unhandled events
        break;
}

http_response_code(200);
echo "Webhook processed";
