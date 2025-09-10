<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Restaurant Billing </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-8 flex-grow">
        <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Restaurant Billing Management</h1>

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
            die("<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6'>Connection failed: " . $conn->connect_error . "</div>");
        }

        // Initialize variables for form
        $order_id = "";
        $guest_id = "";
        $staff_id = "";
        $table_number = "";
        $order_date = "";
        $order_time = "00:00";
        $total_amount = "";
        $payment_id = "";
        $edit_mode = false;
        $error_msg = "";
        $success_msg = "";

        // Handle form submission for Add or Edit
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Sanitize inputs
            $guest_id = isset($_POST['guest_id']) ? intval($_POST['guest_id']) : 0;
            $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            $table_number = isset($_POST['table_number']) ? trim($_POST['table_number']) : "";
            $order_date = isset($_POST['order_date']) ? $_POST['order_date'] : "";
            $order_time = isset($_POST['order_time']) ? $_POST['order_time'] : "00:00";
            $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
            $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;

            // Combine date and time for DATETIME field
            $order_datetime = $order_date . " " . $order_time . ":00";

            if (isset($_POST['order_id']) && $_POST['order_id'] !== "") {
                // Edit existing order
                $order_id = intval($_POST['order_id']);
                $stmt = $conn->prepare("UPDATE RestaurantBilling SET guest_id=?, staff_id=?, table_number=?, order_date=?, total_amount=?, payment_id=? WHERE order_id=?");
                $stmt->bind_param("isssdii", $guest_id, $staff_id, $table_number, $order_datetime, $total_amount, $payment_id, $order_id);
                if ($stmt->execute()) {
                 
                    // Reset form values after update
                    $order_id = "";
                    $guest_id = "";
                    $staff_id = "";
                    $table_number = "";
                    $order_date = "";
                    $order_time = "00:00";
                    $total_amount = "";
                    $payment_id = "";
                    $edit_mode = false;
                } else {
                    $error_msg = "Error updating order: " . $conn->error;
                }
                $stmt->close();
            } else {
                // Add new order
                $stmt = $conn->prepare("INSERT INTO RestaurantBilling (guest_id, staff_id, table_number, order_date, total_amount, payment_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssdi", $guest_id, $staff_id, $table_number, $order_datetime, $total_amount, $payment_id);
                if ($stmt->execute()) { 
                   
                    // Reset form values after insert
                    $order_id = "";
                    $guest_id = "";
                    $staff_id = "";
                    $table_number = "";
                    $order_date = "";
                    $order_time = "00:00";
                    $total_amount = "";
                    $payment_id = "";
                } else {
                    $error_msg = "Error adding order: " . $conn->error;
                }
                $stmt->close();
            }
        }

        // Handle delete operation
        if (isset($_GET['delete'])) {
            $del_id = intval($_GET['delete']);
            $stmt = $conn->prepare("DELETE FROM RestaurantBilling WHERE order_id=?");
            $stmt->bind_param("i", $del_id);
            if ($stmt->execute()) {
             
            } else {
                $error_msg = "Error deleting order: " . $conn->error;
            }
            $stmt->close();
        }

        // Handle edit mode - fetch data for the order to edit
        if (isset($_GET['edit'])) {
            $edit_id = intval($_GET['edit']);
            $stmt = $conn->prepare("SELECT * FROM RestaurantBilling WHERE order_id=?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $order_id = $row['order_id'];
                $guest_id = $row['guest_id'];
                $staff_id = $row['staff_id'];
                $table_number = $row['table_number'];
                $order_date_time = $row['order_date'];
                $dt = new DateTime($order_date_time);
                $order_date = $dt->format('Y-m-d');
                $order_time = $dt->format('H:i');
                $total_amount = $row['total_amount'];
                $payment_id = $row['payment_id'];
                $edit_mode = true;
            }
            $stmt->close();
        }

        // Display messages
        if ($error_msg) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6'>$error_msg</div>";
        }
        if ($success_msg) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6'>$success_msg</div>";
        }
        ?>

        <div class="bg-white rounded shadow p-6 mb-8 max-w-4xl mx-auto">
            <h2 class="text-xl font-semibold mb-4 text-gray-700"><?php echo $edit_mode ? "Edit Order" : "Add New Order"; ?></h2>
            <form method="POST" class="space-y-4">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>" />
                <?php endif; ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="guest_id" class="block text-gray-700 font-medium mb-1">Guest ID</label>
                        <input type="number" id="guest_id" name="guest_id" required min="1" value="<?php echo htmlspecialchars($guest_id); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="staff_id" class="block text-gray-700 font-medium mb-1">Staff ID</label>
                        <input type="number" id="staff_id" name="staff_id" required min="1" value="<?php echo htmlspecialchars($staff_id); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="table_number" class="block text-gray-700 font-medium mb-1">Table Number</label>
                        <input type="text" id="table_number" name="table_number" required maxlength="10" value="<?php echo htmlspecialchars($table_number); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="order_date" class="block text-gray-700 font-medium mb-1">Order Date</label>
                        <input type="date" id="order_date" name="order_date" required value="<?php echo htmlspecialchars($order_date); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="order_time" class="block text-gray-700 font-medium mb-1">Order Time</label>
                        <input type="time" id="order_time" name="order_time" required value="<?php echo htmlspecialchars($order_time); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="total_amount" class="block text-gray-700 font-medium mb-1">Total Amount ($)</label>
                        <input type="number" step="0.01" id="total_amount" name="total_amount" required min="0" value="<?php echo htmlspecialchars($total_amount); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="payment_id" class="block text-gray-700 font-medium mb-1">Payment ID</label>
                        <input type="number" id="payment_id" name="payment_id" required min="1" value="<?php echo htmlspecialchars($payment_id); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded transition flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i>
                        <?php echo $edit_mode ? "Update Order" : "Add Order"; ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="ml-4 inline-block text-gray-600 hover:text-gray-900 font-semibold">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto max-w-6xl mx-auto bg-white rounded shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-indigo-600 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Guest ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Staff ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Table Number</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Order Date & Time</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Total Amount ($)</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Payment ID</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php
                    $sql = "SELECT * FROM RestaurantBilling ORDER BY order_id DESC";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $dt = new DateTime($row['order_date']);
                            $order_date_time = $dt->format('Y-m-d H:i');
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($row['order_id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($row['guest_id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($row['staff_id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($row['table_number']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($order_date_time); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo number_format($row['total_amount'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($row['payment_id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                            <a href="?edit=<?php echo $row['order_id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit Order"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?php echo $row['order_id']; ?>" onclick="return confirm('Are you sure you want to delete this order?');" class="text-red-600 hover:text-red-900" title="Delete Order"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500 text-sm">No orders found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="bg-indigo-600 text-white text-center py-4">
        <p class="text-sm">&copy; <?php echo date("Y"); ?> Restaurant Billing System</p>
    </footer>
</body>
</html>