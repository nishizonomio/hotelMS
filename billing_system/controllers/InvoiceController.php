<?php

require_once __DIR__ . '/../models/invoice.php';
require_once __DIR__ . '/../config/database.php';

class InvoiceController
{
    private $invoiceModel;

    public function __construct()
    {
        $this->invoiceModel = new Invoice(); // model handles DB inside
    }

    /**
     * Create a new invoice by booking ID (auto-calculates room + POS charges).
     */
    public function createInvoice($booking_id, $status = 'unpaid')
    {
        try {
            $invoice_id = $this->invoiceModel->generateInvoiceFromBooking($booking_id, $status);
            return [
                "success" => true,
                "message" => "Invoice created successfully!",
                "invoice_id" => $invoice_id
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Failed to create invoice: " . $e->getMessage()
            ];
        }
    }

    public function getActiveBookings()
    {
        return $this->invoiceModel->getActiveBookings();
    }

    /**
     * Get a single invoice by ID.
     */
    public function getInvoiceById($invoice_id)
    {
        try {
            $result = $this->invoiceModel->getInvoiceById($invoice_id);
            if ($result) {
                return ["success" => true, "data" => $result];
            }
            return ["success" => false, "message" => "Invoice not found."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Get invoices filtered by status.
     */
    public function getInvoicesByStatus($status)
    {
        try {
            $result = $this->invoiceModel->getInvoicesByStatus($status);
            return ["success" => true, "data" => $result];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Get total count of invoices.
     */
    public function getUnpaidInvoiceCount()
    {
        try {
            return ["success" => true, "data" => $this->invoiceModel->getUnpaidInvoiceCount()];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Get all invoices.
     */
    public function getAllInvoices()
    {
        try {
            $result = $this->invoiceModel->getAllInvoices();
            return ["success" => true, "data" => $result];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function updateInvoiceStatusFromPayments($invoice_id)
    {
        $paymentModel = new Payments();
        $totalPaid = $paymentModel->getTotalPaidByInvoice($invoice_id);

        $invoiceTotal = $this->invoiceModel->getInvoiceTotal($invoice_id);

        $newStatus = "unpaid";
        if ($totalPaid >= $invoiceTotal) {
            $newStatus = "paid";
        } elseif ($totalPaid > 0 && $totalPaid < $invoiceTotal) {
            $newStatus = "partial";
        }

        return $this->updateInvoiceStatus($invoice_id, $newStatus);
    }


    /**
     * Update the status of an invoice.
     */
    public function updateInvoiceStatus($invoice_id, $status)
    {
        try {
            $result = $this->invoiceModel->updateInvoiceStatus($invoice_id, $status);
            if ($result) {
                return ["success" => true, "message" => "Invoice status updated successfully!"];
            }
            return ["success" => false, "message" => "Invoice status update failed!"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Delete an invoice by ID.
     */
    public function deleteInvoice($invoice_id)
    {
        try {
            $result = $this->invoiceModel->deleteInvoice($invoice_id);
            if ($result) {
                return ["success" => true, "message" => "Invoice deleted successfully!"];
            }
            return ["success" => false, "message" => "Failed to delete invoice!"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
