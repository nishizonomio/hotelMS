<?php
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';

// header("Content-Type: application/json");
// echo json_encode(["debug" => true, "post" => $_POST]);
// exit;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoiceId = intval($_POST['invoice_id'] ?? 0);
    $amount    = floatval($_POST['amount_paid'] ?? 0);
    $method    = $_POST['payment_method'] ?? '';
    $comments  = $_POST['comments'] ?? '';

    if (!$invoiceId || !$amount || !$method) {
        header("Location: ../views/payments.php?msg=error&error=" . urlencode("Missing required fields."));
        exit;
    }



    try {

        $invoiceController = new InvoiceController();
        $invoice = $invoiceController->getInvoiceById($invoiceId); // You need a method like this



        if (!$invoice) {
            die("Invoice not found.");
        }

        if (!isset($invoice['data']['total_amount'])) {
            die("Error: Invoice total_amount not found in database.");
        }

        $invoiceTotal = (float)$invoice['data']['total_amount'];
        $paymentController = new PaymentController();
        $alreadyPaid = (float)$paymentController->getTotalPaidByInvoice($invoiceId);

        $remainingBalance = $invoiceTotal - $alreadyPaid;


        // 🛑 Validation
        if ($remainingBalance <= 0) {
            die("Error: Invoice is already fully paid.");
        }

        if ($amount > $remainingBalance) {
            die("Error: Payment amount cannot exceed remaining balance of ₱" . number_format($remainingBalance, 2));
        }


        // --- Manual Payments ---
        if (in_array($method, ['Cash', 'Bank transfer'])) {
            $result = $paymentController->recordPayment($invoiceId, null, $method, $amount);

            if ($result['success']) {
                header("Location: ../views/payments.php?msg=success");
                exit;
            } else {
                header("Location: ../views/payments.php?msg=error&error=" . urlencode($result['message']));
                exit;
            }
        }

        // --- Online Payments (PayMongo/Stripe) ---
        $successUrl = "http://localhost/newBilling/routes/paymentsuccess.php";
        $cancelUrl  = "http://localhost/newBilling/routes/paymentfailed.php";

        $result = $paymentController->processOnlinePayment(
            $invoiceId,
            $amount,
            $method,
            $successUrl,
            $cancelUrl
        );

        if ($result['success'] && isset($result['checkout_url'])) {
            header("Location: " . $result['checkout_url']);
            exit;
        } else {
            header("Location: ../views/payments.php?msg=error&error=" . urlencode($result['message']));
            exit;
        }

        // After recording payment
        $invoiceController = new InvoiceController();
        $invoiceTotal = (float)$invoiceController->getInvoiceById($invoiceId)['data']['total_amount'];
        $alreadyPaid = (float)$paymentController->model->getTotalPaidByInvoice($invoiceId);

        if ($alreadyPaid >= $invoiceTotal) {
            $invoiceController->updateInvoiceStatus($invoiceId, 'paid');
        } elseif ($alreadyPaid > 0) {
            $invoiceController->updateInvoiceStatus($invoiceId, 'partial');
        }
    } catch (Exception $e) {
        header("Location: ../views/payments.php?msg=error&error=" . urlencode("Exception: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../views/payments.php?msg=error&error=" . urlencode("Invalid request."));
    exit;
}
