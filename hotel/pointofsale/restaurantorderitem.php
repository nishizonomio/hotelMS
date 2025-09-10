<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Restaurant Order Items</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
  />
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
  <div class="container mx-auto px-4 py-8 flex-grow">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">
      Restaurant Order Items Management
    </h1>

    <?php
    // Database connection parameters
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "hotelpos";

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
      die("<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</div>");
    }

    // Initialize variables for form fields
    $item_id = 0;
    $order_id = "";
    $item_name = "";
    $quantity = "";
    $price = "";
    $edit_mode = false;
    $message = "";

    // Handle Add or Update form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $order_id = intval($_POST["order_id"]);
      $item_name = trim($_POST["item_name"]);
      $quantity = intval($_POST["quantity"]);
      $price = floatval($_POST["price"]);

      if (isset($_POST["add"])) {
        // Add new item
        $stmt = $conn->prepare("INSERT INTO restaurantOrderItems (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
        if ($stmt) {
          $stmt->bind_param("isid", $order_id, $item_name, $quantity, $price);
          if ($stmt->execute()) {
          
            // Clear form fields after successful add
            $order_id = "";
            $item_name = "";
            $quantity = "";
            $price = "";
          } else {
            $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Error adding item: " . htmlspecialchars($stmt->error) . "</div>";
          }
          $stmt->close();
        } else {
          $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Prepare failed: " . htmlspecialchars($conn->error) . "</div>";
        }
      } elseif (isset($_POST["update"])) {
        // Update existing item
        $item_id = intval($_POST["item_id"]);
        $stmt = $conn->prepare("UPDATE restaurantOrderItems SET order_id = ?, item_name = ?, quantity = ?, price = ? WHERE item_id = ?");
        if ($stmt) {
          $stmt->bind_param("isidi", $order_id, $item_name, $quantity, $price, $item_id);
          if ($stmt->execute()) {
           
            // Clear edit mode and form fields after update
            $edit_mode = false;
            $item_id = 0;
            $order_id = "";
            $item_name = "";
            $quantity = "";
            $price = "";
          } else {
            $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Error updating item: " . htmlspecialchars($stmt->error) . "</div>";
          }
          $stmt->close();
        } else {
          $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Prepare failed: " . htmlspecialchars($conn->error) . "</div>";
        }
      }
    }

    // Handle Delete action
    if (isset($_GET["delete"])) {
      $del_id = intval($_GET["delete"]);
      $stmt = $conn->prepare("DELETE FROM restaurantOrderItems WHERE item_id = ?");
      if ($stmt) {
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
         
          // If deleting the item currently being edited, reset form
          if ($edit_mode && $item_id === $del_id) {
            $edit_mode = false;
            $item_id = 0;
            $order_id = "";
            $item_name = "";
            $quantity = "";
            $price = "";
          }
        }
    }
    }
    // Handle Edit action - load item data into form
    if (isset($_GET["edit"])) {
      $edit_id = intval($_GET["edit"]);
      $stmt = $conn->prepare("SELECT item}_id, order_id, item_name, quantity, price FROM restaurantOrderItems WHERE item_id = ?");
      if ($stmt) {
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
          $row = $result->fetch_assoc();
          $item_id = $row["item_id"];
          $order_id = $row["order_id"];
          $item_name = $row["item_name"];
          $quantity = $row["quantity"];
          $price = $row["price"];
          $edit_mode = true;
        } else {
          $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Item not found for editing.</div>";
        }
        $stmt->close();
      } else {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Prepare failed: " . htmlspecialchars($conn->error) . "</div>";
      }
    }

    echo $message;
    ?>

    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
      <form method="POST" class="space-y-4" novalidate>
        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>" />
        <div>
          <label for="order_id" class="block font-semibold mb-1 text-gray-700">Order ID</label>
          <input
            type="number"
            id="order_id"
            name="order_id"
            required
            min="1"
            value="<?php echo htmlspecialchars($order_id); ?>"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="Enter order ID"
          />
        </div>
        <div>
          <label for="item_name" class="block font-semibold mb-1 text-gray-700">Item Name</label>
          <input
            type="text"
            id="item_name"
            name="item_name"
            required
            maxlength="100"
            value="<?php echo htmlspecialchars($item_name); ?>"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="Enter item name"
          />
        </div>
        <div>
          <label for="quantity" class="block font-semibold mb-1 text-gray-700">Quantity</label>
          <input
            type="number"
            id="quantity"
            name="quantity"
            required
            min="1"
            value="<?php echo htmlspecialchars($quantity); ?>"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="Enter quantity"
          />
        </div>
        <div>
          <label for="price" class="block font-semibold mb-1 text-gray-700">Price ($)</label>
          <input
            type="number"
            step="0.01"
            min="0"
            id="price"
            name="price"
            required
            value="<?php echo htmlspecialchars($price); ?>"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="Enter price"
          />
        </div>
        <div class="flex justify-end space-x-3">
          <?php if ($edit_mode): ?>
          <button
            type="submit"
            name="update"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded flex items-center space-x-2"
          >
            <i class="fas fa-save"></i><span>Update</span>
          </button>
          <a
            href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
            class="bg-gray-400 hover:bg-gray-500 text-white font-semibold px-5 py-2 rounded flex items-center space-x-2"
          >
            <i class="fas fa-times"></i><span>Cancel</span>
          </a>
          <?php else: ?>
          <button
            type="submit"
            name="add"
            class="bg-green-600 hover:bg-green-700 text-white font-semibold px-5 py-2 rounded flex items-center space-x-2"
          >
            <i class="fas fa-plus"></i><span>Add</span>
          </button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="mt-10 max-w-5xl mx-auto bg-white rounded shadow overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-indigo-600 text-white">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Item ID</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Order ID</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Item Name</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Quantity</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Price ($)</th>
            <th class="px-6 py-3 text-center text-sm font-semibold uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php
          $result = $conn->query("SELECT item_id, order_id, item_name, quantity, price FROM restaurantOrderItems ORDER BY item_id DESC");
          if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row["item_id"]) . "</td>";
              echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row["order_id"]) . "</td>";
              echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row["item_name"]) . "</td>";
              echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row["quantity"]) . "</td>";
              echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . number_format($row["price"], 2) . "</td>";
              echo "<td class='px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-3'>";
              echo "<a href='?edit=" . intval($row["item_id"]) . "' class='text-indigo-600 hover:text-indigo-900' title='Edit'><i class='fas fa-edit'></i></a>";
              echo "<a href='?delete=" . intval($row["item_id"]) . "' onclick='return confirm(\"Are you sure you want to delete this item?\");' class='text-red-600 hover:text-red-900' title='Delete'><i class='fas fa-trash-alt'></i></a>";
              echo "</td>";
              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='6' class='px-6 py-4 text-center text-gray-500'>No items found.</td></tr>";
          }
          $conn->close();
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <footer class="bg-indigo-600 text-white text-center py-4">
    <p class="text-sm">&copy; <?php echo date("Y"); ?> Restaurant Order Items Management</p>
  </footer>
</body>
</html>