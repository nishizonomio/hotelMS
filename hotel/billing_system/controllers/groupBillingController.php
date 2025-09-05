<?php
require_once __DIR__.'/../models/GroupBilling.php';

class GroupBillingController {
    private $model;

    public function __construct($conn) {
        $this->model = new GroupBilling($conn);
    }

    public function createGroupBilling($group_name, $total_group_amount, $date, $time) {
        return $this->model->create($group_name, $total_group_amount, $date, $time);
    }

    public function getGroupBillingDetails($group_billing_id) {
        return $this->model->getById($group_billing_id);
    }

    public function getAllGroupBillings() {
        return $this->model->getAll();
    }

    public function deleteGroupBilling($group_billing_id) {
        return $this->model->delete($group_billing_id);
    }
}
