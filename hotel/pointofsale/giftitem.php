<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
  />
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap"
    rel="stylesheet"
  />
  <style>
    body {
      font-family: 'Roboto', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center p-4">
<?php
// Database connection parameters - adjust as needed
$host = 'localhost';
$db   = 'hotelpos';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
  echo '<div class="text-red-600 font-bold p-4">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
  exit;
}

// Handle POST requests for Add, Edit, Delete
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // Sanitize inputs
  $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
  $sale_id = isset($_POST['sale_id']) ? (int)$_POST['sale_id'] : 0;
  $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
  $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
  $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;

  if ($action === 'add') {
    // Validate inputs
    if ($item_id <= 0) $errors[] = "Item ID must be a positive integer.";
    if ($sale_id <= 0) $errors[] = "Sale ID must be a positive integer.";
    if ($item_name === '') $errors[] = "Item Name cannot be empty.";
    if ($quantity < 0) $errors[] = "Quantity cannot be negative.";
    if ($price < 0) $errors[] = "Price cannot be negative.";

    // Check if item_id already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM giftshopitems WHERE item_id = ?");
    $stmt->execute([$item_id]);
    if ($stmt->fetchColumn() > 0) {
      $errors[] = "Item ID already exists.";
    }

    if (empty($errors)) {
      $stmt = $pdo->prepare("INSERT INTO giftshopitems (item_id, sale_id, item_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$item_id, $sale_id, $item_name, $quantity, $price]);
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }
  } elseif ($action === 'edit') {
    // Validate inputs
    if ($item_id <= 0) $errors[] = "Invalid Item ID.";
    if ($sale_id <= 0) $errors[] = "Sale ID must be a positive integer.";
    if ($item_name === '') $errors[] = "Item Name cannot be empty.";
    if ($quantity < 0) $errors[] = "Quantity cannot be negative.";
    if ($price < 0) $errors[] = "Price cannot be negative.";

    if (empty($errors)) {
      $stmt = $pdo->prepare("UPDATE giftshopitems SET sale_id = ?, item_name = ?, quantity = ?, price = ? WHERE item_id = ?");
      $stmt->execute([$sale_id, $item_name, $quantity, $price, $item_id]);
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }
  } elseif ($action === 'delete') {
    if ($item_id > 0) {
      $stmt = $pdo->prepare("DELETE FROM giftshopitems WHERE item_id = ?");
      $stmt->execute([$item_id]);
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }
  }
}

// Fetch all items
$stmt = $pdo->query("SELECT item_id, sale_id, item_name, quantity, price FROM giftshopitems ORDER BY item_id ASC");
$items = $stmt->fetchAll();
?>

  <div class="w-full max-w-6xl bg-white rounded-lg shadow-lg p-6 mb-8">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Gift Shop Items</h1>

    <?php if (!empty($errors)) : ?>
      <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        <ul class="list-disc list-inside">
          <?php foreach ($errors as $error) : ?>
            <li><?=htmlspecialchars($error)?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="flex justify-end mb-4">
      <button
        id="addItemBtn"
        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
      >
        <i class="fas fa-plus mr-2"></i> Add Item
      </button>
    </div>

    <div class="overflow-x-auto">
      <table
        id="itemsTable"
        class="min-w-full divide-y divide-gray-200 border border-gray-300 rounded-lg"
      >
        <thead class="bg-gray-100">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Item ID</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Sale ID</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Item Name</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Quantity</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Price ($)</th>
            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200" id="itemsTbody">
          <?php foreach ($items as $item) : ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?=htmlspecialchars($item['item_id'])?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?=htmlspecialchars($item['sale_id'])?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?=htmlspecialchars($item['item_name'])?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?=htmlspecialchars($item['quantity'])?></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?=number_format($item['price'], 2)?></td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                <button
                  class="text-blue-600 hover:text-blue-900 focus:outline-none editBtn"
                  aria-label="Edit item <?=htmlspecialchars($item['item_id'])?>"
                  data-item_id="<?=htmlspecialchars($item['item_id'])?>"
                  data-sale_id="<?=htmlspecialchars($item['sale_id'])?>"
                  data-item_name="<?=htmlspecialchars($item['item_name'])?>"
                  data-quantity="<?=htmlspecialchars($item['quantity'])?>"
                  data-price="<?=htmlspecialchars($item['price'])?>"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete item #<?=htmlspecialchars($item['item_id'])?>?');">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="item_id" value="<?=htmlspecialchars($item['item_id'])?>" />
                  <button
                    type="submit"
                    class="text-red-600 hover:text-red-900 focus:outline-none"
                    aria-label="Delete item <?=htmlspecialchars($item['item_id'])?>"
                  >
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($items) === 0) : ?>
            <tr>
              <td colspan="6" class="px-6 py-4 text-center text-gray-500">No items found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal backdrop -->
  <div
    id="modalBackdrop"
    class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
  >
    <!-- Modal -->
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
      <h2 id="modalTitle" class="text-2xl font-bold mb-4 text-gray-800">Add Item</h2>
      <form id="itemForm" method="post" class="space-y-4">
        <input type="hidden" name="action" id="formAction" value="add" />
        <div>
          <label for="itemId" class="block text-sm font-medium text-gray-700">Item ID</label>
          <input
            type="number"
            id="itemId"
            name="item_id"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50"
            required
            min="1"
          />
        </div>
        <div>
          <label for="saleId" class="block text-sm font-medium text-gray-700">Sale ID</label>
          <input
            type="number"
            id="saleId"
            name="sale_id"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50"
            required
            min="1"
          />
        </div>
        <div>
          <label for="itemName" class="block text-sm font-medium text-gray-700">Item Name</label>
          <input
            type="text"
            id="itemName"
            name="item_name"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50"
            required
            maxlength="100"
          />
        </div>
        <div>
          <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
          <input
            type="number"
            id="quantity"
            name="quantity"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50"
            required
            min="0"
          />
        </div>
        <div>
          <label for="price" class="block text-sm font-medium text-gray-700">Price ($)</label>
          <input
            type="number"
            step="0.01"
            id="price"
            name="price"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50"
            required
            min="0"
          />
        </div>
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
          <button
            type="button"
            id="cancelBtn"
            class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400"
          >
            Cancel
          </button>
          <button
            type="submit"
            id="saveBtn"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            Save
          </button>
        </div>
      </form>
      <button
        id="modalCloseBtn"
        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 focus:outline-none"
        aria-label="Close modal"
      >
        <i class="fas fa-times fa-lg"></i>
      </button>
    </div>
  </div>

  <script>
    const modalBackdrop = document.getElementById("modalBackdrop");
    const modalTitle = document.getElementById("modalTitle");
    const itemForm = document.getElementById("itemForm");
    const addItemBtn = document.getElementById("addItemBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    const modalCloseBtn = document.getElementById("modalCloseBtn");
    const formActionInput = document.getElementById("formAction");

    const itemIdInput = document.getElementById("itemId");
    const saleIdInput = document.getElementById("saleId");
    const itemNameInput = document.getElementById("itemName");
    const quantityInput = document.getElementById("quantity");
    const priceInput = document.getElementById("price");

    // Open modal for add or edit
    function openModal(isEdit = false, data = null) {
      modalBackdrop.classList.remove("hidden");
      if (isEdit && data) {
        modalTitle.textContent = `Edit Item #${data.item_id}`;
        formActionInput.value = "edit";
        itemIdInput.value = data.item_id;
        itemIdInput.readOnly = true;
        saleIdInput.value = data.sale_id;
        itemNameInput.value = data.item_name;
        quantityInput.value = data.quantity;
        priceInput.value = parseFloat(data.price).toFixed(2);
      } else {
        modalTitle.textContent = "Add Item";
        formActionInput.value = "add";
        itemForm.reset();
        itemIdInput.readOnly = false;
      }
      itemIdInput.focus();
    }

    // Close modal
    function closeModal() {
      modalBackdrop.classList.add("hidden");
      itemForm.reset();
      itemIdInput.readOnly = false;
    }

    // Handle add button click
    addItemBtn.addEventListener("click", () => openModal(false));

    // Handle cancel and close modal buttons
    cancelBtn.addEventListener("click", closeModal);
    modalCloseBtn.addEventListener("click", closeModal);

    // Handle clicks on edit buttons
    document.querySelectorAll(".editBtn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const data = {
          item_id: btn.getAttribute("data-item_id"),
          sale_id: btn.getAttribute("data-sale_id"),
          item_name: btn.getAttribute("data-item_name"),
          quantity: btn.getAttribute("data-quantity"),
          price: btn.getAttribute("data-price"),
        };
        openModal(true, data);
      });
    });

    // Close modal on backdrop click (but not on modal content)
    modalBackdrop.addEventListener("click", (e) => {
      if (e.target === modalBackdrop) {
        closeModal();
      }
    });
  </script>
</body>
</html>