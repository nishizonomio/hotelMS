<?php
require_once __DIR__.'/../controllers/InvoiceController.php';
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

$db         = new Database();
$conn       = $db->getConnection();
$controller = new InvoiceController($conn);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $id     = $_GET['invoice_id'] ?? !null;
        $result = $id
            ? $controller->getInvoiceById((int)$id)
            : ['error' => 'Missing invoice_id'];
        echo json_encode($result);
        break;

    case 'POST':
        $bkgId = (int) ($_POST['booking_id']   ?? 0);
        $date  = $_POST['invoice_date']       ?? date('Y-m-d');
        $time  = $_POST['invoice_time']       ?? date('H:i:s');
        $tot   = (float) ($_POST['total_amount'] ?? 0);
        $stat  = $_POST['status']             ?? 'unpaid';
        $result = $controller->createInvoice($bkgId, $date, $time, $tot, $stat);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['error' => 'Unsupported request method']);
}
