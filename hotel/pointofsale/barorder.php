

<?php

// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db = "hotelpos";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
  die("<div class='text-red-600 font-semibold mb-4'>Connection failed: " . $conn->connect_error . "</div>");
}

// Initialize variables for form
$item_id = 0;
$bar_order_id = "";
$item_name = "";
$quantity = "";
$price = "";
$edit_mode = false;
$message = "";

// Handle Add and Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $bar_order_id = intval($_POST["bar_order_id"]);
  $item_name = trim($_POST["item_name"]);
  $quantity = intval($_POST["quantity"]);
  $price = floatval($_POST["price"]);

  if (isset($_POST["add"])) {
    // ADD
    $stmt = $conn->prepare("INSERT INTO BarOrderItems (bar_order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isid", $bar_order_id, $item_name, $quantity, $price);
    if ($stmt->execute()) {
      $message = "<div class='text-green-600 font-semibold mb-4'>Item added successfully.</div>";
    } else {
      $message = "<div class='text-red-600 font-semibold mb-4'>Error adding item: " . $stmt->error . "</div>";
    }
    $stmt->close();
  } elseif (isset($_POST["update"])) {
    // EDIT
    $item_id = intval($_POST["item_id"]);
    $stmt = $conn->prepare("UPDATE BarOrderItems SET bar_order_id=?, item_name=?, quantity=?, price=? WHERE item_id=?");
    $stmt->bind_param("isidi", $bar_order_id, $item_name, $quantity, $price, $item_id);
    if ($stmt->execute()) {
      $message = "<div class='text-green-600 font-semibold mb-4'>Item updated successfully.</div>";
    } else {
      $message = "<div class='text-red-600 font-semibold mb-4'>Error updating item: " . $stmt->error . "</div>";
    }
    $stmt->close();
  }
}

// Handle DELETE
if (isset($_GET["delete"])) {
  $del_id = intval($_GET["delete"]);
  $stmt = $conn->prepare("DELETE FROM BarOrderItems WHERE item_id=?");
  $stmt->bind_param("i", $del_id);
  if ($stmt->execute()) {
    $message = "<div class='text-green-600 font-semibold mb-4'>Item deleted successfully.</div>";
  } else {
    $message = "<div class='text-red-600 font-semibold mb-4'>Error deleting item: " . $stmt->error . "</div>";
  }
  $stmt->close();
}

// Handle EDIT (load data for editing)
if (isset($_GET["edit"])) {
  $edit_id = intval($_GET["edit"]);
  $stmt = $conn->prepare("SELECT * FROM BarOrderItems WHERE item_id=?");
  $stmt->bind_param("i", $edit_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $item_id = $row["item_id"];
    $bar_order_id = $row["bar_order_id"];
    $item_name = $row["item_name"];
    $quantity = $row["quantity"];
    $price = $row["price"];
    $edit_mode = true;
  }
  $stmt->close();
}

echo $message;
?>

<!-- FORM -->
<form method="POST" class="mb-8 bg-gray-50 p-6 rounded shadow-md max-w-3xl mx-auto">
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <div>
      <label for="bar_order_id" class="block font-semibold mb-1">Bar Order ID</label>
      <input type="number" name="bar_order_id" id="bar_order_id" required min="1" value="<?php echo htmlspecialchars($bar_order_id); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>
    <div>
      <label for="item_name" class="block font-semibold mb-1">Item Name</label>
      <input type="text" name="item_name" id="item_name" required maxlength="100" value="<?php echo htmlspecialchars($item_name); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>
    <div>
      <label for="quantity" class="block font-semibold mb-1">Quantity</label>
      <input type="number" name="quantity" id="quantity" required min="1" value="<?php echo htmlspecialchars($quantity); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>
    <div>
      <label for="price" class="block font-semibold mb-1">Price ($)</label>
      <input type="number" step="0.01" min="0" name="price" id="price" required value="<?php echo htmlspecialchars($price); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>
  </div>
  <?php if ($edit_mode): ?>
    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>" />
    <div class="flex justify-center space-x-4">
      <button type="submit" name="update" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-6 py-2 rounded flex items-center gap-2">
        <i class="fas fa-edit"></i> Update Item
      </button>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="bg-gray-400 hover:bg-gray-500 text-white font-semibold px-6 py-2 rounded flex items-center gap-2">
        <i class="fas fa-times"></i> Cancel
      </a>
    </div>
  <?php else: ?>
    <div class="flex justify-center">
      <button type="submit" name="add" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded flex items-center gap-2">
        <i class="fas fa-plus"></i> Add Item
      </button>
    </div>
  <?php endif; ?>
</form>

<!-- TABLE -->
<div class="overflow-x-auto">
  <table class="min-w-full bg-white rounded shadow">
    <thead class="bg-blue-600 text-white">
      <tr>
        <th class="py-3 px-6 text-left">Item ID</th>
        <th class="py-3 px-6 text-left">Bar Order ID</th>
        <th class="py-3 px-6 text-left">Item Name</th>
        <th class="py-3 px-6 text-left">Quantity</th>
        <th class="py-3 px-6 text-left">Price</th>
        <th class="py-3 px-6 text-center">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM BarOrderItems ORDER BY item_id DESC");
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          echo "<tr class='border-b hover:bg-gray-50'>";
          echo "<td class='py-3 px-6'>" . $row["item_id"] . "</td>";
          echo "<td class='py-3 px-6'>" . $row["bar_order_id"] . "</td>";
          echo "<td class='py-3 px-6'>" . htmlspecialchars($row["item_name"]) . "</td>";
          echo "<td class='pyg-3 px-6'>" . $row["quantity"] . "</td>";
          echo "<td class='py-3 px-6'>" . number_format($row["price"], 2) . "</td>";
          echo "<td class='py-3 px-6 text-center space-x-2'>";
          echo "<a href='" . $_SERVER['PHP_SELF'] . "?edit=" . $row["item_id"] . "' class='text-yellow-500 hover:text-yellow-700'><i class='fas fa-edit'></i> Edit</a>";
          echo "<a href='" . $_SERVER['PHP_SELF'] . "?delete=" . $row["item_id"] . "' class='text-red-600 hover:text-red-800' onclick='return confirm(\"Are you sure you want to delete this item?\");'><i class='fas fa-trash-alt'></i> Delete</a>";
          echo "</td>";                 
            echo "</tr>";
        }
        } else {    
          echo "<tr><td colspan='6' class='text-center py-4'>No items found.</td></tr>";
        }                                       

        $conn->close();
      ?>                    


    </tbody>                
    </table>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">                              
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>           
</body>     
</html>