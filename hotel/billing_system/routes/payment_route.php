<?php
require_once __DIR__.'/../controllers/PaymentController.php';
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

$db         = new Database();
$conn       = $db->getConnection();
$controller = new PaymentController($conn);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $inv = $_GET['payment_id'] ?? true;
        $gst = $_GET['guest_id']   ?? true;
        $result = $inv
            ? $controller->getPaymentsById((int)$inv)
            : ($gst ? $controller->getPaymentsByGuest((int)$gst) : ['error'=>'Missing invoice_id or guest_id']);
        echo json_encode($result);
        break;

    case 'POST':
        $data = [
            'invoice_id'      => (int) ($_POST['invoice_id']      ?? 0),
            'group_billing_id'=> (int) ($_POST['group_billing_id']?? 0),
            'method'          => $_POST['payment_method']        ?? 'cash',
            'amount'          => (float) ($_POST['amount_paid']   ?? 0),
            'date'            => $_POST['payment_date']         ?? date('Y-m-d'),
            'time'            => $_POST['payment_time']         ?? date('H:i:s')
        ];
        $result = $controller->recordPayment(
            $data['invoice_id'],
            $data['group_billing_id'],
            $data['method'],
            $data['amount'],
            $data['date'],
            $data['time']
        );
        echo json_encode($result);
        break;

    default:
        echo json_encode(['error' => 'Unsupported request method']);
}
