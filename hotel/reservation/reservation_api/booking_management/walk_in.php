<?php
session_start();
include '../../db_connect.php';
include '../popup_message.php';

// Handle walk-in submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $guest_id   = $_POST['guest_id'];
    $room_id    = $_POST['room_id'];
    $remarks    = $_POST['remarks'];
    $check_out  = $_POST['check_out'];

    $walkin_status = "checked_in"; // walk_in table status
    $room_status   = "occupied";   // rooms table status

    // Override check-in to current server time (MySQL TIMESTAMP compatible)
    $check_in = $_POST['check_in']; // use the value from the form


    // Validate guest
    $stmt_guest = $conn->prepare("SELECT * FROM guests WHERE guest_id = ?");
    $stmt_guest->bind_param("i", $guest_id);
    $stmt_guest->execute();
    $guest_result = $stmt_guest->get_result();
    if ($guest_result->num_rows === 0) {
        $_SESSION['flash_error'] = "❌ No guest found. Please fetch a valid guest first.";
        header("Location: walk_in.php");
        exit;
    }

    // Validate minimum stay of 12 hours
    $checkInTime  = new DateTime($check_in);
    $checkOutTime = new DateTime($check_out);
    $intervalSeconds = $checkOutTime->getTimestamp() - $checkInTime->getTimestamp();
    if ($intervalSeconds < 12 * 3600) {
        $_SESSION['flash_error'] = "❌ Reservation must be at least 12 hours.";
        header("Location: walk_in.php");
        exit;
    }

    // Insert into walk_in
    $sql_walkin = "INSERT INTO walk_in 
        (guest_id, room_id, status, remarks, check_in, check_out) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_walkin = $conn->prepare($sql_walkin);
    if (!$stmt_walkin) {
        $_SESSION['flash_error'] = "❌ SQL Prepare Error: " . $conn->error;
        header("Location: walk_in.php");
        exit;
    }

    $stmt_walkin->bind_param("iissss", $guest_id, $room_id, $walkin_status, $remarks, $check_in, $check_out);
    if ($stmt_walkin->execute()) {
        $walkin_id = $stmt_walkin->insert_id;

        // Get room type & price
        $stmt_room = $conn->prepare("SELECT room_type, price_rate FROM rooms WHERE room_id = ?");
        $stmt_room->bind_param("i", $room_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();
        if ($row = $result_room->fetch_assoc()) {
            $room_type  = $row['room_type'];
            $price_rate = $row['price_rate'];

            // Calculate duration & price
            $interval     = $checkInTime->diff($checkOutTime);
            $totalMinutes = ($interval->days*24*60) + ($interval->h*60) + $interval->i;
            $totalHours   = $totalMinutes / 60;
            $ratePerHour  = $price_rate / 24;
            $total_price  = round($totalHours * $ratePerHour, 2);
            $days    = floor($totalHours / 24);
            $hours   = floor($totalHours % 24);
            $minutes = $totalMinutes % 60;
            $stay_text = "{$days}d {$hours}h {$minutes}m";

            // Insert into room_payments
            $stmt_payment = $conn->prepare("INSERT INTO room_payments (guest_id, walkin_id, room_type, room_price, stay) VALUES (?,?,?,?,?)");
            $stmt_payment->bind_param("iisds", $guest_id, $walkin_id, $room_type, $total_price, $stay_text);
            $stmt_payment->execute();
            $stmt_payment->close();
        }
        $stmt_room->close();

        // Update room status to occupied
        $stmt_update = $conn->prepare("UPDATE rooms SET status=? WHERE room_id=?");
        $stmt_update->bind_param("si", $room_status, $room_id);
        $stmt_update->execute();
        $stmt_update->close();

        $_SESSION['flash_success'] = "✅ Walk-in guest checked in successfully!";
        header("Location: walk_in.php");
        exit;
    } else {
        $_SESSION['flash_error'] = "❌ SQL Execute Error: " . $stmt_walkin->error;
        header("Location: walk_in.php");
        exit;
    }

    $stmt_walkin->close();
}

// Fetch distinct room types
$room_types_result = $conn->query("SELECT DISTINCT room_type FROM rooms");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Walk-in Reservation</title>
<link rel="stylesheet" href="/hotel/reservation/reservation_css/base.css">
<link rel="stylesheet" href="/hotel/reservation/reservation_css/back_button.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
.popup-msg { padding: 10px; margin: 10px 0; border-radius: 5px; font-weight: bold; width: fit-content; }
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
</style>
</head>
<body>
<a href="booking_management.php" class="back-button">
    <img src="/hotel/reservation/reservation_img/back_icon.png" alt="Back">
</a>

<div class="container">
<h2>Create a Walk-in Reservation</h2>

<?php if(isset($_SESSION['flash_success'])): ?>
    <div class="popup-msg success"><?= $_SESSION['flash_success']; ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php elseif(isset($_SESSION['flash_error'])): ?>
    <div class="popup-msg error"><?= $_SESSION['flash_error']; ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form method="post" action="walk_in.php">
    <label for="guest_id">Guest ID:</label>
    <input type="number" id="guest_id" name="guest_id" placeholder="Enter Guest ID" required>
    <button type="button" id="fetch_guest_btn" style="margin-bottom:10px;">🔍 Fetch Guest Info</button>
    <div id="guest_details" style="display:none;"></div>

    <label for="room_type">Room Type:</label>
    <select id="room_type" name="room_type" required>
        <option value="">-- Select Room Type --</option>
        <?php if($room_types_result && $room_types_result->num_rows > 0):
            while($row = $room_types_result->fetch_assoc()):
                echo "<option value='".$row['room_type']."'>".$row['room_type']."</option>";
            endwhile;
        endif; ?>
    </select>

    <label for="room_id">Available Rooms:</label>
    <select id="room_id" name="room_id" required>
        <option value="">-- Select a Room --</option>
    </select>
    <p id="room_count" style="font-weight:bold; margin-top:10px;"></p>

    <label for="check_in">Check-in Date/Time:</label>
    <input type="datetime-local" id="check_in" name="check_in" readonly>

    <label for="check_out">Expected Check-out Date/Time:</label>
    <input type="datetime-local" id="check_out" name="check_out" required>

    <label>Estimated Price:</label>
    <input type="text" id="price_display" readonly placeholder="Select room & dates">

    <label>Stay Duration:</label>
    <input type="text" id="stay_display" readonly placeholder="Select room & dates">

    <label for="remarks">Remarks:</label>
    <textarea id="remarks" name="remarks" rows="4"></textarea>

    <div class="button-group">
        <input type="submit" value="💾 Save Walk-in Reservation">
    </div>
</form>
</div>

<script>
$(document).ready(function(){
    setTimeout(()=>{$(".popup-msg").fadeOut();},5000);

    // Automatically set check-in to current datetime (readonly)
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60000; // adjust timezone
    const localISOTime = new Date(now - offset).toISOString().slice(0,16);
    $("#check_in").val(localISOTime);

    // Load rooms when room type is selected
    function loadRooms(roomType){
        let $roomDropdown = $("#room_id");
        $roomDropdown.html("<option value=''>-- Select a Room --</option>");
        $("#room_count").text("");
        if(!roomType) return;
        $.ajax({
            url:"get_available_room.php",
            type:"POST",
            data:{room_type:roomType},
            dataType:"json",
            success:function(data){
                if(data.status === "success" && data.rooms.length > 0){
                    data.rooms.forEach(room=>{
                        $roomDropdown.append(`<option value="${room.room_id}">${room.room_number}</option>`);
                    });
                    $("#room_count").text(`✅ ${data.rooms.length} rooms available for ${roomType}`);
                } else {
                    alert("❌ No rooms available for the selected Room Type.");
                    $roomDropdown.html("<option value=''>-- Select a Room --</option>");
                }
                $("#check_out,#price_display,#stay_display").val("");
            },
            error:function(){ alert("⚠️ Failed to fetch rooms."); }
        });
    }

    $("#room_type").on("change", function(){ 
        let roomType = $(this).val(); 
        $("#check_out,#price_display,#stay_display").val("");
        $("#room_id").html("<option value=''>-- Select a Room --</option>");
        if(roomType) loadRooms(roomType);
    });

    function updatePrice(){
        let roomType = $("#room_type").val();
        let roomId   = $("#room_id").val();
        let checkIn  = $("#check_in").val();
        let checkOut = $("#check_out").val();

        if(!roomType){ alert("❌ Please select a Room Type first."); $("#check_out").val(""); return; }
        if(!roomId){ alert("❌ Please select a Room first."); $("#check_out").val(""); return; }

        if(checkIn && checkOut){
            let start = new Date(checkIn);
            let end   = new Date(checkOut);
            if((end - start)/(1000*3600) < 12){
                alert("❌ Reservation must be at least 12 hours.");
                $("#check_out,#price_display,#stay_display").val("");
                return;
            }
            $.get('get_price.php',{room_id:roomId,check_in:checkIn,check_out:checkOut},function(data){
                let result = JSON.parse(data);
                $("#price_display").val("₱"+result.price);
                $("#stay_display").val(result.stay);
            });
        }
    }

    $("#room_id,#check_out").on("change", updatePrice);

    // Fetch guest info
    $("#fetch_guest_btn").on("click", function(){
        let guest_id = $("#guest_id").val().trim();
        if(guest_id === "") { alert("❌ Please enter a Guest ID"); return; }
        $.get("get_guest.php", {guest_id: guest_id}, function(data){
            if(data.status === "success"){
                let g = data.guest;
                $("#guest_details").html(
                    `<h3>Guest Information</h3>
                    <p><span>ID:</span> ${g.guest_id}</p>
                    <p><span>Name:</span> ${g.first_name} ${g.last_name}</p>
                    <p><span>Email:</span> ${g.email}</p>
                    <p><span>Phone 1:</span> ${g.first_phone}</p>
                    <p><span>Phone 2:</span> ${g.second_phone}</p>` 
                ).fadeIn();
            } else {
                alert(data.message);
                $("#guest_details").hide();
            }
        }, "json");
    });
});
</script>
</body>
</html>
