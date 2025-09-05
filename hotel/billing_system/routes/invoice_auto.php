<?php
require_once __DIR__.'/../controllers/InvoiceController.php';
require_once __DIR__.'/../controllers/folioController.php';
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

$db                = new Database();
$conn              = $db->getConnection();
$invoiceController = new InvoiceController($conn);
$folioController   = new FolioController($conn);

$eligible = $conn->query("
    SELECT booking_id
      FROM bookings
     WHERE status IN ('checked_in','checked_out')
");

$generated = [];
while ($row = $eligible->fetch_assoc()) {
    $bookingId = (int) $row['booking_id'];

    // skip if invoice exists
    $chk = $conn->prepare("SELECT invoice_id FROM invoices WHERE booking_id = ?");
    $chk->bind_param("i", $bookingId);
    $chk->execute();
    if ($chk->get_result()->num_rows) {
        $chk->close();
        continue;
    }
    $chk->close();

    // sum folio for this booking → invoice_id will be created to match booking_id
    $items = $folioController->getFolioTransactionsByInvoiceId($bookingId);
    $total = array_sum(array_column($items, 'amount'));

    // create invoice
    $res = $invoiceController->createInvoice(
        $bookingId,
        date('Y-m-d'),
        date('H:i:s'),
        $total,
        'unpaid'
    );

    if (!empty($res['success'])) {
        $generated[] = [
            'booking_id'   => $bookingId,
            'invoice_id'   => $res['invoice_id'] ?? null,
            'total_amount' => $total
        ];
    }
}

echo json_encode([
    'generated_invoices' => $generated,
    'count'              => count($generated)
]);
