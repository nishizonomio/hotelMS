<?php

require_once __DIR__.'/../models/invoice.php';
require_once __DIR__.'/../config/database.php';

class InvoiceController {
    private $invoiceModel;
    private $message = "Invoice created successfully!";
    private $message1 = "Failed to create invoice!";
    private $message2 = "Invoice does not exits!";

    public function __construct($db){
        $this->invoiceModel = new Invoice($db);
    }

    public function createInvoice($booking_id, $invoice_date, $invoice_time, $total_amount, $status) {
        $result = $this->invoiceModel->createInvoice($booking_id, $invoice_date, $invoice_time, $total_amount, $status);
        if($result) {
            return ["success" => true, "message" => $this->message];
        } else {
            return ["success" => false, "message" => $this->message1];
        }
    }

    public function getInvoiceById($invoice_id) {
        $result = $this->invoiceModel->getInvoiceById($invoice_id);
        if($result) {
            return ["success" => true, "data" => $result];
        } else {
            return ["success" => false, "message" => $this->message2];
        }
    }

    public function updateInvoiceStatus($invoice_id, $status) {
        $result = $this->invoiceModel->updateInvoiceStatus($invoice_id, $status);
        if($result) {
            return ["success" => true, "message" =>"Invoice status updated successfully!"];
        } else {
            return ["success" => false, "message" => "Errror! Invoice status update failed!"];
        }
    }

    public function deleteInvoice($invoice_id) {
        $result = $this->invoiceModel->deleteInvoice($invoice_id);
        if($result) {
            return ["success" => true, "message" => "Invoice deleted!"];
        } else {
            return ["success" => false, "message" => "Error! Invoice not deleted!"];
        }
    }
}