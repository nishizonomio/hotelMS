<html>
<head>
  <title>Bar POS Management CRUD</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"></link>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center p-4">
  <div class="w-full max-w-5xl bg-white rounded shadow p-6 mt-6">
    <h1 class="text-3xl font-bold mb-6 text-center">Bar POS Management</h1>

    <?php
    // Database connection parameters
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "hotelpos";

    // Create connection
    $conn = new mysqli($host, $user, $pass);

    // Check connection
    if ($conn->connect_error) {
      die("<div class='text-red-600 font-semibold mb-4'>Connection failed: " . $conn->connect_error . "</div>");
    }

    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS $db");
    $conn->select_db($db);

    // Create table if not exists
    $createTableSQL = "CREATE TABLE IF NOT EXISTS BarPOS (
      bar_order_id INT AUTO_INCREMENT PRIMARY KEY,
      guest_id INT NOT NULL,
      staff_id INT NOT NULL,
      table_number VARCHAR(10) NOT NULL,
      order_date DATETIME NOT NULL,
      total_amount DECIMAL(10,2) NOT NULL,
      payment_id INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($createTableSQL)) {
      echo "<div class='text-red-600 font-semibold mb-4'>Error creating table: " . $conn->error . "</div>";
    }

    // Initialize variables for form
    $bar_order_id = 0;
    $guest_id = "";
    $staff_id = "";
    $table_number = "";
    $order_date = "";
    $total_amount = "";
    $payment_id = "";
    $edit_mode = false;
    $message = "";

    // Handle form submission for Add or Update
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $guest_id = intval($_POST["guest_id"]);
      $staff_id = intval($_POST["staff_id"]);
      $table_number = trim($_POST["table_number"]);
      $order_date = trim($_POST["order_date"]);
      $total_amount = floatval($_POST["total_amount"]);
      $payment_id = intval($_POST["payment_id"]);

      if (isset($_POST["add"])) {
        // Add new record
        $stmt = $conn->prepare("INSERT INTO BarPOS (guest_id, staff_id, table_number, order_date, total_amount, payment_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdi", $guest_id, $staff_id, $table_number, $order_date, $total_amount, $payment_id);
        if ($stmt->execute()) {
          $message = "<div class='text-green-600 font-semibold mb-4'>POS record added successfully.</div>";
        } else {
          $message = "<div class='text-red-600 font-semibold mb-4'>Error adding record: " . $stmt->error . "</div>";
        }
        $stmt->close();
      } elseif (isset($_POST["update"])) {
        // Update existing record
        $bar_order_id = intval($_POST["bar_order_id"]);
        $stmt = $conn->prepare("UPDATE BarPOS SET guest_id=?, staff_id=?, table_number=?, order_date=?, total_amount=?, payment_id=? WHERE bar_order_id=?");
        $stmt->bind_param("iissdii", $guest_id, $staff_id, $table_number, $order_date, $total_amount, $payment_id, $bar_order_id);
        if ($stmt->execute()) {
          $message = "<div class='text-green-600 font-semibold mb-4'>POS record updated successfully.</div>";
        } else {
          $message = "<div class='text-red-600 font-semibold mb-4'>Error updating record: " . $stmt->error . "</div>";
        }
        $stmt->close();
      }
    }

    // Handle delete action
    if (isset($_GET["delete"])) {
      $del_id = intval($_GET["delete"]);
      $stmt = $conn->prepare("DELETE FROM BarPOS WHERE bar_order_id=?");
      $stmt->bind_param("i", $del_id);
      if ($stmt->execute()) {
        $message = "<div class='text-green-600 font-semibold mb-4'>POS record deleted successfully.</div>";
      } else {
        $message = "<div class='text-red-600 font-semibold mb-4'>Error deleting record: " . $stmt->error . "</div>";
      }
      $stmt->close();
    }

    // Handle edit action - load data for editing
    if (isset($_GET["edit"])) {
      $edit_id = intval($_GET["edit"]);
      $stmt = $conn->prepare("SELECT * FROM BarPOS WHERE bar_order_id=?");
      $stmt->bind_param("i", $edit_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $bar_order_id = $row["bar_order_id"];
        $guest_id = $row["guest_id"];
        $staff_id = $row["staff_id"];
        $table_number = $row["table_number"];
        $order_date = $row["order_date"];
        $total_amount = $row["total_amount"];
        $payment_id = $row["payment_id"];
        $edit_mode = true;
      }
      $stmt->close();
    }

    echo $message;
    ?>

    <form method="POST" class="mb-8 bg-gray-50 p-6 rounded shadow-md max-w-4xl mx-auto">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label for="guest_id" class="block font-semibold mb-1">Guest ID</label>
          <input type="number" name="guest_id" id="guest_id" required min="1" value="<?php echo htmlspecialchars($guest_id); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label for="staff_id" class="block font-semibold mb-1">Staff ID</label>
          <input type="number" name="staff_id" id="staff_id" required min="1" value="<?php echo htmlspecialchars($staff_id); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label for="table_number" class="block font-semibold mb-1">Table Number</label>
          <input type="text" name="table_number" id="table_number" required maxlength="10" value="<?php echo htmlspecialchars($table_number); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label for="order_date" class="block font-semibold mb-1">Order Date & Time</label>
          <input type="datetime-local" name="order_date" id="order_date" required value="<?php echo $order_date ? date('Y-m-d\TH:i', strtotime($order_date)) : ''; ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label for="total_amount" class="block font-semibold mb-1">Total Amount ($)</label>
          <input type="number" step="0.01" min="0" name="total_amount" id="total_amount" required value="<?php echo htmlspecialchars($total_amount); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label for="payment_id" class="block font-semibold mb-1">Payment ID</label>
          <input type="number" name="payment_id" id="payment_id" required min="1" value="<?php echo htmlspecialchars($payment_id); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>
      <?php if ($edit_mode): ?>
        <input type="hidden" name="bar_order_id" value="<?php echo $bar_order_id; ?>" />
        <div class="flex justify-center space-x-4">
          <button type="submit" name="update" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded flex items-center gap-2">
            <i class="fas fa-edit"></i> Update POS
          </button>
          <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="bg-gray-400 hover:bg-gray-500 text-white font-semibold px-6 py-2 rounded flex items-center gap-2">
            <i class="fas fa-times"></i> Cancel
          </a>
        </div>
      <?php else: ?>
        <div class="flex justify-center">
          <button type="submit" name="add" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded flex items-center gap-2">
            <i class="fas fa-plus"></i> Add POS
          </button>
        </div>
      <?php endif; ?>
    </form>

    <div class="overflow-x-auto">
      <table class="min-w-full bg-white rounded shadow">
        <thead class="bg-blue-600 text-white">
          <tr>
            <th class="py-3 px-6 text-left">Order ID</th>
            <th class="py-3 px-6 text-left">Guest ID</th>
            <th class="py-3 px-6 text-left">Staff ID</th>
            <th class="py-3 px-6 text-left">Table Number</th>
            <th class="py-3 px-6 text-left">Order Date</th>
            <th class="py-3 px-6 text-left">Total Amount ($)</th>
            <th class="py-3 px-6 text-left">Payment ID</th>
            <th class="py-3 px-6 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT * FROM BarPOS ORDER BY bar_order_id DESC");
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo "<tr class='border-b hover:bg-gray-50'>";
              echo "<td class='py-3 px-6'>" . $row["bar_order_id"] . "</td>";
              echo "<td class='py-3 px-6'>" . $row["guest_id"] . "</td>";
              echo "<td class='py-3 px-6'>" . $row["staff_id"] . "</td>";
              echo "<td class='py-3 px-6'>" . htmlspecialchars($row["table_number"]) . "</td>";
              echo "<td class='py-3 px-6'>" . date("Y-m-d H:i", strtotime($row["order_date"])) . "</td>";
              echo "<td class='py-3 px-6'>" . number_format($row["total_amount"], 2) . "</td>";
              echo "<td class='py-3 px-6'>" . $row["payment_id"] . "</td>";
              echo "<td class='py-3 px-6 text-center space-x-2'>";
              echo "<a href='?edit=" . $row["bar_order_id"] . "' class='text-yellow-500 hover:text-yellow-700' title='Edit'><i class='fas fa-edit'></i></a>";
              echo "<a href='?delete=" . $row["bar_order_id"] . "' onclick='return confirm(\"Are you sure you want to delete this POS record?\");' class='text-red-600 hover:text-red-800' title='Delete'><i class='fas fa-trash-alt'></i></a>";
              echo "</td>";
              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='8' class='text-center py-6 text-gray-500'>No POS records found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>