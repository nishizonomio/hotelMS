<?php
session_start();
include '../../db_connect.php';
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$response = [
    "status" => "success",
    "message" => ""
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_type = $_POST['booking_type'] ?? null;
    $booking_id   = $_POST['booking_id'] ?? null;

    if (!$booking_type || !$booking_id) {
        $response['status'] = "error";
        $response['message'] = "Missing booking type or booking ID.";
        echo json_encode($response);
        exit;
    }

    try {
        if ($booking_type === "reservation") {
            $stmt = $conn->prepare("SELECT extended_price, stay FROM room_payments WHERE reservation_id = ?");
        } else {
            $stmt = $conn->prepare("SELECT extended_price, stay FROM room_payments WHERE walkin_id = ?");
        }

        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $already_extended = false;

        while ($row = $result->fetch_assoc()) {
            if (!empty($row['extended_price']) || strpos($row['stay'], "Extended") !== false) {
                $already_extended = true;
                break;
            }
        }

        if ($already_extended) {
            $response['status'] = "restricted";
            $response['message'] = "This booking already has an extended stay. Please recommend the guest to book a new room.";
        } else {
            $response['message'] = "This booking is eligible for an extended stay.";
        }

        $stmt->close();
    } catch (Exception $e) {
        $response['status'] = "error";
        $response['message'] = "Server error: " . $e->getMessage();
    }
} else {
    $response['status'] = "error";
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
exit;
?>
