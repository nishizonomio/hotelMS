<?php
include '../../db_connect.php';

session_start();

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (isset($_GET['get_all_items'])) {
        // Handle loading all inventory items for dropdown
        $sql = "SELECT item_name FROM inventory ORDER BY item_name";
        $result = $conn->query($sql);

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    if (isset($_GET['search'])) {
        // Handle search for inventory items
        $search = $_GET['search'];
        $sql = "SELECT item_name FROM inventory WHERE item_name LIKE ? ORDER BY item_name LIMIT 10";
        $stmt = $conn->prepare($sql);
        $searchParam = "%$search%";
        $stmt->bind_param("s", $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row['item_name'];
        }
        $stmt->close();

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    $sql = "
        SELECT item_id, item_name, quantity, added_at as created_at
        FROM hp_inventory
        ORDER BY item_name
    ";

    $result = $conn->query($sql);
    $data = [];

    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $data[] = $item;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Handle adding supplies to hp_inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supply'])) {
    $item_name = trim($_POST['supply_item_name']);
    $quantity = intval($_POST['supply_quantity']);

    if (!empty($item_name) && $quantity > 0) {
        // Check if item exists in main inventory and has sufficient stock
        $checkStockSql = "SELECT item_id, quantity_in_stock FROM inventory WHERE item_name = ?";
        $checkStockStmt = $conn->prepare($checkStockSql);
        $checkStockStmt->bind_param("s", $item_name);
        $checkStockStmt->execute();
        $stockResult = $checkStockStmt->get_result();

        if ($stockResult->num_rows > 0) {
            $stockRow = $stockResult->fetch_assoc();
            $item_id = $stockRow['item_id'];
            $current_stock = $stockRow['quantity_in_stock'];

            if ($current_stock >= $quantity) {
                // Deduct from main inventory
                $updateStockSql = "UPDATE inventory SET quantity_in_stock = quantity_in_stock - ?, used_qty = used_qty + ? WHERE item_id = ?";
                $updateStockStmt = $conn->prepare($updateStockSql);
                $updateStockStmt->bind_param("iii", $quantity, $quantity, $item_id);
                $updateStockStmt->execute();
                $updateStockStmt->close();

                // Insert into stock_usage
                $usageSql = "INSERT INTO stock_usage (item_id, used_qty, used_by) VALUES (?, ?, 'Housekeeping')";
                $usageStmt = $conn->prepare($usageSql);
                $usageStmt->bind_param("ii", $item_id, $quantity);
                $usageStmt->execute();
                $usageStmt->close();

                // Check if item exists in hp_inventory
                $checkHpSql = "SELECT item_id FROM hp_inventory WHERE item_name = ?";
                $checkHpStmt = $conn->prepare($checkHpSql);
                $checkHpStmt->bind_param("s", $item_name);
                $checkHpStmt->execute();
                $hpResult = $checkHpStmt->get_result();

                if ($hpResult->num_rows > 0) {
                    // Update quantity
                    $updateHpSql = "UPDATE hp_inventory SET quantity = quantity + ?, added_at = CURRENT_TIMESTAMP WHERE item_name = ?";
                    $updateHpStmt = $conn->prepare($updateHpSql);
                    $updateHpStmt->bind_param("is", $quantity, $item_name);
                    $updateHpStmt->execute();
                    $updateHpStmt->close();
                } else {
                    // Insert new item
                    $insertHpSql = "INSERT INTO hp_inventory (item_name, quantity) VALUES (?, ?)";
                    $insertHpStmt = $conn->prepare($insertHpSql);
                    $insertHpStmt->bind_param("si", $item_name, $quantity);
                    $insertHpStmt->execute();
                    $insertHpStmt->close();
                }
                $checkHpStmt->close();

                $_SESSION['success'] = 'Supply added successfully to housekeeping inventory.';
            } else {
                $_SESSION['error'] = 'Insufficient stock in main inventory. Available: ' . $current_stock;
            }
        } else {
            $_SESSION['error'] = 'Item not found in main inventory.';
        }
        $checkStockStmt->close();
    } else {
        $_SESSION['error'] = 'Please select an item and enter a valid quantity.';
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get flash messages
$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Housekeeping Inventory | Housekeeping</title>
    <link rel="stylesheet" href="/hotel/homepage/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px auto 0 auto;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .inventory-table th, .inventory-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .inventory-table th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600;
            color: #ffd700;
        }
        .inventory-table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
            transition: background 0.3s ease;
        }
        .section-title {
            font-size: 1.5rem;
            color: #ffd700;
            margin: 30px 0 15px 0;
            text-align: center;
        }
        .alert {
            padding: 12px 18px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        .alert.success {
            background: rgba(40, 167, 69, 0.8);
            color: #fff;
        }
        .alert.error {
            background: rgba(220, 53, 69, 0.8);
            color: #fff;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.25);
        }
        .request-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 18px;
            border-radius: 8px;
            background: #ffd700;
            color: #000;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .request-btn:hover {
            background: #ffea70;
            transform: translateY(-2px);
        }
        .table-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: rgba(255,255,255,0.15);
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .tab-btn:hover, .tab-btn.active {
            background: #ffd700;
            color: #000;
        }
        .table-section {
            display: block;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal {
            background: #1e1e1e;
            padding: 30px 25px;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 8px 30px rgba(0,0,0,0.5);
            color: #fff;
            text-align: left;
            position: relative;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #ffd700;
            text-align: center;
        }
        .modal label {
            display: block;
            margin: 10px 0 6px;
            font-weight: 600;
        }
        .modal select,
        .modal input,
        .modal textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #444;
            background: #2c2c2c;
            color: #f1f1f1;
            margin-bottom: 15px;
            font-family: 'Outfit', sans-serif;
        }
        .modal textarea {
            resize: vertical;
            min-height: 100px;
        }
        .modal .btn-submit {
            width: 100%;
            padding: 12px;
            background: #ffd700;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            color: #000;
        }
        .modal .btn-submit:hover {
            background: #e5c100;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #aaa;
            cursor: pointer;
        }
        .close-btn:hover {
            color: #fff;
        }
        /* Custom Searchable Dropdown */
        .custom-dropdown {
            position: relative;
            width: 100%;
        }
        .dropdown-header {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 8px;
            background: #2c2c2c;
            cursor: pointer;
            position: relative;
        }
        .dropdown-header input {
            flex: 1;
            border: none;
            background: transparent;
            color: #f1f1f1;
            font-family: 'Outfit', sans-serif;
            outline: none;
            margin: 0;
            padding: 0;
        }
        .dropdown-header input::placeholder {
            color: #aaa;
        }
        .dropdown-arrow {
            color: #f1f1f1;
            font-size: 12px;
            margin-left: 10px;
            transition: transform 0.3s;
        }
        .custom-dropdown.open .dropdown-arrow {
            transform: rotate(180deg);
        }
        .dropdown-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #2c2c2c;
            border: 1px solid #444;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .dropdown-option {
            padding: 10px;
            color: #f1f1f1;
            cursor: pointer;
            border-bottom: 1px solid #444;
        }
        .dropdown-option:last-child {
            border-bottom: none;
        }
        .dropdown-option:hover {
            background: #3c3c3c;
        }
        .dropdown-option.selected {
            background: #ffd700;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header>
                <h1><i class="fas fa-warehouse"></i> Housekeeping Inventory</h1>
                <p>View current housekeeping supplies stock.</p>
            </header>

            <?php if ($successMessage): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <!-- All Items Table -->
            <div id="other-table" class="table-section">
                <!-- Add Supply Button -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <button type="button" id="addSupplyBtn" style="padding: 12px 25px; background: #28a745; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 1rem;">
                        <i class="fas fa-plus"></i> Add Supply
                    </button>
                </div>
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody id="other-tbody">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
                <p id="other-no-data" style="text-align: center; margin-top: 20px; color: rgba(255,255,255,0.7); display: none;">
                    No items available.
                </p>
            </div>

            <a href="../housekeeping.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Housekeeping
            </a>
        </div>
    </div>

    <!-- Add Supply Modal -->
    <div id="addSupplyModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <button class="close-btn" id="closeAddSupplyModal">&times;</button>
            <h2><i class="fas fa-plus"></i> Add Supply to Housekeeping Inventory</h2>
            <form method="POST">
                <div>
                    <label for="supply_item_name">Select Item:</label>
                    <div class="custom-dropdown" id="itemDropdown">
                        <div class="dropdown-header" id="dropdownHeader">
                            <input type="text" id="dropdownSearch" placeholder="Search items..." autocomplete="off">
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <div class="dropdown-options" id="dropdownOptions" style="display: none;">
                            <!-- Options will be populated here -->
                        </div>
                    </div>
                    <input type="hidden" name="supply_item_name" id="supply_item_name" required>
                </div>
                <div>
                    <label for="supply_quantity">Quantity:</label>
                    <input type="number" name="supply_quantity" id="supply_quantity" min="1" required placeholder="Enter quantity">
                </div>
                <button type="submit" name="add_supply" class="btn-submit">
                    <i class="fas fa-plus"></i> Add Supply
                </button>
            </form>
        </div>
    </div>

    <script>
        function fetchData() {
            const tbody = document.getElementById('other-tbody');
            const noData = document.getElementById('other-no-data');

            // Show loading or clear previous data
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Loading...</td></tr>';

            fetch(`housekeeping_inventory.php?ajax=1`)
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = '';

                    if (data.length > 0) {
                        data.forEach(item => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${item.item_id}</td>
                                <td>${item.item_name}</td>
                                <td>${item.quantity}</td>
                                <td>${new Date(item.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                            `;
                            tbody.appendChild(row);
                        });
                        noData.style.display = 'none';
                    } else {
                        noData.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Error loading data</td></tr>';
                });
        }

        // Load items by default
        document.addEventListener('DOMContentLoaded', function() {
            fetchData();

            // Initialize dropdown functionality
            loadItemDropdown();

            // Initialize modal functionality
            initModal();

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        });

        // Modal functionality
        function initModal() {
            const addSupplyBtn = document.getElementById('addSupplyBtn');
            const modal = document.getElementById('addSupplyModal');
            const closeBtn = document.getElementById('closeAddSupplyModal');

            // Show modal
            addSupplyBtn.addEventListener('click', function() {
                modal.style.display = 'flex';
            });

            // Hide modal
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            // Hide modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Load items into custom searchable dropdown
        let allItems = [];
        let selectedItem = null;

        function loadItemDropdown() {
            const dropdownOptions = document.getElementById('dropdownOptions');
            const dropdownSearch = document.getElementById('dropdownSearch');
            const dropdownHeader = document.getElementById('dropdownHeader');
            const itemDropdown = document.getElementById('itemDropdown');

            fetch('housekeeping_inventory.php?ajax=1&get_all_items=1')
                .then(response => response.json())
                .then(data => {
                    allItems = data;

                    if (data.length > 0) {
                        renderDropdownOptions(data);
                        dropdownSearch.placeholder = "Search items...";
                    } else {
                        dropdownOptions.innerHTML = '<div class="dropdown-option">No items available</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading items:', error);
                    dropdownOptions.innerHTML = '<div class="dropdown-option">Error loading items</div>';
                });

            // Toggle dropdown
            dropdownHeader.addEventListener('click', function(e) {
                if (e.target !== dropdownSearch) {
                    toggleDropdown();
                }
            });

            // Search functionality
            dropdownSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const filteredItems = allItems.filter(item =>
                    item.item_name.toLowerCase().includes(searchTerm)
                );
                renderDropdownOptions(filteredItems);
                if (!dropdownOptions.style.display || dropdownOptions.style.display === 'none') {
                    dropdownOptions.style.display = 'block';
                    itemDropdown.classList.add('open');
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!itemDropdown.contains(e.target)) {
                    dropdownOptions.style.display = 'none';
                    itemDropdown.classList.remove('open');
                }
            });
        }

        function toggleDropdown() {
            const dropdownOptions = document.getElementById('dropdownOptions');
            const itemDropdown = document.getElementById('itemDropdown');

            if (dropdownOptions.style.display === 'none' || !dropdownOptions.style.display) {
                dropdownOptions.style.display = 'block';
                itemDropdown.classList.add('open');
                document.getElementById('dropdownSearch').focus();
            } else {
                dropdownOptions.style.display = 'none';
                itemDropdown.classList.remove('open');
            }
        }

        function renderDropdownOptions(items) {
            const dropdownOptions = document.getElementById('dropdownOptions');
            dropdownOptions.innerHTML = '';

            if (items.length > 0) {
                items.forEach(item => {
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'dropdown-option';
                    optionDiv.textContent = item.item_name;
                    optionDiv.dataset.value = item.item_name;

                    if (selectedItem && selectedItem.item_name === item.item_name) {
                        optionDiv.classList.add('selected');
                    }

                    optionDiv.addEventListener('click', function() {
                        selectItem(item);
                    });

                    dropdownOptions.appendChild(optionDiv);
                });
            } else {
                dropdownOptions.innerHTML = '<div class="dropdown-option">No items found</div>';
            }
        }

        function selectItem(item) {
            selectedItem = item;
            const dropdownSearch = document.getElementById('dropdownSearch');
            const hiddenInput = document.getElementById('supply_item_name');
            const dropdownOptions = document.getElementById('dropdownOptions');
            const itemDropdown = document.getElementById('itemDropdown');

            dropdownSearch.value = item.item_name;
            hiddenInput.value = item.item_name;

            dropdownOptions.style.display = 'none';
            itemDropdown.classList.remove('open');

            renderDropdownOptions(allItems);
        }
    </script>
</body>
</html>