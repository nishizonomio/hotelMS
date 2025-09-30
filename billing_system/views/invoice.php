<?php
session_start();
require_once __DIR__ . '/../controllers/InvoiceController.php';

$controller = new InvoiceController();

// Handle filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

if ($statusFilter === "all") {
    $response = $controller->getAllInvoices();
} else {
    $response = $controller->getInvoicesByStatus($statusFilter);
}

$invoices = $response['success'] ? $response['data'] : [];
$bookings = $controller->getActiveBookings();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invoices</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../assets/bootstrap-5.3.8-dist/css/bootstrap.min.css" />
    <script src="../assets/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/invoice.css" />
</head>

<body>
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1060">
            <div id="liveToast" class="toast align-items-center text-bg-<?php echo $_SESSION['flash_type']; ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $_SESSION['flash_message']; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const toastEl = document.getElementById("liveToast");
                if (toastEl) {
                    const toast = new bootstrap.Toast(toastEl, {
                        delay: 4000
                    });
                    toast.show();
                }
            });
        </script>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
    <aside class="sidebar">
        <h2>Billing System</h2>
        <nav>
            <ul>
                <li><a href="billing.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="invoice.php" class="active"><i class="fa-solid fa-file-lines"></i> Invoice</a></li>
                <li><a href="payments.php"><i class="fa-solid fa-wallet"></i> Payment</a></li>
                <li><a href="refund.php"><i class="fa-solid fa-rotate-left"></i> Refund</a></li>
                <li><a href="groupBilling.php"><i class="fa-solid fa-user-group"></i> Group Billing</a></li>
                <li><a href="folio.php"><i class="fa-solid fa-folder"></i> Folio Management</a></li>
                <li><a href="../homepage/index.php"><i class="fa-solid fa-arrow-left"></i> Back</a></li>
            </ul>
        </nav>
    </aside>






    <main class="content">
        <header>
            <h2>Invoice</h2>
            <div>
                <h5>Welcome Admin!</h5>
                <button id="darkModeToggle" class="btn btn-outline-dark">
                    <i id="darkIcon" class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="container text-center">
            <div class="head d-flex justify-content-between align-items-center">
                <h3>Invoices</h3>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
                    + Create Invoice
                </button>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="createInvoiceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="../routes/store_invoice.php" method="POST">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5">Create Invoice</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Booking Dropdown -->
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="floatingSelect" name="booking_id" required>
                                        <option value="" selected disabled>Select booking</option>
                                        <?php foreach ($bookings as $row): ?>
                                            <option value="<?= htmlspecialchars($row['booking_id']) ?>">
                                                <?= htmlspecialchars($row['guest_name']) ?> - Room <?= htmlspecialchars($row['room_number']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="floatingSelect">Bookings</label>
                                </div>

                                <!-- Status -->
                                <div class="form-floating mb-3">
                                    <select class="form-select" name="status" required>
                                        <option value="unpaid" selected>Unpaid</option>
                                        <option value="paid">Paid</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
                                    <label>Status</label>
                                </div>

                                <div id="createInvoiceSpinner" class="text-center mt-3" style="display:none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Invoice</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Invoice Table -->
            <div class="card mt-4">
                <div class="dropdown d-flex justify-content-start mb-3">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?php echo ucfirst($statusFilter); ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="invoice.php?status=all">All</a></li>
                        <li><a class="dropdown-item" href="invoice.php?status=unpaid">Unpaid</a></li>
                        <li><a class="dropdown-item" href="invoice.php?status=paid">Paid</a></li>
                        <li><a class="dropdown-item" href="invoice.php?status=cancelled">Cancelled</a></li>
                        <li><a class="dropdown-item" href="invoice.php?status=refunded">Refunded</a></li>
                    </ul>
                </div>

                <div class="table-responsive overflow-y" style="max-height: 62vh;">
                    <table class="table table-bordered mb-0">
                        <thead class="table-dark sticky-header">
                            <tr>
                                <th>Invoice ID</th>
                                <th>Guest Name</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($invoices)) : ?>
                                <?php foreach ($invoices as $row) : ?>
                                    <tr>
                                        <td><?php echo "INV" . str_pad($row['invoice_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($row['guest_name'] ?? 'Unknown'); ?></td>
                                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td><span class="badge text-bg-<?php echo $row['status'] === 'paid' ? 'success' : ($row['status'] === 'refunded' ? 'info' : 'warning'); ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span></td>
                                        <td>
                                            <a href="../routes/view_invoice.php?id=<?php echo $row['invoice_id']; ?>"><i class="fa-solid fa-eye"></i></a> |
                                            <a href="../routes/edit_invoice.php?id=<?php echo $row['invoice_id']; ?>"><i class="fa-solid fa-pen" style="color: #04b910;" data-bs-toggle="modal" data-bs-target="#stats"></i></a> |
                                            <a href="../routes/delete_invoice.php?id=<?php echo $row['invoice_id']; ?>" onclick="return confirm('Delete this invoice?')"><i class="fa-solid fa-trash-can" style="color: #ff0000;"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5">No invoices found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
</body>

</html>