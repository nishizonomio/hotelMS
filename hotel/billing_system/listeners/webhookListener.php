<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../models/invoice.php';

header('Content-Type: application/json');

// Read raw input
$raw = file_get_contents("php://input");

// Decode JSON
$data = json_decode($raw, true);

// Fallback to form-data if JSON is empty
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

// DEBUG MODE: Return what PHP sees
if (isset($_GET['debug'])) {
    echo json_encode([
        'raw_input'   => $raw,
        'parsed_data' => $data,
        'content_type'=> $_SERVER['CONTENT_TYPE'] ?? null
    ], JSON_PRETTY_PRINT);
    exit;
}

// Validate booking_id
if (!isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing booking_id']);
    exit;
}

$bookingId = (int)$data['booking_id'];

// If total_amount is not provided, fetch from DB
if (!isset($data['total_amount'])) {
    $stmt = $db->prepare("SELECT total_amount FROM bookings WHERE booking_id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }

    $totalAmount = $result['total_amount'];
} else {
    $totalAmount = (float)$data['total_amount'];
}

// Create invoice
$creator = new InvoiceCreator($db);
$response = $creator->createInvoice($bookingId, $totalAmount);

echo json_encode($response);
