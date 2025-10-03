<?php
include '../../db_connect.php';

session_start();

// Fetch all items from hp_inventory with inventory item_id
$sql = "SELECT h.*, i.item_id as inventory_item_id FROM hp_inventory h LEFT JOIN inventory i ON h.item_name = i.item_name ORDER BY h.item_name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HP Inventory | Housekeeping</title>
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
            text-align: left;
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
        .no-data {
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,0.7);
        }
        .overlay {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 100vh !important;
        }
        .container {
            width: 100% !important;
            max-width: 1200px !important;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header>
                <h1><i class="fas fa-warehouse"></i> HP Inventory</h1>
                <p>View current HP inventory items.</p>
            </header>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Inventory Item ID</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Added At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['item_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['inventory_item_id'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($row['added_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-data">No items in HP inventory.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <a href="../housekeeping.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Housekeeping
            </a>
        </div>
    </div>
</body>
</html>