<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../models/invoice.php';
require_once __DIR__.'/../controllers/invoiceController.php';

header('Content-Type: application/json');

// Read raw input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// DEBUG: Show what PHP received
if (isset($_GET['debug'])) {
    echo json_encode([
        'raw_input' => $raw,
        'parsed_data' => $data,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? null
    ], JSON_PRETTY_PRINT);
    exit;
}

// Validate booking_id
if (empty($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing booking_id']);
    exit;
}

$invoice_date = date('Y-m-d');
$invoice_time = date('H:i:s');

$booking_id = (int)$data['booking_id'];

// Fetch booking and room price

$db = new Database();
$sql = "
    SELECT 
        b.check_in,
        b.check_out,
        r.room_price
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.booking_id = ?
";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Booking not found']);
    exit;
}

// Calculate nights stayed
$checkIn = new DateTime($result['check_in']);
$checkOut = new DateTime($result['check_out']);
$nights = $checkOut->diff($checkIn)->days;
$total_amount = $nights * $result['room_price'];
$status = 'unpaid';


// Create invoice
$invoice = new InvoiceController($db);
$response = $invoice->createInvoice($booking_id, $invoice_date, $invoice_time, $total_amount, $status);

echo json_encode($response);

