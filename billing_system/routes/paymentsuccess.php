<?php
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';

$invoiceId = $_GET['invoice_id'] ?? null;

if ($invoiceId) {
    $paymentController = new PaymentController();
    $paymentController->updatePaymentStatusByInvoice($invoiceId, 'succeeded');
    $invoiceController = new InvoiceController();
    $invoiceController->updateInvoiceStatusFromPayments($invoiceId);

    header("Location: ../views/payments.php?msg=success");
    exit;
} else {
    header("Location: ../views/payments.php?msg=error&error=Missing+invoice+id");
    exit;
}
