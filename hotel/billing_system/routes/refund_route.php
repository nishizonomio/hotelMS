<?php
require_once __DIR__.'/../controllers/RefundController.php';
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

$db         = new Database();
$conn       = $db->getConnection();
$controller = new RefundController($conn);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $rid = $_GET['refund_id']   ?? null;
        $pid = $_GET['payment_id']  ?? null;
        $result = $rid
            ? $controller->view((int)$rid)
            : ($pid ? $controller->getRefundsByPayment((int)$pid) : ['error'=>'Missing refund_id or payment_id']);
        echo json_encode($result);
        break;

    case 'POST':
        $data = [
            'payment_id'  => (int) ($_POST['payment_id']  ?? 0),
            'refund_date' => $_POST['refund_date']      ?? date('Y-m-d'),
            'refund_time' => $_POST['refund_time']      ?? date('H:i:s'),
            'amount'      => (float) ($_POST['amount']   ?? 0),
            'reason'      => $_POST['reason']           ?? 'N/A',
            'status'      => $_POST['status']           ?? 'pending'
        ];
        $result = $controller->create(
            $data['payment_id'],
            $data['refund_date'],
            $data['refund_time'],
            $data['amount'],
            $data['reason'],
            $data['status']
        );
        echo json_encode($result);
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $put);
        $rid = (int) ($put['refund_id'] ?? 0);
        $st  = $put['status']         ?? null;
        $result = $controller->updateStatus($rid, $st);
        echo json_encode($result);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $del);
        $rid = (int) ($del['refund_id'] ?? 0);
        $result = $controller->delete($rid);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['error'=>'Unsupported request method']);
}
