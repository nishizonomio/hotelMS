<?php
require_once __DIR__.'/../controllers/FolioController.php';
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

$db         = new Database();
$conn       = $db->getConnection();
$controller = new FolioController($conn);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $invoiceId = $_GET['invoice_id'] ?? true;
        if ($invoiceId) {
            $result = $controller->getFolioTransactionsByInvoiceId((int)$invoiceId);
        } else {
            $result = ['error' => 'Missing invoice_id'];
        }
        echo json_encode($result);
        break;

    case 'POST':
        $data = [
            'invoice_id'       => (int) ($_POST['invoice_id'] ?? 0),
            'service_type'     => $_POST['service_type'] ?? '',
            'description'      => $_POST['description'] ?? 'N/A',
            'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
            'transaction_time' => $_POST['transaction_time'] ?? date('H:i:s'),
            'amount'           => (float) ($_POST['amount'] ?? 0)
        ];
        $result = $controller->createFolioTransaction(
            $data['invoice_id'],
            $data['service_type'],
            $data['description'],
            $data['transaction_date'],
            $data['transaction_time'],
            $data['amount']
        );
        echo json_encode($result);
        break;

    default:
        echo json_encode(['error' => 'Unsupported request method']);
}
