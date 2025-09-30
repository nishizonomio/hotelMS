<?php

require_once __DIR__ . '/../controllers/InvoiceController.php';
require_once __DIR__ . '/../config/database.php';

session_start();

// Validate form input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'unpaid';

    if ($booking_id <= 0) {
        $_SESSION['flash_message'] = "Invalid booking selected.";
        $_SESSION['flash_type'] = "danger";
        header("Location: ../views/invoice.php");
        exit;
    }


    try {
        $controller = new InvoiceController();
        $result = $controller->createInvoice($booking_id, $status);

        if ($result['success']) {
            $_SESSION['flash_message'] = "Invoice created successfully!";
            $_SESSION['flash_type'] = "success";
            // echo "Invoice created successfully! ID: " . $result['invoice_id'];
        } else {
            $_SESSION['flash_message'] = $result['message'] ?? "Failed to create invoice.";
            $_SESSION['flash_type'] = "danger";
            // echo "Failed to create invoice: " . ($result['message'] ?? "Unknown error");
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
        // echo "Exception: " . $e->getMessage();
    }
} else {
    $_SESSION['flash_message'] = "Invalid request method.";
    $_SESSION['flash_type'] = "danger";
    // die("Invalid request method.");
}

header("Location: ../views/invoice.php");
exit;
