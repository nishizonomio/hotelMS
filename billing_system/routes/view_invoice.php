<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';

if (!isset($_GET['id'])) {
    die("Invoice ID is required.");
}

$db = (new Database())->getConnection();
$controller = new InvoiceController($db);

$invoice_id = intval($_GET['id']);
$response = $controller->getInvoiceById($invoice_id);

if (!$response['success']) {
    die("Invoice not found!");
}

$invoice = $response['data'];

// Fetch POS charges linked to this invoice
$stmt = $db->prepare("SELECT description, total_amount, source_module 
                      FROM pos_charges 
                      WHERE invoice_id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$pos_charges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Room charge = total - sum of pos charges
$pos_total = array_sum(array_column($pos_charges, 'total_amount'));
$room_charge = $invoice['total_amount'] - $pos_total;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo "INV" . str_pad($invoice['invoice_id'], 4, '0', STR_PAD_LEFT); ?></title>
    <link rel="stylesheet" href="../assets/bootstrap-5.3.8-dist/css/bootstrap.min.css">
</head>

<body class="container mt-5">

    <h2>Invoice Details</h2>
    <hr>

    <h5>Invoice ID: <?php echo "INV" . str_pad($invoice['invoice_id'], 4, '0', STR_PAD_LEFT); ?></h5>
    <p><strong>Guest:</strong> <?php echo htmlspecialchars($invoice['guest_name']); ?></p>
    <p><strong>Status:</strong>
        <span class="badge bg-<?php echo $invoice['status'] === 'paid' ? 'success' : 'warning'; ?>">
            <?php echo ucfirst($invoice['status']); ?>
        </span>
    </p>
    <p><strong>Date:</strong> <?php echo $invoice['invoice_date']; ?>
        <strong>Time:</strong> <?php echo $invoice['invoice_time']; ?>
    </p>

    <hr>
    <h4>Charges</h4>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Description</th>
                <th>Amount (₱)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Room Charge</td>
                <td><?php echo number_format($room_charge, 2); ?></td>
            </tr>
            <?php if (!empty($pos_charges)): ?>
                <?php foreach ($pos_charges as $pos): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pos['description']) . " (" . $pos['source_module'] . ")"; ?></td>
                        <td><?php echo number_format($pos['total_amount'], 2); ?></td>
                        <td><?php echo ucfirst($invoice['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-primary">
                <th>Total</th>
                <th><?php echo "₱" . number_format($invoice['total_amount'], 2); ?></th>
                <th><?php echo "₱" . number_format($invoice['total_amount'], 2); ?></th>
            </tr>
        </tfoot>
    </table>

    <a href="../views/invoice.php" class="btn btn-secondary">← Back</a>
</body>

</html>