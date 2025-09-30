<?php
require_once __DIR__ . '/../controllers/InvoiceController.php';

$controller = new InvoiceController();

// Handle filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

if ($statusFilter === "all") {
    $response = $controller->getAllInvoices();
} else {
    $response = $controller->getInvoicesByStatus($statusFilter);
}

$pendingInvoices = $controller->getUnpaidInvoiceCount();
$pendingCount = $pendingInvoices['total'] ?? 0;

$invoices = [];
if (!empty($response) && isset($response['success']) && $response['success']) {
    if (isset($response['data']) && is_array($response['data'])) {
        // slice safely
        $invoices = array_slice($response['data'], 0, 3);
    } else {
        // defensive fallback: if data is a single invoice object, convert to array
        if (!empty($response['data'])) {
            $invoices = is_array($response['data']) ? array_slice($response['data'], 0, 3) : [];
        }
    }
}
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
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>

<body>
    <aside class="sidebar">
        <h2>Billing System</h2>
        <nav>
            <ul>
                <li>
                    <a href="billing.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
                </li>
                <li>
                    <a href="invoice.php"><i class="fa-solid fa-file-lines"></i> Invoice</a>
                </li>
                <li>
                    <a href="payments.php"><i class="fa-solid fa-wallet"></i> Payment</a>
                </li>
                <li>
                    <a href="refund.php"><i class="fa-solid fa-rotate-left"></i> Refund</a>
                </li>
                <!-- <li>
                    <a href="groupBilling.php"><i class="fa-solid fa-user-group"></i> Group Billing</a>
                </li> -->
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
            <h2>Dashboard</h2>
            <div>
                <h5>Welcome Admin!</h5>
                <button id="darkModeToggle" class="btn theme btn-outline-dark">
                    <i id="darkIcon" class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="container text-center">
            <h2>Overview</h2>
            <div class="boxes">
                <div class="row row1">
                    <div class="card card1 col gx-4">
                        <p>💰 Total Sales</p>
                        <h3>&#8369; 100000</h3>
                    </div>
                    <div class="card card2 col">
                        <p>📄 Unpaid Invoice</p>
                        <h3><?php echo $pendingCount; ?></h3>
                    </div>
                    <div class="card card3 col">
                        <p>⏰ Overdue</p>
                        <h3>4</h3>
                    </div>
                    <div class="card card4 col">
                        <p>💸 Refunds</p>
                        <h3>4</h3>
                    </div>
                </div>
                <div class="row row2">
                    <div class="card card5 col">
                        <h3>Recent Invoice</h3>
                        <?php if (!empty($invoices)) : ?>
                            <?php
                            // Get only the first 3 invoices
                            $recentInvoices = array_slice($invoices, 0, 3);
                            ?>

                            <?php foreach ($invoices as $row) : ?>
                                <div class="cont">
                                    <div class="name">
                                        <h6><?php echo "INV" . str_pad($row['invoice_id'], 4, '0', STR_PAD_LEFT); ?></h6>
                                        <p><?php echo htmlspecialchars($row['guest_name'] ?? 'Unknown'); ?></p>
                                    </div>
                                    <div class="price">
                                        <h6>₱<?php echo number_format($row['total_amount'], 2); ?></h6>
                                        <p>
                                            <span class="badge text-bg-<?php echo $row['status'] === 'paid' ? 'success' : ($row['status'] === 'refunded' ? 'info' : 'warning'); ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>No invoices found.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card card6 col">
                        <h3>⚡ Quick Actions</h3>
                        <button
                            type="button"
                            class="btn btn1 btn-primary mb-3"
                            data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop">
                            + Create Invoice
                        </button>
                        <button
                            type="button"
                            class="btn btn2 btn-primary mb-3"
                            data-bs-toggle="modal"
                            data-bs-target="#staticBackdropPayment">
                            + Process Payment
                        </button>
                        <button
                            type="button"
                            class="btn btn3 btn-primary mb-3"
                            data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop">
                            + View Pending Items
                        </button>
                        <div
                            class="modal fade"
                            id="staticBackdrop"
                            data-bs-backdrop="static"
                            data-bs-keyboard="false"
                            tabindex="-1"
                            z-index="0"
                            aria-labelledby="staticBackdropLabel"
                            aria-hidden="true">
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
                                                    <?php
                                                    require_once __DIR__ . '/../config/database.php';
                                                    $db = (new Database())->getConnection();
                                                    $sql = "SELECT b.booking_id, CONCAT(g.first_name,' ',g.last_name) AS guest_name, r.room_number
                                                FROM bookings b
                                                JOIN guests g ON b.guest_id = g.guest_id
                                                JOIN rooms r ON b.room_id = r.room_id
                                                WHERE b.status IN ('confirmed','checked_in')";
                                                    $result = $db->query($sql);
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='{$row['booking_id']}'>" .
                                                            htmlspecialchars($row['guest_name']) . " - Room " . $row['room_number'] .
                                                            "</option>";
                                                    }
                                                    ?>
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
                        <div
                            class="modal fade"
                            id="staticBackdropPayment"
                            data-bs-backdrop="static"
                            data-bs-keyboard="false"
                            tabindex="-1"
                            aria-labelledby="staticBackdropLabel"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
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
                                                    <select
                                                        class="form-select"
                                                        id="floatingSelect"
                                                        aria-label="Floating label select example">
                                                        <option selected>Select Guest</option>
                                                        <option value="1">One</option>
                                                    </select>
                                                    <label for="floatingSelect">Guest Id</label>
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
                                                        aria-label="Floating label select example">
                                                        <option selected>Select Payment Method</option>
                                                        <option value="1">Credit Card</option>
                                                        <option value="2">Gcash</option>
                                                        <option value="3">Cash</option>
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
                                </div>
                            </div>
                        </div>
                        <div
                            class="modal fade"
                            id="staticBackdrop2"
                            data-bs-backdrop="static"
                            data-bs-keyboard="false"
                            tabindex="-1"
                            z-index="0"
                            aria-labelledby="staticBackdropLabel"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                            Create Invoice
                                        </h1>
                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-floating mb-3">
                                            <select
                                                class="form-select"
                                                id="floatingSelect"
                                                aria-label="Floating label select example">
                                                <option selected>Select booking</option>
                                                <option value="1">One</option>
                                                <option value="2">Two</option>
                                                <option value="3">Three</option>
                                            </select>
                                            <label for="floatingSelect">Bookings</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            data-bs-dismiss="modal">
                                            Close
                                        </button>
                                        <button type="button" class="btn btn-primary">
                                            Submit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
        <!-- Success Toast -->
        <div
            id="toastSuccess"
            class="toast align-items-center text-bg-success border-0 fade"
            role="alert"
            aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">✅ Invoice created successfully!</div>
                <button
                    type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>

        <!-- Error Toast -->
        <div
            id="toastError"
            class="toast align-items-center text-bg-danger border-0 fade"
            role="alert"
            aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ❌ Error creating invoice. Please try again.
                </div>
                <button
                    type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>