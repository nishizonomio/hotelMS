<?php
require_once __DIR__.'/../controllers/paymentController.php';
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

$db         = new Database();
$conn       = $db->getConnection();
$controller = new PaymentController($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error'=>'Unsupported request method']);
    exit;
}

$method    = $_POST['payment_method'] ?? null;
$amount    = (float) ($_POST['amount'] ?? 0);
$reference = $_POST['reference']  ?? 'N/A';
$paymentId = (int) ($_POST['payment_id'] ?? 0);

if (!$method || $amount <= 0 || !$paymentId) {
    echo json_encode(['error'=>'Missing or invalid input']);
    exit;
}

$result = $controller->handlePayment($method, $amount, $reference, $paymentId);
echo json_encode($result);
