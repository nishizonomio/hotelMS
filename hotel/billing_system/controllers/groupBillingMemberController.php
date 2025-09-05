<?php
require_once __DIR__.'/../models/GroupBillingMembers.php';

class GroupBillingMemberController {
    private $model;

    public function __construct($conn) {
        $this->model = new GroupBillingMembers($conn);
    }

    public function addInvoiceToGroup($group_billing_id, $invoice_id, $share_amount) {
        return $this->model->add($group_billing_id, $invoice_id, $share_amount);
    }

    public function getGroupMembers($group_billing_id) {
        return $this->model->getByGroup($group_billing_id);
    }

    public function updateMemberShare($group_billing_id, $invoice_id, $share_amount) {
        return $this->model->update($group_billing_id, $invoice_id, $share_amount);
    }

    public function removeMember($group_billing_id, $invoice_id) {
        return $this->model->delete($group_billing_id, $invoice_id);
    }
}
