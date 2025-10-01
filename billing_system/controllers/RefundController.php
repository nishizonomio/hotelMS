<?php
require_once __DIR__ . '/../models/Refund.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../models/paymentGateway.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

class RefundController
{
    private $model;
    private $paymentController;
    private $gateway;

    public function __construct($conn)
    {
        $this->model = new Refund($conn);
        $this->paymentController = new PaymentController();
        $this->gateway = new PaymentGatewayTransactions();
    }

    /**
     * Submit a new refund request (status = pending)
     */
    public function submitRefundRequest($payment_id, $refund_amount, $refund_method, $refund_reason, $processed_by = null)
    {
        // Fetch the payment record
        $payment = $this->paymentController->getPaymentsById($payment_id);
        if (!$payment) return ["success" => false, "message" => "Payment not found."];

        // Create refund request with DB values
        $refund = $this->model->createRefund(
            $payment['payment_id'],       // payment_id
            $payment['invoice_id'],       // invoice_id
            $payment['amount_paid'],      // amount
            $refund_method,
            $refund_reason,                      // reason
            null,                          // processed_by = NULL
            'pending',

        );

        return [
            "success" => true,
            "message" => "Refund request submitted.",
            "refund_id" => $refund['refund_id']
        ];
    }

    public function approveRefund($refund_id)
    {
        // Fetch refund record
        $refund = $this->model->getRefundById($refund_id);
        if (!$refund) return ["success" => false, "message" => "Refund not found."];

        // Fetch payment record
        $payment = $this->paymentController->getPaymentsById($refund['payment_id']);
        if (!$payment) return ["success" => false, "message" => "Original payment not found."];

        $refund_method = $refund['refund_method'];
        $amount = $refund['refund_amount'] ?? 0;
        $reason = $refund['refund_reason'] ?? 'No reason provided';

        // Process refund based on method
        switch ($refund_method) {
            case 'cash':
            case 'bank_transfer':
                $result = $this->processManualRefund($refund, $payment);
                break;

            case 'gcash':
                $result = $this->processPayMongoRefund($refund, $payment);
                break;

            case 'credit_card':
            case 'debit_card':
                $result = $this->processStripeRefund($refund, $payment);
                break;

            default:
                return ["success" => false, "message" => "Unknown refund method."];
        }

        // Update refund as processed
        $this->model->updateRefundStatus($refund_id, $result['status']);

        return $result;
    }

    private function processManualRefund($refund, $payment)
    {
        // Simply mark as success
        return ["success" => true, "message" => "Manual refund recorded.", "status" => "success"];
    }

    private function processPayMongoRefund($refund, $payment)
    {
        $secretKey = $_ENV['PAYMONGO_SECRET_KEY'];
        $amount_cents = intval($refund['refund_amount'] * 100);

        $ch = curl_init("https://api.paymongo.com/v1/refunds");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Basic " . base64_encode($secretKey . ":")
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                "data" => [
                    "attributes" => [
                        "amount" => $amount_cents,
                        "payment" => $payment['payment_id'],
                        "reason" => $refund['refund_reason']
                    ]
                ]
            ])
        ]);

        $response = json_decode(curl_exec($ch), true);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->model->updateRefundStatus($refund['refund_id'], 'failed');
            return ["success" => false, "message" => $err, "status" => "failed"];
        }

        if (isset($response['data'])) {
            $gateway_transaction_id = $response['data']['id'];
            $this->gateway->createTransaction(
                $refund['refund_id'],
                "PayMongo",
                $gateway_transaction_id,
                "200",
                "Refund created",
                json_encode($response),
                'success'
            );

            $this->model->updateRefundStatus($refund['refund_id'], 'success');
            return ["success" => true, "message" => "Refund successful via PayMongo.", "status" => "success"];
        }

        $this->model->updateRefundStatus($refund['refund_id'], 'failed');
        return ["success" => false, "message" => "PayMongo refund failed.", "status" => "failed"];
    }

    private function processStripeRefund($refund, $payment)
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        try {
            $stripeRefund = \Stripe\Refund::create([
                'payment_intent' => $payment['stripe_payment_intent_id'],
                'amount' => intval($refund['amount'] * 100)
            ]);

            $this->gateway->createTransaction(
                $refund['refund_id'],
                "Stripe",
                $stripeRefund->id,
                "200",
                "Refund created",
                json_encode($stripeRefund),
                'success'
            );

            $this->model->updateRefundStatus($refund['refund_id'], 'success');
            return ["success" => true, "message" => "Refund successful via Stripe.", "status" => "success"];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->model->updateRefundStatus($refund['refund_id'], 'failed');
            return ["success" => false, "message" => $e->getMessage(), "status" => "failed"];
        }
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

    public function listRefundsByStatus($status)
    {
        return $this->model->getRefundsByStatus($status);
    }

    public function listAllRefunds()
    {
        return $this->model->getAllRefunds();
    }

    /**
     * Get all pending refund requests
     */
    public function listPendingRefunds()
    {
        return $this->model->getPendingRefunds();
    }

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
