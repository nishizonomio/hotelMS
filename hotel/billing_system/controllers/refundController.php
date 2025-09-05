<?php
require_once __DIR__.'/../models/Refund.php';

class RefundController {
    private $model;

    public function __construct($conn) {
        $this->model = new Refund($conn);
    }

    public function create($payment_id, $refund_date, $refund_time, $amount, $reason, $status) {
        return $this->model->createRefund($payment_id, $refund_date, $refund_time, $amount, $reason, $status);
    }

    public function view($refund_id) {
        return $this->model->getRefundById($refund_id);
    }

    public function getRefundsByPayment($payment_id) {
        return $this->model->getRefundsByPaymentId($payment_id);
    }

    public function updateStatus($refund_id, $status) {
        return $this->model->updateStatus($refund_id, $status);
    }

    public function delete($refund_id) {
        return $this->model->deleteRefund($refund_id);
    }
}
