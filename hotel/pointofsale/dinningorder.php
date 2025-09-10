<html>
<head>
  <title>In-Room Dining Orders Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"></link>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

  <div class="container mx-auto px-4 py-8 flex-grow">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">In-Room Dining Orders Management</h1>

    <?php
    // Database connection parameters
    $host = "localhost";
    $user = "root";
    $password = "";
    $dbname = "hotelpos";

    // Create connection
    $conn = new mysqli($host, $user, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
      die("<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6'>Connection failed: " . $conn->connect_error . "</div>");
    }

    // Initialize variables for form fields
    $service_id = "";
    $guest_id = "";
    $staff_id = "";
    $room_number = "";
    $order_date = "";
    $total_amount = "";
    $payment_id = "";

    $error = "";
    $success = "";

    // Handle Create or Update form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      // Sanitize inputs
      $guest_id = isset($_POST['guest_id']) ? intval($_POST['guest_id']) : null;
      $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
      $room_number = isset($_POST['room_number']) ? trim($_POST['room_number']) : "";
      $order_date = isset($_POST['order_date']) ? trim($_POST['order_date']) : "";
      $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
      $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : null;

      if (empty($guest_id) || empty($staff_id) || empty($room_number) || empty($order_date) || $total_amount <= 0 || empty($payment_id)) {
        $error = "Please fill in all fields with valid values.";
      } else {
        if (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
          // Update existing record
          $service_id = intval($_POST['service_id']);
          $stmt = $conn->prepare("UPDATE InRoomDiningOrders SET guest_id=?, staff_id=?, room_number=?, order_date=?, total_amount=?, payment_id=? WHERE service_id=?");
          $stmt->bind_param("iissdii", $guest_id, $staff_id, $room_number, $order_date, $total_amount, $payment_id, $service_id);
          if ($stmt->execute()) {
            $success = "Order updated successfully.";
          } else {
            $error = "Error updating order: " . $stmt->error;
          }
          $stmt->close();
        } else {
          // Insert new record
          $stmt = $conn->prepare("INSERT INTO InRoomDiningOrders (guest_id, staff_id, room_number, order_date, total_amount, payment_id) VALUES (?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("iissdi", $guest_id, $staff_id, $room_number, $order_date, $total_amount, $payment_id);
          if ($stmt->execute()) {
            $success = "Order added successfully.";
          } else {
            $error = "Error adding order: " . $stmt->error;
          }
          $stmt->close();
        }
      }
    }

    // Handle Delete operation
    if (isset($_GET['delete'])) {
      $del_id = intval($_GET['delete']);
      $stmt = $conn->prepare("DELETE FROM InRoomDiningOrders WHERE service_id=?");
      $stmt->bind_param("i", $del_id);
      if ($stmt->execute()) {
        $success = "Order deleted successfully.";
      } else {
        $error = "Error deleting order: " . $stmt->error;
      }
      $stmt->close();
    }

    // Handle Edit operation - load data for editing
    if (isset($_GET['edit'])) {
      $edit_id = intval($_GET['edit']);
      $stmt = $conn->prepare("SELECT * FROM InRoomDiningOrders WHERE service_id=?");
      $stmt->bind_param("i", $edit_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $service_id = $row['service_id'];
        $guest_id = $row['guest_id'];
        $staff_id = $row['staff_id'];
        $room_number = $row['room_number'];
        $order_date = date("Y-m-d\TH:i", strtotime($row['order_date']));
        $total_amount = $row['total_amount'];
        $payment_id = $row['payment_id'];
      }
      $stmt->close();
    }
    ?>

    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
        <strong class="font-bold"><i class="fas fa-exclamation-triangle"></i> Error:</strong>
        <span class="block sm:inline"> <?php echo $error; ?></span>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
        <strong class="font-bold"><i class="fas fa-check-circle"></i> Success:</strong>
        <span class="block sm:inline"> <?php echo $success; ?></span>
      </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-8 max-w-3xl mx-auto">
      <h2 class="text-xl font-semibold mb-4 text-gray-700"><?php echo $service_id ? "Edit Order #$service_id" : "Add New Order"; ?></h2>
      <form method="POST" action="" class="space-y-4">
        <?php if ($service_id): ?>
          <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($service_id); ?>" />
        <?php endif; ?>

        <div>
          <label for="guest_id" class="block text-gray-700 font-medium mb-1">Guest ID</label>
          <input type="number" id="guest_id" name="guest_id" value="<?php echo htmlspecialchars($guest_id); ?>" required min="1" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label for="staff_id" class="block text-gray-700 font-medium mb-1">Staff ID</label>
          <input type="number" id="staff_id" name="staff_id" value="<?php echo htmlspecialchars($staff_id); ?>" required min="1" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label for="room_number" class="block text-gray-700 font-medium mb-1">Room Number</label>
          <input type="text" id="room_number" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>" required maxlength="10" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label for="order_date" class="block text-gray-700 font-medium mb-1">Order Date & Time</label>
          <input type="datetime-local" id="order_date" name="order_date" value="<?php echo htmlspecialchars($order_date); ?>" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label for="total_amount" class="block text-gray-700 font-medium mb-1">Total Amount ($)</label>
          <input type="number" step="0.01" min="0" id="total_amount" name="total_amount" value="<?php echo htmlspecialchars($total_amount); ?>" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label for="payment_id" class="block text-gray-700 font-medium mb-1">Payment ID</label>
          <input type="number" id="payment_id" name="payment_id" value="<?php echo htmlspecialchars($payment_id); ?>" required min="1" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div class="flex justify-end space-x-4 pt-4">
          <?php if ($service_id): ?>
            <a href="?" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition"><i class="fas fa-times"></i> Cancel</a>
          <?php endif; ?>
          <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
            <?php echo $service_id ? "Update Order" : "Add Order"; ?>
          </button>
        </div>
      </form>
    </div>

    <div class="overflow-x-auto bg-white shadow-md rounded max-w-6xl mx-auto">
      <table class="min-w-full table-auto border-collapse border border-gray-300">
        <thead class="bg-indigo-600 text-white">
          <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Service ID</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Guest ID</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Staff ID</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Room Number</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Order Date & Time</th>
            <th class="border border-gray-300 px-4 py-2 text-right">Total Amount ($)</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Payment ID</th>
            <th class="border border-gray-300 px-4 py-2 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sql = "SELECT * FROM InRoomDiningOrders ORDER BY order_date DESC, service_id DESC";
          $result = $conn->query($sql);
          if ($result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
          ?>
            <tr class="hover:bg-gray-50">
              <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($row['service_id']); ?></td>
              <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($row['guest_id']); ?></td>
              <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($row['staff_id']); ?></td>
              <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($row['room_number']); ?></td>
              <td class="border border-gray-300 px-4 py-2"><?php echo date("Y-m-d H:i", strtotime($row['order_date'])); ?></td>
              <td class="border border-gray-300 px-4 py-2 text-right"><?php echo number_format($row['total_amount'], 2); ?></td>
              <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($row['payment_id']); ?></td>
              <td class="border border-gray-300 px-4 py-2 text-center space-x-2">
                <a href="?edit=<?php echo $row['service_id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit"><i class="fas fa-edit"></i></a>
                <a href="?delete=<?php echo $row['service_id']; ?>" onclick="return confirm('Are you sure you want to delete this order?');" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash-alt"></i></a>
              </td>
            </tr>
          <?php
            endwhile;
          else:
          ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-gray-500">No orders found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <footer class="bg-indigo-600 text-white text-center py-4">
    <p>© <?php echo date("Y"); ?> In-Room Dining Orders Management</p>
  </footer>

</body>
</html>