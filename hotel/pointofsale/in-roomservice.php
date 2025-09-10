<html>
<head>
  <title>Room Service Items CRUD</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"></link>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
  <div class="container mx-auto px-4 py-8 flex-grow">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Room Service Items Management</h1>

    <?php
    // Database connection
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "hotelpos";

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
      die("<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Connection failed: " . $conn->connect_error . "</div>");
    }

    // Initialize variables
    $item_id = 0;
    $service_id = "";
    $item_name = "";
    $quantity = "";
    $price = "";
    $update = false;
    $message = "";

    // Handle Create
    if (isset($_POST['save'])) {
      $service_id = $conn->real_escape_string($_POST['service_id']);
      $item_name = $conn->real_escape_string($_POST['item_name']);
      $quantity = $conn->real_escape_string($_POST['quantity']);
      $price = $conn->real_escape_string($_POST['price']);

      $sql = "INSERT INTO RoomServiceItems (service_id, item_name, quantity, price) VALUES ('$service_id', '$item_name', '$quantity', '$price')";
      if ($conn->query($sql) === TRUE) {
        $message = "<div class='bg-green-100 text-green-700 p-4 rounded mb-6'>New item added successfully.</div>";
      } else {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Error: " . $conn->error . "</div>";
      }
    }

    // Handle Delete
    if (isset($_GET['delete'])) {
      $item_id = intval($_GET['delete']);
      $sql = "DELETE FROM RoomServiceItems WHERE item_id=$item_id";
      if ($conn->query($sql) === TRUE) {
        $message = "<div class='bg-green-100 text-green-700 p-4 rounded mb-6'>Item deleted successfully.</div>";
      } else {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Error deleting item: " . $conn->error . "</div>";
      }
    }

    // Handle Edit - fetch data for update
    if (isset($_GET['edit'])) {
      $item_id = intval($_GET['edit']);
      $result = $conn->query("SELECT * FROM RoomServiceItems WHERE item_id=$item_id");
      if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $service_id = $row['service_id'];
        $item_name = $row['item_name'];
        $quantity = $row['quantity'];
        $price = $row['price'];
        $update = true;
      }
    }

    // Handle Update
    if (isset($_POST['update'])) {
      $item_id = intval($_POST['item_id']);
      $service_id = $conn->real_escape_string($_POST['service_id']);
      $item_name = $conn->real_escape_string($_POST['item_name']);
      $quantity = $conn->real_escape_string($_POST['quantity']);
      $price = $conn->real_escape_string($_POST['price']);

      $sql = "UPDATE RoomServiceItems SET service_id='$service_id', item_name='$item_name', quantity='$quantity', price='$price' WHERE item_id=$item_id";
      if ($conn->query($sql) === TRUE) {
        $message = "<div class='bg-green-100 text-green-700 p-4 rounded mb-6'>Item updated successfully.</div>";
        $update = false;
        $service_id = "";
        $item_name = "";
        $quantity = "";
        $price = "";
      } else {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Error updating item: " . $conn->error . "</div>";
      }
    }

    echo $message;

    // Fetch all items
    $items = $conn->query("SELECT * FROM RoomServiceItems ORDER BY item_id DESC");
    ?>

    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
      <form method="POST" class="space-y-4">
        <?php if ($update): ?>
          <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
        <?php endif; ?>
        <div>
          <label for="service_id" class="block text-gray-700 font-semibold mb-1">Service ID</label>
          <input type="number" name="service_id" id="service_id" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo htmlspecialchars($service_id); ?>">
        </div>
        <div>
          <label for="item_name" class="block text-gray-700 font-semibold mb-1">Item Name</label>
          <input type="text" name="item_name" id="item_name" required maxlength="100" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo htmlspecialchars($item_name); ?>">
        </div>
        <div>
          <label for="quantity" class="block text-gray-700 font-semibold mb-1">Quantity</label>
          <input type="number" name="quantity" id="quantity" required min="1" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo htmlspecialchars($quantity); ?>">
        </div>
        <div>
          <label for="price" class="block text-gray-700 font-semibold mb-1">Price ($)</label>
          <input type="number" step="0.01" min="0" name="price" id="price" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo htmlspecialchars($price); ?>">
        </div>
        <div class="flex justify-end space-x-2">
          <?php if ($update): ?>
            <button type="submit" name="update" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition flex items-center"><i class="fas fa-save mr-2"></i> Update</button>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500 transition flex items-center"><i class="fas fa-times mr-2"></i> Cancel</a>
          <?php else: ?>
            <button type="submit" name="save" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition flex items-center"><i class="fas fa-plus mr-2"></i> Add Item</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="mt-10 max-w-5xl mx-auto bg-white p-6 rounded shadow overflow-x-auto">
      <table class="min-w-full table-auto border-collapse border border-gray-300">
        <thead>
          <tr class="bg-indigo-600 text-white">
            <th class="border border-gray-300 px-4 py-2 text-left">Item ID</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Service ID</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Item Name</th>
            <th class="border border-gray-300 px-4 py-2 text-right">Quantity</th>
            <th class="border border-gray-300 px-4 py-2 text-right">Price ($)</th>
            <th class="border border-gray-300 px-4 py-2 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($items->num_rows > 0): ?>
            <?php while($row = $items->fetch_assoc()): ?>
              <tr class="hover:bg-gray-100">
                <td class="border border-gray-300 px-4 py-2"><?php echo $row['item_id']; ?></td>
                <td class="border border-gray-300 px-4 py-2"><?php echo $row['service_id']; ?></td>
                <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($row['item_name']); ?></td>
                <td class="border border-gray-300 px-4 py-2 text-right"><?php echo $row['quantity']; ?></td>
                <td class="border border-gray-300 px-4 py-2 text-right"><?php echo number_format($row['price'], 2); ?></td>
                <td class="border border-gray-300 px-4 py-2 text-center space-x-2">
                  <a href="?edit=<?php echo $row['item_id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit"><i class="fas fa-edit"></i></a>
                  <a href="?delete=<?php echo $row['item_id']; ?>" onclick="return confirm('Are you sure you want to delete this item?');" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash-alt"></i></a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-4 text-gray-500">No room service items found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <footer class="bg-indigo-600 text-white text-center py-4">
    &copy; <?php echo date("Y"); ?> Hotel Room Service Management
  </footer>
</body>
</html>