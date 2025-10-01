<?php
require_once __DIR__ . '/../controllers/RefundController.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();
$refundController = new RefundController($conn);
$paymentController = new PaymentController($conn);
$invoiceController = new InvoiceController($conn);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $paymentId  = intval($_POST['payment_id'] ?? 0);
    $invoiceId  = intval($_POST['invoice_id'] ?? 0); // optional if needed
    $amount     = floatval($_POST['refund_amount'] ?? 0);
    $method     = $_POST['refund_method'] ?? '';
    $reason     = trim($_POST['refund_reason'] ?? '');
    $action     = $_POST['action'] ?? '';
    $processed_by = 1; // TODO: replace with logged-in user ID

    if ($action === 'request') {
        $result = $refundController->submitRefundRequest($paymentId, $amount, $method, $reason, $processed_by);
        if ($result['success'] ?? false) {
            $_SESSION['message'] = "Refund processed successfully. Status: " . ($result['status'] ?? 'processed');
        } else {
            $_SESSION['error'] = "Refund processing failed: " . ($result['message'] ?? 'Unknown error');
        }
    } elseif ($action === 'approve') {
        $refundId = intval($_POST['refund_id'] ?? 0);
        $refundController->approveRefund($refundId, $processed_by);
    } elseif ($action === 'decline') {
        $refundId = intval($_POST['refund_id'] ?? 0);
        $refundController->declineRefund($refundId, $processed_by);
    } elseif ($action === 'process') {
        $refundId = intval($_POST['refund_id'] ?? 0);
        $refundController->approveRefund($refundId);

        // Set a session message for success/failure


        // Redirect back to the refund page
        // header("Location: /hotel/billing_system/views/refund.php");
        // exit;
    }


    header("Location: /hotel/billing_system/views/refund.php");
    exit;
}
