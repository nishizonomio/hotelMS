<?php
require_once __DIR__ . '/../controllers/PaymentController.php';

$invoiceId = $_GET['invoice_id'] ?? null;

if ($invoiceId) {
    $paymentController = new PaymentController();
    $paymentController->updatePaymentStatusByInvoice($invoiceId, 'succeeded');

    header("Location: ../views/payments.php?msg=success");
    exit;
} else {
    header("Location: ../views/payments.php?msg=error&error=Missing+invoice+id");
    exit;
}


echo "<h2>✅ Payment Successful!</h2>";
echo "<p>Your transaction is being confirmed. You may go back to <a href='../views/payments.php'>Payments</a>.</p>";
