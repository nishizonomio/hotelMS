<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';
require_once __DIR__ . '/../views/invoice.php';

$db = (new Database())->getConnection();
$controller = new InvoiceController($db);

if (!isset($_GET['id'])) {
    die("Invoice ID is required.");
}

$invoice_id = intval($_GET['id']);
$response = $controller->getInvoiceById($invoice_id);

if (!$response['success']) {
    die($response['message']);
}

$invoice = $response['data'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $updateResponse = $controller->updateInvoiceStatus($invoice_id, $status);

    if ($updateResponse['success']) {
        header("Location: ../views/invoice.php?msg=updated");
        exit;
    } else {
        echo "<p class='text-danger'>" . $updateResponse['message'] . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Invoice</title>
    <link rel="stylesheet" href="/assets/bootstrap-5.3.8-dist/css/bootstrap.min.css">
</head>

<body>

    <div class="modal fade" id="stats" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="staticBackdropLabel">Edit Invoice</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <select class="form-select" name="status" required>
                            <option value="unpaid" <?php if ($invoice['status'] == 'unpaid') echo 'selected'; ?>>Unpaid</option>
                            <option value="paid" <?php if ($invoice['status'] == 'paid') echo 'selected'; ?>>Paid</option>
                            <option value="cancelled" <?php if ($invoice['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                            <option value="refunded" <?php if ($invoice['status'] == 'refunded') echo 'selected'; ?>>Refunded</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Understood</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>