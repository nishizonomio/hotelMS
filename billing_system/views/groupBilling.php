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
    <link rel="stylesheet" href="../assets/css/group.css" />
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
                <li>
                    <a href="groupBilling.php" class="active"><i class="fa-solid fa-user-group"></i> Group Billing</a>
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
            <h2>Group Billing</h2>
            <div>
                <h5>Welcome Admin!</h5>
                <button id="darkModeToggle" class="btn btn-outline-dark">
                    <i id="darkIcon" class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="container text-center">
            <div class="head">
                <h3>Group Billing</h3>
                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#staticBackdrop">
                    + Generate Group Invoice
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
                                            <textarea
                                                class="form-control"
                                                placeholder="Leave a comment here"
                                                id="floatingTextarea"
                                                style="height: 100px"></textarea>
                                            <label for="floatingTextarea">Reason</label>
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
            </div>
            <div class="card">
                <div class="groupHead">
                    <div class="group">
                        <h5>Group Name</h5>
                        <p>Guest Name</p>
                    </div>
                    <p>
                        <span class="badge rounded-pill text-bg-success">Confirmed</span>
                    </p>
                </div>
                <div class="row mb-4">
                    <div class="col">
                        <i class="fa-solid fa-people-group"></i> 15 Rooms
                    </div>
                    <div class="col">
                        <i class="fa-solid fa-calendar-days"></i> 2020-12-25 - 2021-01-01
                    </div>
                    <div class="col">
                        <i class="fa-solid fa-envelope"></i> example@email.com
                    </div>
                    <div class="col"><i class="fa-solid fa-phone"></i> 09674563219</div>
                </div>
                <div class="summary">
                    <h5>Billing Summary</h5>
                    <h5>&#8369; 20,000</h5>
                </div>
                <div class="row">
                    <div class="col col1">
                        <p class="charge">Room Charges</p>
                        <h5 class="amou">&#8369;18,000</h5>
                    </div>
                    <div class="col col2">
                        <p>Extra Services</p>
                        <h5>&#8369;2000</h5>
                    </div>
                </div>
                <form action="" class="formBtn">
                    <button type="button" class="btn btn-primary">
                        Generate Group Invoice
                    </button>
                    <button type="button" class="btn btn-secondary">
                        Split Billing
                    </button>
                    <button type="button" class="btn btn-success">
                        Process Payment
                    </button>
                </form>
            </div>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
</body>

</html>