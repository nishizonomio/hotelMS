<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Minibar Tracking</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
  />
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
  <header class="bg-blue-700 text-white p-4 shadow">
    <h1 class="text-2xl font-semibold text-center">Minibar Tracking System</h1>
  </header>

  <style>

body {
  font-family: 'Outfit', sans-serif;
  background: url("hotel_room.jpg") no-repeat center center fixed;
  background-size: cover;
  margin: 0;
  padding: 0;
}



  </style>

  <main class="flex-grow container mx-auto p-4">
    <?php

    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "hotelpos";

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
      die("<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Connection failed: " . $conn->connect_error . "</div>");
    }

  
    $minibar_id = 0;
    $guest_id = "";
    $room_number = "";
    $item_name = "";
    $quantity = "";
    $price = "";
    $usage_date = "";
    $payment_id = "";
    $edit_mode = false;
    $error_msg = "";
    $success_msg = "";

    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
     
      $guest_id = isset($_POST['guest_id']) ? intval($_POST['guest_id']) : 0;
      $room_number = isset($_POST['room_number']) ? trim($_POST['room_number']) : "";
      $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : "";
      $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
      $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
      $usage_date = isset($_POST['usage_date']) ? trim($_POST['usage_date']) : "";
      $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;

      
      if ($guest_id <= 0 || $room_number === "" || $item_name === "" || $quantity <= 0 || $price <= 0 || $usage_date === "") {
        $error_msg = "Please fill in all required fields with valid values.";
      } else {
        
        if (isset($_POST['minibar_id']) && intval($_POST['minibar_id']) > 0) {
         
          $minibar_id = intval($_POST['minibar_id']);
          $stmt = $conn->prepare("UPDATE MinibarTracking SET guest_id=?, room_number=?, item_name=?, quantity=?, price=?, usage_date=?, payment_id=? WHERE minibar_id=?");
          $stmt->bind_param("issidisi", $guest_id, $room_number, $item_name, $quantity, $price, $usage_date, $payment_id, $minibar_id);
          if ($stmt->execute()) {
            $success_msg = "Record updated successfully.";
          } else {
            $error_msg = "Error updating record: " . $conn->error;
          }
          $stmt->close();
        } else {
          
          $stmt = $conn->prepare("INSERT INTO MinibarTracking (guest_id, room_number, item_name, quantity, price, usage_date, payment_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("issidsi", $guest_id, $room_number, $item_name, $quantity, $price, $usage_date, $payment_id);
          if ($stmt->execute()) {
            $success_msg = "Record saved successfully.";
            
            $guest_id = "";
            $room_number = "";
            $item_name = "";
            $quantity = "";
            $price = "";
            $usage_date = "";
            $payment_id = "";
          } else {
            $error_msg = "Error saving record: " . $conn->error;
          }
          $stmt->close();
        }
      }
    }

    
    if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
      $del_id = intval($_GET['delete']);
      $stmt = $conn->prepare("DELETE FROM MinibarTracking WHERE minibar_id=?");
      $stmt->bind_param("i", $del_id);
      if ($stmt->execute()) {
        echo "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Record deleted successfully.</div>";
      } else {
        echo "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Error deleting record: " . $conn->error . "</div>";
      }
      $stmt->close();
    }

    
    if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
      $edit_id = intval($_GET['edit']);
      $stmt = $conn->prepare("SELECT * FROM MinibarTracking WHERE minibar_id=?");
      $stmt->bind_param("i", $edit_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $minibar_id = $row['minibar_id'];
        $guest_id = $row['guest_id'];
        $room_number = $row['room_number'];
        $item_name = $row['item_name'];
        $quantity = $row['quantity'];
        $price = $row['price'];
        $usage_date = date("Y-m-d\TH:i", strtotime($row['usage_date']));
        $payment_id = $row['payment_id'];
        $edit_mode = true;
      }
      $stmt->close();
    }
    ?>

    <?php if ($error_msg): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error_msg; ?></div>
    <?php endif; ?>
    <?php if ($success_msg): ?>
      <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <section class="bg-white p-6 rounded shadow mb-8">
      <h2 class="text-xl font-semibold mb-4"><?php echo $edit_mode ? "Edit Minibar Record" : "Add New Minibar Record"; ?></h2>
      <form method="POST" class="space-y-4 max-w-3xl">
        <?php if ($edit_mode): ?>
          <input type="hidden" name="minibar_id" value="<?php echo $minibar_id; ?>" />
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="guest_id" class="block font-medium mb-1">Guest ID <span class="text-red-600">*</span></label>
            <input
              type="number"
              id="guest_id"
              name="guest_id"
              min="1"
              value="<?php echo htmlspecialchars($guest_id); ?>"
              required
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          
            />
          </div>

          <div>
            <label for="room_number" class="block font-medium mb-1">Room Number <span class="text-red-600">*</span></label>
            <input
              type="text"
              id="room_number"
              name="room_number"
              maxlength="10"
              value="<?php echo htmlspecialchars($room_number); ?>"
              required
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            
            />
          </div>

          <div>
            <label for="item_name" class="block font-medium mb-1">Item Name <span class="text-red-600">*</span></label>
            <input
              type="text"
              id="item_name"
              name="item_name"
              maxlength="100"
              value="<?php echo htmlspecialchars($item_name); ?>"
              required
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          
            />
          </div>

          <div>
            <label for="quantity" class="block font-medium mb-1">Quantity <span class="text-red-600">*</span></label>
            <input
              type="number"
              id="quantity"
              name="quantity"
              min="1"
              value="<?php echo htmlspecialchars($quantity); ?>"
              required
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              
            />
          </div>

          <div>
            <label for="price" class="block font-medium mb-1">Price (₱) <span class="text-red-600">*</span></label>
            <input
              type="number"
              id="price"
              name="price"
              min="0.01"
              step="0.01"
              value="<?php echo htmlspecialchars($price); ?>"
              required
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
             
            />
          </div>

          <div>
            <label for="usage_date" class="block font-medium mb-1">Usage Date & Time <span class="text-red-600">*</span></label>
            <input
              type="datetime-local"
              id="usage_date"
              name="usage_date"
              value="<?php echo htmlspecialchars($usage_date); ?>"
              required
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label for="payment_id" class="block font-medium mb-1">Payment ID</label>
            <input
              type="number"
              id="payment_id"
              name="payment_id"
              min="0"
              value="<?php echo htmlspecialchars($payment_id); ?>"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
      
            />
          </div>
        </div>

        <div class="pt-4">
          <button
            type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <?php echo $edit_mode ? "Update Record" : "Save Record"; ?>
          </button>
          <?php if ($edit_mode): ?>
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="ml-4 inline-block text-gray-600 hover:text-gray-900">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section class="bg-white p-6 rounded shadow">
      <h2 class="text-xl font-semibold mb-4">Minibar Records</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded">
          <thead class="bg-blue-600 text-white">
            <tr>
              <th class="px-4 py-2 border border-blue-700 text-left text-sm">ID</th>
              <th class="px-4 py-2 border border-blue-700 text-left text-sm">Guest ID</th>
              <th class="px-4 py-2 border border-blue-700 text-left text-sm">Room Number</th>
              <th class="px-4 py-2 border border-blue-700 text-left text-sm">Item Name</th>
              <th class="px-4 py-2 border border-blue-700 text-left text-sm">Quantity</th>
              <th class="px-4 py-2 border border-blue-700 text-left text-sm">Price (₱)</th>
              <th class="px-4 py-2 border border-blue-700 text-left text-sm">Usage Date</th>
              <th class="px-4 py-2 border border-blue-700 text-left text-sm">Payment ID</th>
              <th class="px-4 py-2 border border-blue-700 text-center text-sm">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT * FROM MinibarTracking ORDER BY usage_date DESC, minibar_id DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                echo "<tr class='odd:bg-gray-50 even:bg-white'>";
                echo "<td class='border border-gray-300 px-3 py-1 text-sm'>" . htmlspecialchars($row['minibar_id']) . "</td>";
                echo "<td class='border border-gray-300 px-3 py-1 text-sm'>" . htmlspecialchars($row['guest_id']) . "</td>";
                echo "<td class='border border-gray-300 px-3 py-1 text-sm'>" . htmlspecialchars($row['room_number']) . "</td>";
                echo "<td class='border border-gray-300 px-3 py-1 text-sm'>" . htmlspecialchars($row['item_name']) . "</td>";
                echo "<td class='border border-gray-300 px-3 py-1 text-sm'>" . htmlspecialchars($row['quantity']) . "</td>";
                echo "<td class='border border-gray-300 px-3 py-1 text-sm'>$" . number_format($row['price'], 2) . "</td>";
                echo "<td class='border border-gray-300 px-3 py-1 text-sm'>" . date("Y-m-d H:i", strtotime($row['usage_date'])) . "</td>";
                echo "<td class='border border-gray-300 px-3 py-1 text-sm'>" . ($row['payment_id'] !== null ? htmlspecialchars($row['payment_id']) : "") . "</td>";
                echo "<td class='border border-gray-300 px-3 py-1 text-center text-sm space-x-2'>";
                echo "<a href='?edit=" . $row['minibar_id'] . "' class='text-blue-600 hover:text-blue-800' title='Edit'><i class='fas fa-edit'></i></a>";
                echo "<a href='?delete=" . $row['minibar_id'] . "' onclick='return confirm(\"Are you sure you want to delete this record?\");' class='text-red-600 hover:text-red-800' title='Delete'><i class='fas fa-trash-alt'></i></a>";
                echo "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='9' class='text-center p-4 text-gray-600'>No records found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <footer class="bg-blue-700 text-white p-4 text-center text-sm">
    &copy; <?php echo date("Y"); ?> Minibar Tracking System
  </footer>
</body>
</html>