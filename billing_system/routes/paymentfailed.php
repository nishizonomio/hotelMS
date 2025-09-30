<?php
require_once __DIR__ . '/../controllers/PaymentController.php';

$invoiceId = $_GET['invoice_id'] ?? null;

if ($invoiceId) {
    $paymentController = new PaymentController();
    $paymentController->updatePaymentStatusByInvoice($invoiceId, 'cancelled');

    header("Location: ../views/payments.php?msg=cancelled");
    exit;
} else {
    header("Location: ../views/payments.php?msg=error&error=Missing+invoice+id");
    exit;
}

echo "<h2>❌ Payment Cancelled</h2>";
echo "<p>You cancelled the payment. <a href='../views/payments.php'>Try again</a>.</p>";
