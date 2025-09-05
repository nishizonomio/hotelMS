<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sales Management System - CRUD</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
  />
  <style>
    .table-container::-webkit-scrollbar {
      height: 8px;
    }
    .table-container::-webkit-scrollbar-thumb {
      background-color: #a78bfa;
      border-radius: 4px;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

  <header class="bg-purple-700 text-white p-4 shadow-md">
    <h1 class="text-2xl font-semibold text-center">Sales Management System</h1>
  </header>

  <main class="flex-grow container mx-auto p-4 max-w-7xl">
    <?php
      // Connect to database
      $servername = "localhost";
      $username = "root";
      $password = "";
      $dbname = "hotelpos";

      $conn = new mysqli($servername, $username, $password, $dbname);
      if ($conn->connect_error) {
        die("<div class='text-center text-red-600 p-4'>Connection failed: " . $conn->connect_error . "</div>");
      }

      // Initialize variables for form fields
      $sale_id = "";
      $guest_id = "";
      $staff_id = "";
      $sale_date = "";
      $total_amount = "";
      $payment_id = "";
      $edit_mode = false;
      $error_message = "";

      // Handle form submission for Add or Edit
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $sale_id_post = $conn->real_escape_string($_POST['sale_id']);
        $guest_id_post = $conn->real_escape_string($_POST['guest_id']);
        $staff_id_post = $conn->real_escape_string($_POST['staff_id']);
        $sale_date_post = $conn->real_escape_string($_POST['sale_date']);
        $total_amount_post = floatval($_POST['total_amount']);
        $payment_id_post = $conn->real_escape_string($_POST['payment_id']);

        if (isset($_POST['edit']) && $_POST['edit'] === "true") {
          // Update existing sale
          $update_sql = "UPDATE giftshopsales SET guest_id='$guest_id_post', staff_id='$staff_id_post', sale_date='$sale_date_post', total_amount=$total_amount_post, payment_id='$payment_id_post' WHERE sale_id='$sale_id_post'";
          if ($conn->query($update_sql)) {
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit();
          } else {
            $error_message = "Error updating sale: " . $conn->error;
          }
        } else {
          // Insert new sale
          // Check if sale_id already exists
          $check_sql = "SELECT sale_id FROM giftshopsales WHERE sale_id='$sale_id_post'";
          $check_result = $conn->query($check_sql);
          if ($check_result && $check_result->num_rows > 0) {
            $error_message = "Sale ID already exists. Please use a unique Sale ID.";
          } else {
            $insert_sql = "INSERT INTO giftshopsales (sale_id, guest_id, staff_id, sale_date, total_amount, payment_id) VALUES ('$sale_id_post', '$guest_id_post', '$staff_id_post', '$sale_date_post', $total_amount_post, '$payment_id_post')";
            if ($conn->query($insert_sql)) {
              header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
              exit();
            } else {
              $error_message = "Error adding sale: " . $conn->error;
            }
          }
        }
      }

      // Handle delete request
      if (isset($_GET['delete'])) {
        $del_id = $conn->real_escape_string($_GET['delete']);
        $conn->query("DELETE FROM giftshopsales WHERE sale_id='$del_id'");
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
      }

      // Handle edit request - prefill form
      if (isset($_GET['edit'])) {
        $edit_id = $conn->real_escape_string($_GET['edit']);
        $edit_sql = "SELECT * FROM giftshopsales WHERE sale_id='$edit_id' LIMIT 1";
        $edit_result = $conn->query($edit_sql);
        if ($edit_result && $edit_result->num_rows === 1) {
          $edit_mode = true;
          $row = $edit_result->fetch_assoc();
          $sale_id = htmlspecialchars($row['sale_id']);
          $guest_id = htmlspecialchars($row['guest_id']);
          $staff_id = htmlspecialchars($row['staff_id']);
          $sale_date = htmlspecialchars($row['sale_date']);
          $total_amount = htmlspecialchars($row['total_amount']);
          $payment_id = htmlspecialchars($row['payment_id']);
        } else {
          // If sale_id not found, redirect to main page
          header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
          exit();
        }
      }
    ?>

    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-4 text-purple-800"><?= $edit_mode ? "Edit Sale" : "Add New Sale" ?></h2>
      <?php if ($error_message): ?>
        <div class="max-w-3xl mx-auto mb-4 p-3 bg-red-100 text-red-700 rounded"><?= $error_message ?></div>
      <?php endif; ?>
      <form id="saleForm" class="bg-white p-6 rounded-lg shadow-md max-w-3xl mx-auto space-y-6" method="POST" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="sale_id" class="block text-gray-700 font-medium mb-1">Sale ID</label>
            <input
              type="text"
              id="sale_id"
              name="sale_id"
              required
              <?= $edit_mode ? "readonly" : "" ?>
              class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white <?= $edit_mode ? "cursor-not-allowed" : "" ?>"
              placeholder="Enter unique sale ID"
              value="<?= $sale_id ?>"
            />
          </div>

          <div>
            <label for="guest_id" class="block text-gray-700 font-medium mb-1">Guest ID</label>
            <input
              type="text"
              id="guest_id"
              name="guest_id"
              required
              class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
              placeholder="Enter guest ID"
              value="<?= $guest_id ?>"
            />
          </div>

          <div>
            <label for="staff_id" class="block text-gray-700 font-medium mb-1">Staff ID</label>
            <input
              type="text"
              id="staff_id"
              name="staff_id"
              required
              class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
              placeholder="Enter staff ID"
              value="<?= $staff_id ?>"
            />
          </div>

          <div>
            <label for="sale_date" class="block text-gray-700 font-medium mb-1">Sale Date</label>
            <input
              type="date"
              id="sale_date"
              name="sale_date"
              required
              class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
              value="<?= $sale_date ?>"
            />
          </div>

          <div>
            <label for="total_amount" class="block text-gray-700 font-medium mb-1">Total Amount ($)</label>
            <input
              type="number"
              step="0.01"
              min="0"
              id="total_amount"
              name="total_amount"
              required
              class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
              placeholder="Enter total amount"
              value="<?= $total_amount ?>"
            />
          </div>

          <div>
            <label for="payment_id" class="block text-gray-700 font-medium mb-1">Payment ID</label>
            <input
              type="text"
              id="payment_id"
              name="payment_id"
              required
              class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
              placeholder="Enter payment ID"
              value="<?= $payment_id ?>"
            />
          </div>
        </div>

        <input type="hidden" name="edit" value="<?= $edit_mode ? "true" : "false" ?>" />

        <div class="text-center">
          <button
            type="submit"
            class="bg-purple-700 hover:bg-purple-800 text-white font-semibold py-2 px-6 rounded-md transition"
          >
            <?= $edit_mode ? "Update Sale" : "Add Sale" ?>
          </button>
          <?php if ($edit_mode): ?>
            <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="ml-4 inline-block text-purple-700 hover:underline font-semibold">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section>
      <h2 class="text-xl font-semibold mb-4 text-purple-800">Sales Records</h2>
      <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="min-w-full divide-y divide-gray-200 table-auto">
          <thead class="bg-purple-700 text-white">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold">Sale ID</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Guest ID</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Staff ID</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Sale Date</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Total Amount ($)</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Payment ID</th>
              <th class="px-4 py-3 text-center text-sm font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody id="salesTableBody" class="divide-y divide-gray-200 bg-white">
            <?php
              $sql = "SELECT * FROM giftshopsales ORDER BY sale_date DESC, sale_id DESC";
              $result = $conn->query($sql);

              if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  echo "<tr class='hover:bg-purple-50'>";
                  echo "<td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['sale_id']) . "</td>";
                  echo "<td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['guest_id']) . "</td>";
                  echo "<td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['staff_id']) . "</td>";
                  echo "<td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['sale_date']) . "</td>";
                  echo "<td class='px-4 py-3 text-sm'>$" . number_format($row['total_amount'], 2) . "</td>";
                  echo "<td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['payment_id']) . "</td>";
                  echo "<td class='px-4 py-3 text-center text-sm space-x-3'>";
                  echo "<a href='?edit=" . urlencode($row['sale_id']) . "' class='text-blue-600 hover:text-blue-800' title='Edit Sale'><i class='fas fa-edit'></i></a>";
                  echo "<a href='?delete=" . urlencode($row['sale_id']) . "' onclick='return confirm(\"Are you sure you want to delete this sale?\");' class='text-red-600 hover:text-red-800' title='Delete Sale'><i class='fas fa-trash-alt'></i></a>";
                  echo "</td>";
                  echo "</tr>";
                }
              } else {
                echo "<tr><td colspan='7' class='text-center text-gray-500 p-4'>No sales records found.</td></tr>";
              }

              $conn->close();
            ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <footer class="bg-purple-700 text-white text-center p-4 mt-8">
    <p>© 2024 Sales Management System. All rights reserved.</p>
  </footer>

</body>
</html>