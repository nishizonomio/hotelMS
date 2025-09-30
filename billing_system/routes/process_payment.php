<?php
session_start();
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoiceId = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $amountPaid = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : null;

    if ($invoiceId <= 0 || $amountPaid <= 0 || !$paymentMethod) {
        $_SESSION['error'] = "Invalid payment details.";
        header("Location: ../views/payments.php");
        exit;
    }

    try {
        $paymentController = new PaymentController();
        $invoiceController = new InvoiceController();

        // 1. Record the payment
        $paymentResult = $paymentController->recordPayment(
            $invoiceId,
            null, // group billing id (not used for now)
            $paymentMethod,
            $amountPaid,
            date('Y-m-d'),
            date('H:i:s'),
            null
        );

        if ($paymentResult['success']) {
            // 2. Update invoice status to PAID
            $invoiceController->updateInvoiceStatus($invoiceId, 'paid');

            $_SESSION['success'] = "Payment successful! Invoice #$invoiceId has been marked as PAID.";
        } else {
            $_SESSION['error'] = "Payment failed: " . ($paymentResult['error'] ?? 'Unknown error.');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: ../views/payments.php");
exit;
