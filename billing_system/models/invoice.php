<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/POSChargeHelper.php';


class Invoice
{
    private $conn;
    private $posHelper;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->posHelper = new POSChargesHelper();
    }

    /** 
     * Create invoice manually (not auto-summing) 
     */
    public function createInvoice($booking_id, $total_amount, $status)
    {
        $invoice_date = date('Y-m-d');
        $invoice_time = date('H:i:s');

        $stmt = $this->conn->prepare("
            INSERT INTO invoices (booking_id, invoice_date, invoice_time, total_amount, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }

        $stmt->bind_param("issds", $booking_id, $invoice_date, $invoice_time, $total_amount, $status);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /** 
     * Generate invoice automatically from booking 
     * Adds room price + all unbilled POS charges for guest 
     */
    // public function generateInvoiceFromBooking($booking_id, $status = 'unpaid')
    // {
    //     // Get guest and room
    //     $stmt = $this->conn->prepare("SELECT guest_id, room_id FROM bookings WHERE booking_id = ?");
    //     $stmt->bind_param("i", $booking_id);
    //     $stmt->execute();
    //     $stmt->bind_result($guest_id, $room_id);
    //     $stmt->fetch();
    //     $stmt->close();

    //     if (!$guest_id) {
    //         throw new Exception("No guest found for booking ID $booking_id");
    //     }

    //     // 🔹 Sync POS charges before calculating totals
    //     $this->posHelper->syncAllPOSCharges($guest_id);

    //     // Get unbilled POS charges
    //     $stmt = $this->conn->prepare("
    //     SELECT COALESCE(SUM(total_amount),0) 
    //     AS pos_total
    //     FROM pos_charges 
    //     WHERE guest_id = ? AND invoice_id IS NULL
    // ");
    //     $stmt->bind_param("i", $guest_id);
    //     $stmt->execute();
    //     $pos_total = $stmt->get_result()->fetch_assoc()['pos_total'] ?? 0;
    //     $stmt->close();

    //     // Get room price
    //     $stmt = $this->conn->prepare("SELECT room_price FROM rooms WHERE room_id = ?");
    //     $stmt->bind_param("i", $room_id);
    //     $stmt->execute();
    //     $room_price = $stmt->get_result()->fetch_assoc()['room_price'] ?? 0;
    //     $stmt->close();

    //     $total_amount = $room_price + $pos_total;

    //     // Insert invoice
    //     $stmt = $this->conn->prepare("
    //     INSERT INTO invoices (booking_id, invoice_date, invoice_time, total_amount, status) 
    //     VALUES (?, CURDATE(), CURTIME(), ?, ?)
    // ");
    //     $stmt->bind_param("ids", $booking_id, $total_amount, $status);
    //     $stmt->execute();
    //     $invoice_id = $stmt->insert_id;
    //     $stmt->close();

    //     // Update POS charges with this invoice
    //     $stmt = $this->conn->prepare("
    //     UPDATE pos_charges SET invoice_id = ? 
    //     WHERE guest_id = ? AND invoice_id IS NULL
    // ");
    //     $stmt->bind_param("ii", $invoice_id, $guest_id);
    //     $stmt->execute();
    //     $stmt->close();

    //     return $invoice_id;
    // }

    public function generateInvoiceFromBooking($booking_id, $status = 'unpaid')
    {
        // Step 1: Get guest_id and room_id
        $stmt = $this->conn->prepare("SELECT guest_id, room_id FROM bookings WHERE booking_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed (bookings): " . $this->conn->error);
        }
        $stmt->bind_param("i", $booking_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed (bookings): " . $stmt->error);
        }
        $stmt->bind_result($guest_id, $room_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            throw new Exception("No booking found with booking_id = $booking_id");
        }
        $stmt->close();

        if (!$guest_id) {
            throw new Exception("No guest found for booking ID $booking_id");
        }

        // Step 2: Sync POS charges
        try {
            $this->posHelper->syncAllPOSCharges($guest_id);
        } catch (Exception $e) {
            throw new Exception("POS sync failed: " . $e->getMessage());
        }

        // Step 3: Get unbilled POS charges
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(total_amount),0) AS pos_total FROM pos_charges WHERE guest_id = ? AND invoice_id IS NULL");
        if (!$stmt) {
            throw new Exception("Prepare failed (pos_charges): " . $this->conn->error);
        }
        $stmt->bind_param("i", $guest_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed (pos_charges): " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Get result failed (pos_charges): " . $stmt->error);
        }
        $pos_total = $result->fetch_assoc()['pos_total'] ?? 0;
        $stmt->close();

        // Step 4: Get room price
        $stmt = $this->conn->prepare("SELECT room_price FROM rooms WHERE room_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed (rooms): " . $this->conn->error);
        }
        $stmt->bind_param("i", $room_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed (rooms): " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Get result failed (rooms): " . $stmt->error);
        }
        $room_price = $result->fetch_assoc()['room_price'] ?? null;
        $stmt->close();

        if ($room_price === null) {
            throw new Exception("Room ID $room_id not found in rooms table");
        }

        // Step 5: Calculate total
        $total_amount = $room_price + $pos_total;

        // Step 6: Insert invoice
        $stmt = $this->conn->prepare("INSERT INTO invoices (booking_id, invoice_date, invoice_time, total_amount, status) VALUES (?, CURDATE(), CURTIME(), ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed (insert invoice): " . $this->conn->error);
        }
        $stmt->bind_param("ids", $booking_id, $total_amount, $status);
        if (!$stmt->execute()) {
            throw new Exception("Invoice insert failed: " . $stmt->error);
        }
        $invoice_id = $stmt->insert_id;
        $stmt->close();

        // Step 7: Update POS charges with invoice_id
        $stmt = $this->conn->prepare("UPDATE pos_charges SET invoice_id = ? WHERE guest_id = ? AND invoice_id IS NULL");
        if (!$stmt) {
            throw new Exception("Prepare failed (update POS): " . $this->conn->error);
        }
        $stmt->bind_param("ii", $invoice_id, $guest_id);
        if (!$stmt->execute()) {
            throw new Exception("POS update failed: " . $stmt->error);
        }
        $stmt->close();

        return $invoice_id;
    }



    public function getInvoiceById($invoice_id)
    {
        $stmt = $this->conn->prepare("
            SELECT i.*, CONCAT(g.first_name, ' ', g.last_name) AS guest_name
            FROM invoices i
            LEFT JOIN bookings b ON i.booking_id = b.booking_id
            LEFT JOIN guests g ON b.guest_id = g.guest_id
            WHERE i.invoice_id = ?
        ");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getActiveBookings()
    {
        $sql = "SELECT b.booking_id, CONCAT(g.first_name,' ',g.last_name) AS guest_name, r.room_number
                FROM bookings b
                JOIN guests g ON b.guest_id = g.guest_id
                JOIN rooms r ON b.room_id = r.room_id
                WHERE b.status IN ('confirmed','checked_in')";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInvoicesByStatus($status)
    {
        $stmt = $this->conn->prepare("
            SELECT i.*, CONCAT(g.first_name, ' ', g.last_name) AS guest_name
            FROM invoices i
            LEFT JOIN bookings b ON i.booking_id = b.booking_id
            LEFT JOIN guests g ON b.guest_id = g.guest_id
            WHERE i.status = ?
            ORDER BY i.invoice_id DESC
        ");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getBookingById($booking_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getUnpaidInvoiceCount()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM invoices WHERE status = 'unpaid'");
        $stmt->execute();
        $result =  $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ['total' => $result['total']];
    }

    public function getAllInvoices()
    {
        $stmt = $this->conn->prepare("
            SELECT i.*, CONCAT(g.first_name, ' ', g.last_name) AS guest_name
            FROM invoices i
            LEFT JOIN bookings b ON i.booking_id = b.booking_id
            LEFT JOIN guests g ON b.guest_id = g.guest_id
            ORDER BY i.invoice_id DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getInvoiceTotal($invoice_id)
    {
        $stmt = $this->conn->prepare("SELECT total_amount FROM invoices WHERE invoice_id = ?");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['total_amount'] ?? 0;
    }


    public function updateInvoiceStatus($invoice_id, $status)
    {
        $stmt = $this->conn->prepare("UPDATE invoices SET status = ? WHERE invoice_id = ?");
        $stmt->bind_param("si", $status, $invoice_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteInvoice($invoice_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM invoices WHERE invoice_id = ?");
        $stmt->bind_param("i", $invoice_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
