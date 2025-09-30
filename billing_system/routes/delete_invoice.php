<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';

$db = (new Database())->getConnection();
$controller = new InvoiceController($db);

if (!isset($_GET['id'])) {
    die("Invoice ID is required.");
}

$invoice_id = intval($_GET['id']);
$response = $controller->deleteInvoice($invoice_id);

if ($response['success']) {
    header("Location: ../views/invoice.php?msg=deleted");
    exit;
} else {
    die($response['message']);
}
