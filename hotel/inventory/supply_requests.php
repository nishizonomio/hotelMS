<?php
include '../db_connect.php';

session_start();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    $approved_by = trim($_POST['approved_by'] ?? '');

    if ($action === 'approve' && empty($approved_by)) {
        $_SESSION['error'] = 'Please provide your name for approval.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $status = $action === 'approve' ? 'Approved' : 'Rejected';

    $conn->begin_transaction();
    try {
        if ($action === 'approve') {
            // Get request details
            $getSql = "SELECT sr.item_id, sr.requested_qty, i.item_name, i.quantity_in_stock
                      FROM supply_requests sr
                      JOIN inventory i ON sr.item_id = i.item_id
                      WHERE sr.request_id = ?";
            $getStmt = $conn->prepare($getSql);
            $getStmt->bind_param("i", $request_id);
            $getStmt->execute();
            $result = $getStmt->get_result();
            $request = $result->fetch_assoc();
            $getStmt->close();

            if ($request && $request['quantity_in_stock'] >= $request['requested_qty']) {
                // Update inventory (reduce stock)
                $updateSql = "UPDATE inventory SET quantity_in_stock = quantity_in_stock - ? WHERE item_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ii", $request['requested_qty'], $request['item_id']);
                $updateStmt->execute();
                $updateStmt->close();

                // Update request status
                $updateReqSql = "UPDATE supply_requests SET status = ?, approved_by = ?, approved_date = NOW() WHERE request_id = ?";
                $reqStmt = $conn->prepare($updateReqSql);
                $reqStmt->bind_param("ssi", $status, $approved_by, $request_id);
                $reqStmt->execute();
                $reqStmt->close();

                $_SESSION['success'] = "Request approved and inventory updated.";
            } else {
                $_SESSION['error'] = "Insufficient stock to fulfill this request.";
                $conn->rollback();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            // Just update status for rejection
            $updateReqSql = "UPDATE supply_requests SET status = ?, approved_by = ?, approved_date = NOW() WHERE request_id = ?";
            $reqStmt = $conn->prepare($updateReqSql);
            $reqStmt->bind_param("ssi", $status, $approved_by, $request_id);
            $reqStmt->execute();
            $reqStmt->close();

            $_SESSION['success'] = "Request rejected.";
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Error processing request: ' . $e->getMessage();
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get all supply requests
$requests_sql = "
    SELECT sr.*, i.item_id, i.item_name, i.category, i.unit_price
    FROM supply_requests sr
    JOIN inventory i ON sr.item_id = i.item_id
    ORDER BY sr.request_date DESC
";
$requests_result = $conn->query($requests_sql);

// Get flash messages
$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supply Requests | Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body, html {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('/hotel/homepage/hotel_room.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            color: #fff;
        }

        .overlay {
            background-color: rgba(0,0,0,0.88);
            min-height: 100vh;
            padding: 40px 20px;
            box-sizing: border-box;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 32px;
            font-weight: 600;
        }

        p {
            text-align: center;
            margin-bottom: 30px;
            font-size: 16px;
            color: #ccc;
        }

        .alert {
            padding: 12px 18px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        .alert.success {
            background: rgba(40, 167, 69, 0.8);
            color: #fff;
        }
        .alert.error {
            background: rgba(220, 53, 69, 0.8);
            color: #fff;
        }

        table {
            margin: 20px auto;
            border-collapse: separate;
            border-spacing: 0;
            width: 95%;
            background: #23272f;
            color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 18px rgba(0,0,0,0.15);
            opacity: 0.95;
        }

        th, td {
            padding: 14px 12px;
            text-align: center;
            font-size: 15px;
            border: none;
        }

        th {
            background: #303642;
            font-weight: 700;
            font-size: 16px;
            color: #FF9800;
        }

        tr:hover td {
            background: #2e3440;
            transition: background 0.2s;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-Pending { background: #ffc107; color: #000; }
        .status-Approved { background: #28a745; color: #fff; }
        .status-Rejected { background: #dc3545; color: #fff; }
        .status-Fulfilled { background: #17a2b8; color: #fff; }

        .btn-approve, .btn-reject {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            margin: 0 2px;
        }
        .btn-approve { background: #28a745; color: #fff; }
        .btn-reject { background: #dc3545; color: #fff; }
        .btn-approve:hover { background: #218838; }
        .btn-reject:hover { background: #c82333; }

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
    </style>
</head>
<body>
<div class="overlay">
    <div class="container">
        <header>
            <h1>Supply Requests Management</h1>
            <p>Review and approve housekeeping supply requests.</p>
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

        <?php if ($requests_result && $requests_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Item ID</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Requested Qty</th>
                    <th>Unit Price</th>
                    <th>Total Value</th>
                    <th>Requested By</th>
                    <th>Staff ID</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($request = $requests_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $request['request_id']; ?></td>
                    <td><?php echo $request['item_id']; ?></td>
                    <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['category']); ?></td>
                    <td><?php echo $request['requested_qty']; ?></td>
                    <td>₱<?php echo number_format($request['unit_price'], 2); ?></td>
                    <td>₱<?php echo number_format($request['requested_qty'] * $request['unit_price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($request['requested_by']); ?></td>
                    <td><?php echo htmlspecialchars($request['staff_id'] ?? '-'); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?></td>
                    <td><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo $request['status']; ?></span></td>
                    <td>
                        <?php if ($request['status'] === 'Pending'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="text" name="approved_by" placeholder="Your name" required style="width: 80px; padding: 4px; margin-right: 4px;">
                            <button type="submit" class="btn-approve">Approve</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="text" name="approved_by" placeholder="Your name" required style="width: 80px; padding: 4px; margin-right: 4px;">
                            <button type="submit" class="btn-reject">Reject</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; margin-top: 50px; color: rgba(255,255,255,0.7);">
            No supply requests found.
        </p>
        <?php endif; ?>

        <a href="/hotel/homepage/index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Main Dashboard
        </a>
    </div>
</div>
</body>
</html>