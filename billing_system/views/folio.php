<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <!-- <link
        rel="stylesheet"
        href="../assets/bootstrap-5.3.8-dist/css/bootstrap.min.css" />
    <script src="../assets/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script> -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/folio.css" />
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
                    <a href="payments.php"><i class="fa-solid fa-wallet"></i> Payment</a>
                </li>
                <li>
                    <a href="refund.php"><i class="fa-solid fa-rotate-left"></i> Refund</a>
                </li>
                <!-- <li>
                    <a href="groupBilling.php"><i class="fa-solid fa-user-group"></i> Group Billing</a>
                </li> -->
                <li>
                    <a href="folio.php" class="active"><i class="fa-solid fa-folder"></i> Folio Management</a>
                </li>
                <li>
                    <a href="/../hotel/homepage/index.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </li>
            </ul>
        </nav>
    </aside>
    <main class="content">
        <header>
            <h2>Folio Management</h2>
            <div>
                <h5>Welcome Admin!</h5>
                <button id="darkModeToggle" class="btn btn-outline-dark">
                    <i id="darkIcon" class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="container text-center">
            <div class="card">
                <div class="folio-header">
                    <div class="folio-info">
                        <h2>John Smith</h2>
                        <p>Room 201 • 2024-01-15 - 2024-01-18</p>
                    </div>
                    <div class="folio-actions">
                        <a href="#"><i class="fa-solid fa-download"></i></a>
                        <a href="javascript:window.print()"><i class="fa-solid fa-print"></i></a>
                        <a href="#"><i class="fa-solid fa-paper-plane"></i></a>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Charges</th>
                            <th>Payments</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2024-01-15</td>
                            <td>Room Charge - Deluxe Suite</td>
                            <td>$840</td>
                            <td>-</td>
                            <td>$840</td>
                        </tr>
                        <tr>
                            <td>2024-01-15</td>
                            <td>Room Payment - 2 nights</td>
                            <td>-</td>
                            <td>$600</td>
                            <td>$600</td>
                        </tr>
                        <tr>
                            <td>2024-01-16</td>
                            <td>Room Service</td>
                            <td>$150</td>
                            <td>-</td>
                            <td>$600</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Total</td>
                            <td>$600</td>
                            <td>$600</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
</body>

</html>