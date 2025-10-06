<?php
require_once('../db.php');
$order_id = $_POST['order_id'] ?? rand(1000,9999);
$guest = null;

if(!empty($_GET['guest'])){
    $val = $_GET['guest'];
    if(is_numeric($val)){
        $stmt = $conn->prepare("
            SELECT g.guest_id, g.first_name, g.last_name, rm.room_number
            FROM guests g
            LEFT JOIN reservations r ON g.guest_id = r.guest_id AND r.status='checked_in'
            LEFT JOIN rooms rm ON r.room_id = rm.room_id
            WHERE g.guest_id = ?
            ORDER BY r.check_in DESC
            LIMIT 1
        ");
        $stmt->execute([$val]);
    } else {
        $stmt = $conn->prepare("
            SELECT g.guest_id, g.first_name, g.last_name, rm.room_number
            FROM guests g
            LEFT JOIN reservations r ON g.guest_id = r.guest_id AND r.status='checked_in'
            LEFT JOIN rooms rm ON r.room_id = rm.room_id
            WHERE CONCAT(g.first_name,' ',g.last_name) LIKE ?
            ORDER BY r.check_in DESC
            LIMIT 1
        ");
        $stmt->execute(["%$val%"]);
    }
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $guest = $row;
    }
}

$food_items = $conn->query("SELECT * FROM recipes WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
