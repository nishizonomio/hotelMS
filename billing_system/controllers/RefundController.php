<?php
require_once __DIR__ . '/../models/Refund.php';

class RefundController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new Refund($conn);
    }

    /**
     * Submit a new refund request (status = pending)
     */
    public function submitRefundRequest($payment_id, $invoice_id, $amount, $method, $reason, $processed_by)
    {
        return $this->model->createRefund($payment_id, $invoice_id, $amount, $method, $reason, $processed_by);
    }

    /**
     * View refund details by ID
     */
    public function viewRefund($refund_id)
    {
        return $this->model->getRefundById($refund_id);
    }

    /**
     * Get all refunds for a given payment
     */
    public function listRefundsByPayment($payment_id)
    {
        return $this->model->getRefundsByPaymentId($payment_id);
    }

    /**
     * Get all pending refund requests
     */
    public function listPendingRefunds()
    {
        return $this->model->getPendingRefunds();
    }

    /**
     * Approve a refund request
     */
    public function approveRefund($refund_id, $processed_by)
    {
        return $this->model->updateStatus($refund_id, 'approved', $processed_by);
    }

    /**
     * Decline a refund request
     */
    public function declineRefund($refund_id, $processed_by)
    {
        return $this->model->updateStatus($refund_id, 'declined', $processed_by);
    }

    /**
     * Delete a refund record
     */
    public function deleteRefund($refund_id)
    {
        return $this->model->deleteRefund($refund_id);
    }
}
