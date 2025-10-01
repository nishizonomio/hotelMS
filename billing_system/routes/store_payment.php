<?php
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/payments.php?msg=error&text=" . urlencode("Invalid request."));
    exit;
}

// Get POST data
$invoiceId = intval($_POST['invoice_id'] ?? 0);
$amount    = floatval($_POST['amount_paid'] ?? 0);
$method    = $_POST['payment_method'] ?? '';
$comments  = $_POST['comments'] ?? '';

if (!$invoiceId || $amount <= 0 || !$method) {
    header("Location: ../views/payments.php?msg=error&text=" . urlencode("Missing or invalid required fields."));
    exit;
}

try {
    $invoiceController = new InvoiceController();
    $invoiceResult = $invoiceController->getInvoiceById($invoiceId);

    if (!$invoiceResult || !$invoiceResult['success']) {
        header("Location: ../views/payments.php?msg=error&text=" . urlencode("Invoice not found."));
        exit;
    }

    $invoiceData = $invoiceResult['data'];
    $invoiceTotal = (float)$invoiceData['total_amount'];

    $paymentController = new PaymentController();
    $alreadyPaid = (float)$paymentController->getTotalPaidByInvoice($invoiceId);
    $remainingBalance = $invoiceTotal - $alreadyPaid;

    // Validation
    if ($remainingBalance <= 0) {
        header("Location: ../views/payments.php?msg=error&text=" . urlencode("Invoice is already fully paid."));
        exit;
    }

    if ($amount > $remainingBalance) {
        header("Location: ../views/payments.php?msg=error&text=" . urlencode("Payment amount cannot exceed remaining balance of ₱" . number_format($remainingBalance, 2)));
        exit;
    }

    // --- Manual Payments ---
    if (in_array($method, ['cash', 'bank_transfer'])) {
        $result = $paymentController->recordPayment($invoiceId, null, $method, $amount);

        if ($result['success']) {
            // Update invoice status
            $alreadyPaid += $amount;
            if ($alreadyPaid >= $invoiceTotal) {
                $invoiceController->updateInvoiceStatus($invoiceId, 'paid');
            } else {
                $invoiceController->updateInvoiceStatus($invoiceId, 'partial');
            }

            header("Location: ../views/payments.php?msg=success&text=" . urlencode("Payment recorded successfully."));
            exit;
        } else {
            header("Location: ../views/payments.php?msg=error&text=" . urlencode($result['message']));
            exit;
        }
    }

    // --- Online Payments (PayMongo/Stripe) ---
    $successUrl = "http://localhost/hotel/billing_system/routes/paymentsuccess.php";
    $cancelUrl  = "http://localhost/hotel/billing_system/routes/paymentfailed.php";

    $result = $paymentController->processOnlinePayment($invoiceId, $amount, $method, $successUrl, $cancelUrl, $status);

    if ($result['success'] && isset($result['checkout_url'])) {
        header("Location: " . $result['checkout_url']);
        exit;
    } else {
        header("Location: ../views/payments.php?msg=error&text=" . urlencode("Payment gateway error."));
        exit;
    }
} catch (Exception $e) {
    header("Location: ../views/payments.php?msg=error&text=" . urlencode("Exception: " . $e->getMessage()));
    exit;
}
