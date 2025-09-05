<?php
// controllers/PaymentGatewayController.php

require_once __DIR__.'/../models/paymentgateway.php';

class PaymentGatewayController {
    private $gateway;

    /**
     * Optional config can be passed for future expansion (e.g. API keys)
     */
    public function __construct($config = []) {
        $this->gateway = new PaymentGateway($config);
    }

    /**
     * Simulate and log a payment gateway transaction
     *
     * @param string $method         Payment method (e.g. cash, card, online)
     * @param float  $amount         Amount paid
     * @param string $reference      Booking code or external reference
     * @param int    $paymentId      ID of the recorded payment
     * @param mysqli $conn           Active DB connection
     * @return array                 Gateway response + transaction log status
     */
    public function simulateTransaction($method, $amount, $reference, $paymentId, $conn) {
        if (!$conn || !$paymentId || !$method || $amount <= 0) {
            return [
                "success" => false,
                "message" => "Missing or invalid input for gateway transaction.",
                "transaction_id" => null
            ];
        }

        return $this->gateway->processPayment($method, $amount, $reference, $paymentId, $conn);
    }
}
