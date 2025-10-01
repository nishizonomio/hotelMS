<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/RefundController.php';

$db = new Database();
$conn = $db->getConnection();

// Controllers
$refundController  = new RefundController($conn);
$invoiceController = new InvoiceController($conn);

// Refund data
$pendingRefunds  = $refundController->listRefundsByStatus('pending');
$approvedRefunds = $refundController->listRefundsByStatus('approved');
$declinedRefunds = $refundController->listRefundsByStatus('declined');
$historyRefunds  = $refundController->listAllRefunds();

// Paid invoices (eligible for refund request)
// Get all paid payments
$paymentController = new PaymentController();
$response = $paymentController->getPaymentByStatus('succeeded');
$paidPayments = [];

if (is_array($response) && isset($response['success']) && $response['success'] && isset($response['data'])) {
    $paidPayments = $response['data'];
} elseif (is_array($response) && isset($response['data'])) {
    $paidPayments = $response['data'];
} elseif (is_array($response)) {
    $paidPayments = $response;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Refund Management</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../assets/bootstrap-5.3.8-dist/css/bootstrap.min.css" />
    <script src="../assets/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/refund.css" />
</head>

<body>
    <aside class="sidebar">
        <h2>Billing System</h2>
        <nav>
            <ul>
                <li><a href="billing.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="invoice.php"><i class="fa-solid fa-file-lines"></i> Invoice</a></li>
                <li><a href="payments.php"><i class="fa-solid fa-wallet"></i> Payment</a></li>
                <li><a href="refund.php" class="active"><i class="fa-solid fa-rotate-left"></i> Refund</a></li>
                <!-- <li><a href="groupBilling.php"><i class="fa-solid fa-user-group"></i> Group Billing</a></li> -->
                <li><a href="folio.php"><i class="fa-solid fa-folder"></i> Folio</a></li>
                <li><a href="/../hotel/homepage/index.php"><i class="fa-solid fa-arrow-left"></i> Back</a></li>
            </ul>
        </nav>
    </aside>

    <main class="content">
        <header>
            <h2>Refund</h2>
            <div>
                <h5>Welcome Admin!</h5>
                <button id="darkModeToggle" class="btn btn-outline-dark">
                    <i id="darkIcon" class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="container text-center">
            <div class="head">
                <h3>Refund</h3>
                <!-- Request Refund Modal Trigger -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#refundModal">
                    <i class="fa-solid fa-rotate-right"></i> Request Refund
                </button>
            </div>

            <!-- Refund Modal -->
            <div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form action="/hotel/billing_system/routes/refund_action.php" method="POST">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5">Request Refund</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating mb-3">
                                            <select class="form-select" name="payment_id" required onchange="updateRefundInfo(this)">
                                                <option value="" selected disabled>Select Paid Invoice</option>
                                                <?php foreach ($paidPayments as $inv): ?>
                                                    <option
                                                        value="<?= $inv['payment_id'] ?>"
                                                        data-amount="<?= $inv['amount_paid'] ?>"
                                                        data-method="<?= htmlspecialchars($inv['payment_method']) ?>">
                                                        <?= "INV" . str_pad($inv['invoice_id'], 4, '0', STR_PAD_LEFT) ?>
                                                        - <?= htmlspecialchars($inv['guest_name']) ?>
                                                        - <?= htmlspecialchars($inv['status']) ?>
                                                        (₱<?= number_format($inv['amount_paid'], 2) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label>Paid Invoice</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" name="refund_amount" placeholder="Amount" disabled />
                                            <label>Refund Amount</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="refund_method" placeholder="Method" disabled />
                                            <label>Refund Method</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-floating mb-3">
                                    <textarea class="form-control" name="refund_reason" style="height:100px" required></textarea>
                                    <label>Reason</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="action" value="request" class="btn btn-primary">Submit</button>
                            </div>
                        </form>

                        <script>
                            function updateRefundInfo(select) {
                                const selected = select.selectedOptions[0];
                                document.getElementById('refund_amount').value = selected.dataset.amount;
                                document.getElementById('refund_method').value = selected.dataset.method;
                            }
                        </script>

                    </div>
                </div>
            </div>

            <!-- Refund Requests -->
            <div class="card overflow-auto mt-4" id="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#requests">Pending</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#approved">Approved</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#declined">Declined</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#history">History</a></li>
                    </ul>
                </div>
                <div class="card-body tab-content">
                    <!-- Pending Refunds -->
                    <div class="tab-pane fade show active" id="requests">
                        <h5>Pending Refund Requests</h5>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pendingRefunds)): ?>
                                    <?php foreach ($pendingRefunds as $refund): ?>
                                        <tr>
                                            <td>INV-<?= str_pad($refund['invoice_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                            <td>₱<?= number_format($refund['refund_amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($refund['refund_reason']) ?></td>

                                            <td>
                                                <form action="/hotel/billing_system/routes/refund_action.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="refund_id" value="<?= $refund['refund_id'] ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                                <form action="/hotel/billing_system/routes/refund_action.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="refund_id" value="<?= $refund['refund_id'] ?>">
                                                    <button type="submit" name="action" value="decline" class="btn btn-danger btn-sm">Decline</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">No pending requests</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Approved Refunds -->
                    <div class="tab-pane fade" id="approved">
                        <h5>Approved Refunds</h5>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($approvedRefunds)): ?>
                                    <?php foreach ($approvedRefunds as $refund): ?>
                                        <tr>
                                            <td>#INV-<?= str_pad($refund['invoice_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                            <td>₱<?= number_format($refund['refund_amount'], 2) ?></td>
                                            <td><span class="badge text-bg-success">Approved</span></td>
                                            <td>
                                                <form action="../routes/refund_action.php" method="POST">
                                                    <input type="hidden" name="refund_id" value="<?= $refund['refund_id'] ?>">
                                                    <button type="submit" name="action" value="process" class="btn btn-primary btn-sm">Process</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No approved refunds</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Declined Refunds -->
                    <div class="tab-pane fade" id="declined">
                        <h5>Declined Refunds</h5>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($declinedRefunds)): ?>
                                    <?php foreach ($declinedRefunds as $refund): ?>
                                        <tr>
                                            <td>#INV-<?= str_pad($refund['invoice_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                            <td>₱<?= number_format($refund['refund_amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($refund['refund_reason']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">No declined refunds</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Refund History -->
                    <div class="tab-pane fade" id="history">
                        <h5>Refund History</h5>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($historyRefunds)): ?>
                                    <?php foreach ($historyRefunds as $refund): ?>
                                        <tr>
                                            <td>#INV-<?= str_pad($refund['invoice_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                            <td>₱<?= number_format($refund['refund_amount'], 2) ?></td>
                                            <td><span class="badge text-bg-<?= $payment['status'] === 'approved' ? 'success' : ($payment['status'] === 'declined' ? 'danger' : 'secondary'); ?>"><?= ucfirst($refund['status']) ?></span></td>
                                            <td><?= htmlspecialchars($refund['refund_date']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No refund history</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>

</html>