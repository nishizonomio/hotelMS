<?php
// controllers/PaymentController.php

require_once __DIR__.'/../models/paymentgateway.php';
require_once __DIR__.'/../models/payments.php';
require_once __DIR__.'/../config/database.php';

class PaymentController {
    private $gateway;
    private $model;

    public function __construct($conn) {
        $this->model = new Payments($conn);
        $this->gateway = new PaymentGateway();
    }

    // Record a payment using the model
    public function recordPayment($invoiceId, $groupBillingId, $method, $amount, $date, $time) {
        return $this->model->create($invoiceId, $groupBillingId, $method, $amount, $date, $time);
    }

    // Fetch payments by invoice
    public function getPaymentsByInvoiceId($invoice_id) {
        return $this->model->getPaymentsByInvoiceId($invoice_id);
    }

    public function getPaymentsById($payment_id) {
        return $this->model->getPaymentsById($payment_id);
    }

    // Fetch payments by guest
    public function getPaymentsByGuest($guestId) {
        return $this->model->getByGuest($guestId);
    }

    // Simulate and log a gateway transaction
    public function handlePayment($method, $amount, $reference, $paymentId) {
        return $this->gateway->processPayment($method, $amount, $reference, $paymentId, $this->model->getConnection());
    }
}
