<?php
// controllers/PaymentController.php

require_once __DIR__ . '/../models/paymentGateway.php';
require_once __DIR__ . '/../models/payment.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/InvoiceController.php';
require_once __DIR__ . '/../config/env.php';


class PaymentController
{
    private $gateway;
    private $model;
    private $conn;
    private $invoiceController;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->model = new Payments(); // model creates its own connection
        $this->invoiceController = new InvoiceController();
        $this->gateway = new PaymentGatewayTransactions();
    }


    public function recordPayment($invoice_id, $group_billing_id, $payment_method, $amount_paid)
    {
        // 1. Get total invoice amount
        $invoiceModel = new Invoice();
        $invoiceTotal = $invoiceModel->getInvoiceTotal($invoice_id);

        // 2. Get total already paid
        $totalPaid = $this->model->getTotalPaidByInvoice($invoice_id);

        // 3. Compute remaining balance
        $remainingBalance = $invoiceTotal - $totalPaid;

        // 4. Validate input
        if ($amount_paid > $remainingBalance) {
            return [
                "success" => false,
                "message" => "❌ Payment exceeds remaining balance. Amount due is ₱" . number_format($remainingBalance, 2)
            ];
        }
        $paymentResult = $this->model->createPayment(
            $invoice_id,
            $amount_paid,
            $payment_method,
            $group_billing_id,
            'succeeded' // status
        );

        if (!$paymentResult['success']) {
            return [
                "success" => false,
                "message" => "Failed to record payment.",
                "error"   => $paymentResult['error'] ?? null
            ];
        }

        // Update invoice status after payment
        $this->invoiceController->updateInvoiceStatusFromPayments($invoice_id);

        return [
            "success" => true,
            "message" => "Payment recorded successfully and invoice updated.",
            "payment_id" => $paymentResult['payment_id']
        ];
    }

    public function retryPayment($payment_id, $method, $success_url, $cancel_url, $status)
    {
        // 1. Ask the model for the cancelled payment
        $oldPayment = $this->model->getCancelledPaymentsById($payment_id);

        if (!$oldPayment) {
            return ["success" => false, "message" => "Payment not found or not cancelled."];
        }

        $invoice_id = $oldPayment['invoice_id'];
        $amount     = $oldPayment['amount_paid'];

        // 2. Create a new payment entry
        $newPayment = $this->model->createPayment(
            $invoice_id,
            $amount,
            $method,
            $oldPayment['group_billing_id'] ?? null,
            $oldPayment['gateway_name'] ?? null,
            null,
            $status
        );

        // 3. Process via gateway
        if (in_array($method, ['gcash', 'paymaya'])) {
            return $this->createPayMongoCheckoutSession($invoice_id, $amount, $method, $success_url, $cancel_url, $status);
        } elseif (in_array($method, ['credit_card', 'debit_card'])) {
            return $this->createStripeCheckoutSession($invoice_id, $amount, $method, $success_url, $cancel_url, $status);
        }

        return ["success" => false, "message" => "Unsupported method: $method"];
    }



    public function processOnlinePayment($invoice_id, $amount_paid, $payment_method, $success_url, $cancel_url, $status)
    {

        $success_url .= (strpos($success_url, '?') === false ? '?' : '&') . "invoice_id=" . $invoice_id;
        $cancel_url  .= (strpos($cancel_url, '?') === false ? '?' : '&') . "invoice_id=" . $invoice_id;
        if (in_array($payment_method, ['gcash'])) {
            return $this->createPayMongoCheckoutSession($invoice_id, $amount_paid, $payment_method, $success_url, $cancel_url, $status);
        } elseif (in_array($payment_method, ['credit_card', 'debit_card'])) {
            return $this->createStripeCheckoutSession($invoice_id, $amount_paid, $payment_method, $success_url, $cancel_url, $status);
        }
        return ["success" => false, "message" => "Unsupported method: $payment_method"];
    }

    private function createPayMongoCheckoutSession($invoice_id, $amount_paid, $payment_method, $success_url, $cancel_url, $status)
    {
        $secretKey = $_ENV['PAYMONGO_SECRET_KEY'];
        $amount_cents = $amount_paid * 100;

        $payment = $this->model->createPayment(
            $invoice_id,
            $amount_paid,
            $payment_method,
            null,
            'pending'
        );
        $payment_id = $payment['payment_id'];

        $ch = curl_init("https://api.paymongo.com/v1/checkout_sessions");
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
                        "line_items" => [[
                            "name" => "Invoice #$invoice_id",
                            "quantity" => 1,
                            "amount" => $amount_cents,
                            "currency" => "PHP"
                        ]],
                        "payment_method_types" => ["gcash", "paymaya", "card", "dob"],
                        "success_url" => $success_url,
                        "cancel_url" => $cancel_url
                    ]
                ]
            ])
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);


        $gateway_transaction_id = $response['data']['id'] ?? null;
        $checkout_url = $response['data']['attributes']['checkout_url'] ?? null;

        $this->gateway->createTransaction(
            $payment_id,
            "PayMongo",
            $gateway_transaction_id,
            "200",
            "Checkout created",
            json_encode($response),
            $status
        );



        return ["success" => (bool)$checkout_url, "checkout_url" => $checkout_url];
    }

    private function createStripeCheckoutSession($invoice_id, $amount, $payment_method, $success_url, $cancel_url, $status)
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']); // move to env later

        $amount_cents = $amount * 100;

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'php',
                    'product_data' => ['name' => "Invoice #$invoice_id"],
                    'unit_amount' => $amount_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
        ]);

        if (empty($session->url)) {
            return [
                "success" => false,
                "message" => "Failed to create Stripe checkout session.",
                "debug"   => json_encode($session)
            ];
        }

        $payment = $this->model->createPayment(
            $invoice_id,
            $amount,
            $payment_method,
            null,
            'pending'
        );

        $this->gateway->createTransaction(
            $payment['payment_id'],
            "Stripe",
            $session->id,
            "200",
            "Checkout created",
            json_encode($session),
            $status
        );

        if (empty($session->url)) {
            return ["success" => false, "message" => "Failed to create Stripe checkout session."];
        }


        return ["success" => true, "checkout_url" => $session->url];
    }

    public function updatePaymentStatusByInvoice($invoice_id, $status)
    {
        return $this->model->updatePaymentStatusByInvoice($invoice_id, $status);
    }

    public function getPaymentByStatus($status)
    {
        return $this->model->getPaymentByStatus($status);
    }

    // controllers/PaymentController.php
    public function getRecentPayments($limit = 5)
    {
        return $this->model->getRecentPayments($limit = 5);
    }


    // Fetch payments by invoice
    public function getPaymentsByInvoiceId($invoice_id)
    {
        return $this->model->getPaymentsByInvoiceId($invoice_id);
    }

    public function getPaymentsById($payment_id)
    {
        return $this->model->getPaymentsById($payment_id);
    }

    public function getAllPayments()
    {
        return $this->model->getAllPayments();
    }

    public function getTotalPaidByInvoice($invoice_id)
    {
        return $this->model->getTotalPaidByInvoice($invoice_id);
    }

    // Fetch payments by guest
    public function getPaymentsByGuest($guestId)
    {
        return $this->model->getByGuest($guestId);
    }
}
