<?php
header('Content-Type: application/json');
include '../../db_connect.php';

if(isset($_GET['guest_id'])){
    $guest_id = intval($_GET['guest_id']);
    
    $stmt = $conn->prepare("SELECT * FROM guests WHERE guest_id = ?");
    $stmt->bind_param("i", $guest_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $guest = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'guest'  => [
                'guest_id'    => $guest['guest_id'],
                'first_name'  => $guest['first_name'],
                'last_name'   => $guest['last_name'],
                'email'       => $guest['email'],
                'first_phone' => $guest['first_phone'],
                'second_phone'=> $guest['second_phone']
            ]
        ]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Guest not found']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status'=>'error','message'=>'Guest ID not provided']);
}
