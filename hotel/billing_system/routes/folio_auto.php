<?php
require_once __DIR__.'/../controllers/FolioController.php';
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

$db         = new Database();
$conn       = $db->getConnection();
$controller = new FolioController($conn);

$inserted = [];

// Define POS sources and mapping
$posSources = [
    [
        "table"        => "RestaurantBilling",
        "type"         => "restaurant",
        "id_field"     => "order_id",
        "amount_field" => "total_amount",
        "date_field"   => "order_date",
        "guest_field"  => "guest_id"
    ],
    [
        "table"        => "MinibarTracking",
        "type"         => "minibar",
        "id_field"     => "minibar_id",
        "amount_field" => "price",
        "date_field"   => "usage_date",
        "guest_field"  => "guest_id"
    ],
    [
        "table"        => "InRoomDiningOrders",
        "type"         => "inroom",
        "id_field"     => "service_id",
        "amount_field" => "total_amount",
        "date_field"   => "order_date",
        "guest_field"  => "guest_id"
    ],
    [
        "table"        => "GiftShopSales",
        "type"         => "giftshop",
        "id_field"     => "sale_id",
        "amount_field" => "total_amount",
        "date_field"   => "sale_date",
        "guest_field"  => "guest_id"
    ],
    [
        "table"        => "BarPOS",
        "type"         => "bar",
        "id_field"     => "bar_order_id",
        "amount_field" => "total_amount",
        "date_field"   => "order_date",
        "guest_field"  => "guest_id"
    ]
];

foreach ($posSources as $source) {
    $sql = sprintf(
        "SELECT %s, %s, %s, %s
         FROM %s
         WHERE %s > 0",
        $source['id_field'],
        $source['guest_field'],
        $source['amount_field'],
        $source['date_field'],
        $source['table'],
        $source['amount_field']
    );

    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $guestId = (int) $row[$source['guest_field']];
        $amount  = (float) $row[$source['amount_field']];
        $date    = date('Y-m-d',   strtotime($row[$source['date_field']]));
        $time    = date('H:i:s',   strtotime($row[$source['date_field']]));
        $desc    = "{$source['type']} charge from {$source['table']} ID {$row[$source['id_field']]}";

        // Lookup invoice_id by guest_id via bookings→invoices
        $invStmt = $conn->prepare("
            SELECT i.invoice_id
              FROM invoices i
              JOIN bookings b ON i.booking_id = b.booking_id
             WHERE b.guest_id = ?
             LIMIT 1
        ");
        $invStmt->bind_param("i", $guestId);
        $invStmt->execute();
        $invRes = $invStmt->get_result()->fetch_assoc();
        $invStmt->close();

        if (empty($invRes['invoice_id'])) {
            continue;
        }

        $invoiceId = (int) $invRes['invoice_id'];

        // Create folio transaction
        $controller->createFolioTransaction(
            $invoiceId,
            $source['type'],
            $desc,
            $date,
            $time,
            $amount
        );

        $inserted[] = [
            'invoice_id'   => $invoiceId,
            'service_type' => $source['type'],
            'description'  => $desc,
            'amount'       => $amount
        ];
    }
}

echo json_encode([
    'inserted_folio_items' => $inserted,
    'count'                => count($inserted)
]);
