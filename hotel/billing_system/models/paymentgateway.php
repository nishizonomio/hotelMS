<?php
// models/PaymentGateway.php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/payments.php';

class PaymentGateway {
    private $config;

    public function __construct($config = []) {
        $this->config = $config;
    }

    public function processPayment($method, $amount, $reference = null, $paymentId = null, $conn = null) {
        if (!$this->isValid($method, $amount)) {
            $result = [
                "success" => false,
                "gateway" => $method,
                "transaction_id" => null,
                "amount" => $amount,
                "reference" => $reference,
                "message" => "Invalid payment method or amount."
            ];
            $result['transaction_id'] = $this->logTransaction($paymentId, $method, '400', $result['message'], $conn);
            return $result;
        }

        switch (strtolower($method)) {
            case 'cash':
                $result = $this->processCash($amount, $reference);
                break;
            case 'card':
                $result = $this->processCard($amount, $reference);
                break;
            case 'online':
                $result = $this->processOnline($amount, $reference);
                break;
            default:
                $result = [
                    "success" => false,
                    "gateway" => $method,
                    "transaction_id" => null,
                    "amount" => $amount,
                    "reference" => $reference,
                    "message" => "Unsupported payment method."
                ];
        }

        if ($conn && $paymentId) {
            $result['transaction_id'] = $this->logTransaction(
                $paymentId,
                $result['gateway'],
                $result['success'] ? '200' : '400',
                $result['message'] ?? 'Processed',
                $conn
            );
        }

        return $result;
    }

    private function isValid($method, $amount) {
        return in_array(strtolower($method), ['cash', 'card', 'online']) && is_numeric($amount) && $amount > 0;
    }

    private function processCash($amount, $reference) {
        return [
            "success" => true,
            "gateway" => "Cash",
            "transaction_id" => uniqid("cash_"),
            "amount" => $amount,
            "reference" => $reference,
            "message" => "Cash payment recorded."
        ];
    }

    private function processCard($amount, $reference) {
        return [
            "success" => true,
            "gateway" => "Card",
            "transaction_id" => uniqid("card_"),
            "amount" => $amount,
            "reference" => $reference,
            "message" => "Card payment simulated."
        ];
    }

    private function processOnline($amount, $reference) {
        return [
            "success" => true,
            "gateway" => "Online",
            "transaction_id" => uniqid("online_"),
            "amount" => $amount,
            "reference" => $reference,
            "message" => "Online payment simulated."
        ];
    }

    private function logTransaction($paymentId, $gateway, $responseCode, $responseMessage, $conn) {
        if (!$conn || !$paymentId) return null;

        $stmt = $conn->prepare("
            INSERT INTO payment_gateway_transactions 
            (payment_id, gateway_name, response_code, response_message) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $paymentId, $gateway, $responseCode, $responseMessage);
        
        if ($stmt->execute()) {
            return $stmt->insert_id; // Return actual transaction_id from DB
        }

        return null;
    }
}
