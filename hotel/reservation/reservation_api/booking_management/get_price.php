<?php
include '../../db_connect.php';

if (isset($_GET['room_id'], $_GET['check_in'], $_GET['check_out'])) {
    $room_id   = (int) $_GET['room_id'];
    $check_in  = $_GET['check_in'];
    $check_out = $_GET['check_out'];

    $sql = "SELECT price_rate FROM rooms WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $stmt->bind_result($price_rate);
    $stmt->fetch();
    $stmt->close();

    if ($price_rate) {
        $checkInTime  = new DateTime($check_in);
        $checkOutTime = new DateTime($check_out);
        $interval     = $checkInTime->diff($checkOutTime);

        $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        $totalHours   = $totalMinutes / 60;

        $ratePerHour = $price_rate / 24;
        $total_price = round($totalHours * $ratePerHour, 2);

        $days    = floor($totalHours / 24);
        $hours   = floor($totalHours % 24);
        $minutes = $totalMinutes % 60;
        $stay_text = "{$days}d {$hours}h {$minutes}m";

        echo json_encode(["price" => number_format($total_price, 2), "stay" => $stay_text]);
    } else {
        echo json_encode(["price" => "0.00", "stay" => "N/A"]);
    }
} else {
    echo json_encode(["price" => "0.00", "stay" => "N/A"]);
}
$conn->close();
?>
