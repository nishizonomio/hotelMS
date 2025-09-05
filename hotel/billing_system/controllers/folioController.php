<?php

require_once __DIR__.'/../models/folioTransactions.php';
require_once __DIR__.'/../config/database.php';

class FolioController {
    private $folioModel;

    public function __construct($conn) {
        $this->folioModel = new FolioTransactions($conn);
    }

    public function createFolioTransaction($invoice_id, $service_type, $description, $transaction_date, $transaction_time, $amount) {
        $folio = $this->folioModel->createFolioTransaction($invoice_id, $service_type, $description, $transaction_date, $transaction_time, $amount);
        if($folio) {
            return ["success" => true, "message" => "Folio transaction created successfully!"];
        } else {
            return ["success" => false, "message" => "Failed to create folio transaction!"];
        }
    }

    public function getFolioTransactionsById($transaction_id) {
        $transaction = $this->folioModel->getFolioTransactionsById($transaction_id);
        if($transaction) {
            return ["success" => true, "data" => $transaction];
        } else {
            return ["success" => false, "message" => "No transactions found!"];
        }
    }

    public function getFolioTransactionsByInvoiceId($invoice_id) {
        $transactions = $this->folioModel->getFolioTransactionsByInvoiceId($invoice_id);
        if($transactions) {
            return ["success" => true, "data" => $transactions];
        } else {
            return ["success" => false, "message" => "No transactions found for this invoice!"];
        }
    }

    public function deleteFolioTransaction($transaction_id) {
        $delete = $this->folioModel->deleteFolioTransaction($transaction_id);
        if($delete) {
            return ["success" => true, "message" => "Folio transaction deleted successfully!"];
        } else {
            return ["success" => false, "message" => "Failed to delete folio!"];
        }
    }

    public function updateFolioTransaction($transaction_id) {
        $update = $this->folioModel->updateFolioTransaction($transaction_id);
        if($update) {
            return ["success" => true, "message" => "Folio transaction updated!"];
        } else {
            return ["success" => false, "message" => "Error! Folio transaction not updated!"];
        }
    }
}