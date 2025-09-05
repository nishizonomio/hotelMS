<?php
// routes/groupbilling.php

require_once __DIR__.'/../controllers/GroupBillingController.php';
require_once __DIR__.'/../controllers/GroupBillingMemberController.php';
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

$db             = new Database();
$conn           = $db->getConnection();
$billingCtrl    = new GroupBillingController($conn);
$memberCtrl     = new GroupBillingMemberController($conn);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $groupId = isset($_GET['group_billing_id']) ? (int)$_GET['group_billing_id'] : null;
        $action  = $_GET['action'] ?? null;

        if (!$groupId) {
            echo json_encode(['error' => 'Missing group_billing_id']);
            break;
        }

        if ($action === 'members') {
            // List members of a group
            $result = $memberCtrl->getGroupMembers($groupId);
        } else {
            // Get group billing details
            $result = $billingCtrl->getGroupBillingDetails($groupId);
        }

        echo json_encode($result);
        break;

    case 'POST':
        $groupId = isset($_POST['group_billing_id']) ? (int)$_POST['group_billing_id'] : null;

        if (isset($_POST['invoice_id'], $_POST['share_amount'])) {
            // Add invoice to group
            $invoiceId  = (int)$_POST['invoice_id'];
            $shareAmount = (float)$_POST['share_amount'];
            $result = $memberCtrl->addInvoiceToGroup($groupId, $invoiceId, $shareAmount);
        } else {
            // Create new group billing
            $name   = $_POST['group_name']            ?? '';
            $total  = (float) $_POST['total_group_amount'] ?? 0;
            $date   = $_POST['date']                  ?? date('Y-m-d');
            $time   = $_POST['time']                  ?? date('H:i:s');
            $result = $billingCtrl->createGroupBilling($name, $total, $date, $time);
        }

        echo json_encode($result);
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $put);

        if (!isset($put['group_billing_id'], $put['invoice_id'], $put['share_amount'])) {
            echo json_encode(['error' => 'Missing group_billing_id, invoice_id or share_amount']);
            break;
        }

        $groupId     = (int) $put['group_billing_id'];
        $invoiceId   = (int) $put['invoice_id'];
        $shareAmount = (float) $put['share_amount'];

        $result = $memberCtrl->updateMemberShare($groupId, $invoiceId, $shareAmount);
        echo json_encode($result);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $del);

        if (isset($del['invoice_id'])) {
            // Remove a member
            $groupId   = (int) $del['group_billing_id'];
            $invoiceId = (int) $del['invoice_id'];
            $result = $memberCtrl->removeMember($groupId, $invoiceId);
        } else {
            // Delete entire group billing
            $groupId = (int) $del['group_billing_id'];
            $result = $billingCtrl->deleteGroupBilling($groupId);
        }

        echo json_encode($result);
        break;

    default:
        echo json_encode(['error' => 'Unsupported request method']);
}
