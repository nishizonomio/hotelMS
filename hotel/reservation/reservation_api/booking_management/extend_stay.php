<?php
session_start();
include '../../db_connect.php';
date_default_timezone_set('Asia/Manila');

// Load flash messages
$success_message = $_SESSION['success_message'] ?? "";
$error_message   = $_SESSION['error_message'] ?? "";
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Function to calculate extended stay
function calculateExtendedStay($conn, $booking_type, $booking_id, $new_checkout) {
    if (!in_array($booking_type, ['reservation', 'walk_in'])) {
        return ['success'=>false,'message'=>"❌ Invalid booking type."];
    }

    $booking_id = (int)$booking_id;
    if ($booking_id <= 0) return ['success'=>false,'message'=>"❌ Invalid booking ID."];
    if (empty($new_checkout)) return ['success'=>false,'message'=>"❌ New check-out is required."];

    $table = $booking_type === "reservation" ? "reservations" : "walk_in";
    $id_column = $booking_type === "reservation" ? "reservation_id" : "walkin_id";

    $stmt = $conn->prepare("SELECT * FROM $table WHERE $id_column=?");
    $stmt->bind_param("i",$booking_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) return ['success'=>false,'message'=>"❌ Booking ID does not exist."];
    $booking = $res->fetch_assoc();

    // Check if booking is already checked out
    if ($booking['status'] === 'checked_out') {
        return ['success'=>false,'message'=>"❌ Cannot extend stay - Guest has already checked out!"];
    }

    if (!empty($booking['extended_duration']) && $booking['extended_duration'] !== "00:00:00") {
        return ['success'=>false,'message'=>"❌ This booking already has an extended stay!"];
    }

    if (!isset($booking['room_id']) || $booking['room_id'] <= 0) {
        return ['success'=>false,'message'=>"❌ No room assigned to this booking."];
    }

    $room_id = (int)$booking['room_id'];
    $room_stmt = $conn->prepare("SELECT price_rate, day FROM rooms WHERE room_id=?");
    $room_stmt->bind_param("i",$room_id);
    $room_stmt->execute();
    $room_res = $room_stmt->get_result();
    if ($room_res->num_rows === 0) return ['success'=>false,'message'=>"❌ Room not found."];
    $room = $room_res->fetch_assoc();

    $price_rate = (float)$room['price_rate'];
    $day_rate   = max((float)$room['day'],1);

    try {
        $current_checkout = new DateTime($booking['check_out']);
        $new_checkout_dt  = new DateTime($new_checkout);
    } catch(Exception $e) {
        return ['success'=>false,'message'=>"❌ Invalid date format."];
    }

    if ($new_checkout_dt <= $current_checkout) {
        return ['success'=>false,'message'=>"❌ New check-out must be after current check-out!"];
    }

    $interval = $current_checkout->diff($new_checkout_dt);
    $days = $interval->days;
    $hours = $interval->h;
    $minutes = $interval->i;

    // Ensure minimum 1 hour
    $totalMinutes = ($days*24*60) + ($hours*60) + $minutes;
    if ($totalMinutes < 60) {
        return ['success'=>false,'message'=>"❌ Minimum extension is 1 hour!"];
    }

    $total_hours_decimal = $totalMinutes / 60;

    // Calculate extended price
    $daily_rate = $price_rate / $day_rate;
    $price_per_hour = $daily_rate / 24;
    $extended_price = round($total_hours_decimal * $price_per_hour,2);

    // Format duration DD:HH:MM
    $extended_duration_db = sprintf("%02d:%02d:%02d",$days,$hours,$minutes);

    return [
        'success'=>true,
        'extended_duration'=>$extended_duration_db,
        'extended_price'=>$extended_price
    ];
}

// Handle AJAX calculation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ajax'])) {
    $booking_type = $_POST['booking_type'] ?? null;
    $booking_id = $_POST['booking_id'] ?? null;
    $new_checkout = $_POST['new_checkout'] ?? null;

    $result = calculateExtendedStay($conn,$booking_type,(int)$booking_id,$new_checkout);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['extend_submit'])) {
    $booking_type = $_POST['booking_type'] ?? null;
    $booking_id   = $_POST['booking_id'] ?? null;
    $new_checkout = $_POST['new_checkout'] ?? null;

    $result = calculateExtendedStay($conn,$booking_type,(int)$booking_id,$new_checkout);
    if($result['success']){
        $table = $booking_type==="reservation"?"reservations":"walk_in";
        $id_column = $booking_type==="reservation"?"reservation_id":"walkin_id";

        // ✅ Update check_out + extended_duration + updated_at
        $stmt = $conn->prepare("UPDATE $table 
            SET extended_duration=ADDTIME(COALESCE(extended_duration,'00:00:00'),?), 
                check_out=?, 
                updated_at=NOW() 
            WHERE $id_column=?");
        $stmt->bind_param("ssi",$result['extended_duration'],$new_checkout,$booking_id);
        $stmt->execute();

        // ✅ Update room_payments extended price + duration
        $pay_col = $booking_type==='reservation'?'reservation_id':'walkin_id';
        $stmt2 = $conn->prepare("SELECT payment_id FROM room_payments WHERE $pay_col=? ORDER BY payment_id DESC LIMIT 1");
        $stmt2->bind_param("i",$booking_id);
        $stmt2->execute();
        $pay_res = $stmt2->get_result();
        if($pay_res->num_rows>0){
            $payment_id = $pay_res->fetch_assoc()['payment_id'];
            $stmt3 = $conn->prepare("UPDATE room_payments 
                SET extended_price=COALESCE(extended_price,0)+?, 
                    extended_duration=ADDTIME(COALESCE(extended_duration,'00:00:00'),?), 
                    updated_at=NOW() 
                WHERE payment_id=?");
            $stmt3->bind_param("dsi",$result['extended_price'],$result['extended_duration'],$payment_id);
            $stmt3->execute();
        }

        $_SESSION['success_message'] = "✅ Guest stay extended successfully! ₱{$result['extended_price']} added.";
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header("Location: extend_stay.php");
    exit;
}

// Fetch data - exclude checked out bookings
$reservations = [];
$walkins = [];
$rooms = [];

$res = $conn->query("SELECT reservation_id AS id, guest_id, room_id, check_in, DATE_FORMAT(check_out, '%Y-%m-%dT%H:%i') as check_out, extended_duration, status FROM reservations WHERE status != 'checked_out'");
while($row=$res->fetch_assoc()) $reservations[]=$row;

$res = $conn->query("SELECT walkin_id AS id, guest_id, room_id, check_in, DATE_FORMAT(check_out, '%Y-%m-%dT%H:%i') as check_out, extended_duration, status FROM walk_in WHERE status != 'checked_out'");
while($row=$res->fetch_assoc()) $walkins[]=$row;

$res = $conn->query("SELECT * FROM rooms");
while($row=$res->fetch_assoc()) $rooms[$row['room_id']]=$row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Extend Guest Stay</title>
<link rel="stylesheet" href="../../reservation_css/base.css">
<link rel="stylesheet" href="../../reservation_css/back_button.css">
<style>
#extended_price,#extended_duration,#current_checkout{font-weight:bold;}
#error-msg{color:red;margin-top:5px;}
.alert{text-align:center;padding:10px;border-radius:8px;margin-bottom:10px;opacity:1;transition:opacity 1s ease-out;}
.alert-success{background:lightgreen;color:#000;}
.alert-error{background:#f44336;color:#fff;}
</style>
</head>
<body class="dark-theme">
<div class="container">

    <a href="booking_management.php" class="back-button">
        <img src="/hotel/reservation/reservation_img/back_icon.png" alt="Back">
    </a>

    <h1>Extend Guest Stay</h1>

<?php if(!empty($success_message)): ?>
<div class="alert alert-success"><?=htmlspecialchars($success_message)?></div>
<?php endif; ?>
<?php if(!empty($error_message)): ?>
<div class="alert alert-error"><?=htmlspecialchars($error_message)?></div>
<?php endif; ?>

<form method="POST" id="extend-form" autocomplete="off">
<label for="booking_type_select">Select Booking Type:</label>
<select id="booking_type_select" name="booking_type" required>
<option value="">-- Select Booking Type --</option>
<option value="reservation">Reservation</option>
<option value="walk_in">Walk-in</option>
</select>

<label for="booking_id_input" id="booking_id_label">Booking ID:</label>
<input type="number" name="booking_id" id="booking_id_input" required placeholder="Enter ID">
<div id="error-msg"></div>

<label for="current_checkout">Current Check-out:</label>
<input type="datetime-local" id="current_checkout" readonly>

<label for="new_checkout">New Check-out Date/Time:</label>
<input type="datetime-local" name="new_checkout" id="new_checkout" required>

<label for="extended_price">Estimated Extended Stay Price:</label>
<input type="text" id="extended_price" readonly>

<label for="extended_duration">Extended Duration (DD:HH:MM):</label>
<input type="text" id="extended_duration" readonly>

<input type="submit" name="extend_submit" value="Extend Stay">
</form>
</div>

<script>
const reservations = <?= json_encode($reservations) ?>;
const walkins = <?= json_encode($walkins) ?>;
const rooms = <?= json_encode($rooms) ?>;

const bookingTypeSelect = document.getElementById("booking_type_select");
const bookingIdInput    = document.getElementById("booking_id_input");
const bookingIdLabel    = document.getElementById("booking_id_label");
const currentCheckout   = document.getElementById("current_checkout");
const extendedPrice     = document.getElementById("extended_price");
const extendedDuration  = document.getElementById("extended_duration");
const errorMsgDiv       = document.getElementById("error-msg");
const newCheckoutInput  = document.getElementById("new_checkout");

function resetFields(){
    currentCheckout.value="";
    extendedPrice.value="";
    extendedDuration.value="";
    errorMsgDiv.textContent="";
}

bookingTypeSelect.addEventListener("change",()=>{
    bookingIdInput.value="";
    resetFields();
    bookingIdLabel.textContent = bookingTypeSelect.value==="reservation"?"Reservation ID:":"Walk-in ID:";
});

bookingIdInput.addEventListener("input",()=>{
    resetFields();
    const type = bookingTypeSelect.value;
    const id = bookingIdInput.value.trim();
    if(!type||!id) return;

    let booking = type==="reservation"
        ? reservations.find(r=>r.id==id)
        : walkins.find(w=>w.id==id);

    if(!booking){
        errorMsgDiv.textContent="❌ Booking not found or not eligible for extension!";
        return;
    }

    // Allow extension only for checked_in or reserved
    if(!['checked_in', 'reserved'].includes(booking.status)){
        errorMsgDiv.textContent="❌ Cannot extend stay - Only 'checked_in' or 'reserved' bookings are allowed!";
        return;
    }

    currentCheckout.value=booking.check_out;

    if(booking.extended_duration && booking.extended_duration!=="00:00:00"){
        errorMsgDiv.textContent="❌ Already has extended stay!";
        return;
    }

    if(newCheckoutInput.value) calculateExtendedStayJS();
});

function calculateExtendedStayJS(){
    const type = bookingTypeSelect.value;
    const id = bookingIdInput.value.trim();
    let newCheckout = newCheckoutInput.value;
    if(!type||!id||!newCheckout) return;

    fetch(window.location.href,{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`booking_type=${encodeURIComponent(type)}&booking_id=${encodeURIComponent(id)}&new_checkout=${encodeURIComponent(newCheckout)}&ajax=1`
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            extendedPrice.value = "₱"+data.extended_price;
            extendedDuration.value = data.extended_duration;
            errorMsgDiv.textContent = "";
        } else {
            extendedPrice.value="";
            extendedDuration.value="";
            errorMsgDiv.textContent=data.message;
        }
    }).catch(err=>{
        extendedPrice.value="";
        extendedDuration.value="";
        errorMsgDiv.textContent="❌ Error calculating extended stay!";
        console.error(err);
    });
}

newCheckoutInput.addEventListener("change",calculateExtendedStayJS);

// Auto fade alerts
setTimeout(()=>{
    document.querySelectorAll('.alert').forEach(el=>{
        el.style.opacity="0";
        setTimeout(()=>el.remove(),1000);
    });
},5000);
</script>
</body>
</html>
