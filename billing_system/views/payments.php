<?php
require_once __DIR__ . '/../controllers/InvoiceController.php';
require_once __DIR__ . '/../controllers/PaymentController.php';

$invoiceController = new InvoiceController();
$response = $invoiceController->getInvoicesByStatus('unpaid');

$unpaidInvoices = [];
if (is_array($response) && isset($response['success']) && $response['success'] && isset($response['data'])) {
    $unpaidInvoices = $response['data'];
} elseif (is_array($response) && isset($response['data'])) {
    $unpaidInvoices = $response['data']; // fallback
} elseif (is_array($response)) {
    // if controller returned plain array of rows already
    $unpaidInvoices = $response;
}




$db = new Database();
$conn = $db->getConnection();
$paymentController = new PaymentController($conn);
$recentPayments = $paymentController->getRecentPayments();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <!-- <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous"
    />
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
      crossorigin="anonymous"
    ></script> -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link
        rel="stylesheet"
        href="../assets/bootstrap-5.3.8-dist/css/bootstrap.min.css" />
    <script src="../assets/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/payment.css" />
</head>

<body>
    <aside class="sidebar">
        <h2>Billing System</h2>
        <nav>
            <ul>
                <li>
                    <a href="billing.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
                </li>
                <li>
                    <a href="invoice.php"><i class="fa-solid fa-file-lines"></i> Invoice</a>
                </li>
                <li>
                    <a href="payments.php" class="active"><i class="fa-solid fa-wallet"></i> Payment</a>
                </li>
                <li>
                    <a href="refund.php"><i class="fa-solid fa-rotate-left"></i> Refund</a>
                </li>
                <li>
                    <a href="groupBilling.php"><i class="fa-solid fa-user-group"></i> Group Billing</a>
                </li>
                <li>
                    <a href="folio.php"><i class="fa-solid fa-folder"></i> Folio Management</a>
                </li>
                <li>
                    <a href="../homepage/index.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </li>
            </ul>
        </nav>
    </aside>
    <main class="content">
        <header>
            <h2>Payment</h2>
            <div>
                <h5>Welcome Admin!</h5>
                <button id="darkModeToggle" class="btn btn-outline-dark">
                    <i id="darkIcon" class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="container text-center">
            <div class="head">
                <h3>Payment</h3>
                <button
                    type="button"
                    class="btn btn-success"
                    data-bs-toggle="modal"
                    data-bs-target="#staticBackdrop">
                    <i class="fa-solid fa-rotate-right"></i> Process Payment
                </button>
                <div
                    class="modal fade"
                    id="staticBackdrop"
                    data-bs-backdrop="static"
                    data-bs-keyboard="false"
                    tabindex="-1"
                    aria-labelledby="staticBackdropLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <form action="../routes/store_payment.php" method="POST">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                        Payment
                                    </h1>
                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col">
                                            <div class="form-floating mb-3">
                                                <select class="form-select" name="invoice_id" required>
                                                    <option value="" selected disabled>Select Unpaid Invoice</option>
                                                    <?php foreach ($unpaidInvoices as $inv): ?>
                                                        <option value="<?= $inv['invoice_id'] ?>">
                                                            <?= "INV" . str_pad($inv['invoice_id'], 4, '0', STR_PAD_LEFT) ?>
                                                            - <?= htmlspecialchars($inv['guest_name']) ?>
                                                            (₱<?= number_format($inv['total_amount'], 2) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="floatingSelect">Select Invoice</label>
                                                <div
                                                    id="createInvoiceSpinner"
                                                    class="text-center mt-3"
                                                    style="display: none">
                                                    <div
                                                        class="spinner-border text-primary"
                                                        role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-floating mb-3">
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    id="floatingInput"
                                                    name="amount_paid"
                                                    placeholder="Amount" />
                                                <label for="floatingInput">Amount</label>
                                                <div
                                                    id="createInvoiceSpinner"
                                                    class="text-center mt-3"
                                                    style="display: none">
                                                    <div
                                                        class="spinner-border text-primary"
                                                        role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <div class="form-floating mb-3">
                                                <select
                                                    class="form-select"
                                                    id="floatingSelect"
                                                    name="payment_method"
                                                    aria-label="Floating label select example">
                                                    <option value="" selected>Select Payment Method</option>
                                                    <option value="credit_card">Credit Card</option>
                                                    <option value="debit_card">Debit Card</option>
                                                    <option value="gcash">Gcash</option>
                                                    <option value="cash">Cash</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                </select>
                                                <label for="floatingSelect">Payment Method</label>
                                                <div
                                                    id="createInvoiceSpinner"
                                                    class="text-center mt-3"
                                                    style="display: none">
                                                    <div
                                                        class="spinner-border text-primary"
                                                        role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-floating mb-3">
                                                <textarea
                                                    class="form-control"
                                                    placeholder="Leave a comment here"
                                                    name="comments"
                                                    id="floatingTextarea"></textarea>
                                                <label for="floatingTextarea">Comments</label>
                                                <div
                                                    id="createInvoiceSpinner"
                                                    class="text-center mt-3"
                                                    style="display: none">
                                                    <div
                                                        class="spinner-border text-primary"
                                                        role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        data-bs-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <h3>Recent Payment</h3>
                <?php if (!empty($recentPayments)): ?>
                    <?php foreach ($recentPayments as $payment): ?>
                        <?php
                        $statusClasses = [
                            'succeeded' => 'success',
                            'failed' => 'danger',
                            'cancelled' => 'warning', // 🟡 new status
                            'pending' => 'secondary' // fallback for pending
                        ];

                        $badgeClass = $statusClasses[$payment['status']] ?? 'secondary';
                        ?>
                        <div class="cont d-flex justify-content-between align-items-center mb-3">
                            <div class="name d-flex align-items-center">
                                <i class="fa-solid fa-user fa-lg me-2"></i>
                                <div class="info">
                                    <h6 style="text-align: start;"><?= "INV" . str_pad($payment['invoice_id'], 4, '0', STR_PAD_LEFT); ?></h6>
                                    <p><?= htmlspecialchars($payment['guest_name']); ?></p>
                                </div>
                            </div>
                            <div class="price text-end">
                                <h6>₱<?= number_format($payment['amount_paid'], 2); ?></h6>
                                <p>
                                    <span class="badge text-bg-<?= $badgeClass; ?>">
                                        <?= ucfirst($payment['status']); ?>
                                    </span>

                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No payments recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>

</html>