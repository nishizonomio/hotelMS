<?php
require_once __DIR__ . '/../config/database.php';

class POSChargesHelper
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Sync all POS tables into pos_charges
     */
    public function syncAllPOSCharges($guest_id = null)
    {

        $this->syncLounge($guest_id);
        $this->syncGiftShop($guest_id);
        $this->syncRoomDining($guest_id);
    }

    /**
     * Get all unpaid charges for a guest
     */
    public function getUnpaidChargesByGuest($guest_id)
    {
        $sql = "SELECT * FROM pos_charges 
                WHERE guest_id = ? AND status = 'unpaid'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $guest_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $charges = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $charges;
    }


    public function attachChargesToInvoice($guest_id, $invoice_id)
    {
        $sql = "UPDATE pos_charges 
                SET invoice_id = ?, status = 'paid' 
                WHERE guest_id = ? AND status = 'unpaid'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $invoice_id, $guest_id);
        $stmt->execute();
        $stmt->close();
    }

    private function insertIfNotExists($guest_id, $module, $source_id, $description, $amount, $charge_date)
    {

        $check = $this->conn->prepare(
            "SELECT pos_charge_id FROM pos_charges WHERE source_module = ? AND source_id = ?"
        );
        $check->bind_param("si", $module, $source_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            return;
        }
        $check->close();

        $stmt = $this->conn->prepare(
            "INSERT INTO pos_charges (guest_id, source_module, source_id, description, total_amount, charge_date) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isisss", $guest_id, $module, $source_id, $description, $amount, $charge_date);
        $stmt->execute();
        $stmt->close();
    }

    /* ===================== POS SOURCE SYNC METHODS ===================== */

    private function syncLounge($guest_id = null)
    {
        $sql = "SELECT order_id, guest_id, total_amount, order_date FROM lounge_orders";
        if ($guest_id) {
            $sql .= " WHERE guest_id = " . intval($guest_id);
        }

        $result = $this->conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $desc = "Lounge Order #" . $row['order_id'];
            $this->insertIfNotExists(
                $row['guest_id'],
                'Lounge',
                $row['order_id'],
                $desc,
                $row['total_amount'],
                $row['order_date']
            );
        }
    }

    private function syncGiftShop($guest_id = null)
    {
        $sql = "SELECT sale_id, guest_id, total_amount, sale_date FROM giftshop_sales";
        if ($guest_id) {
            $sql .= " WHERE guest_id = " . intval($guest_id);
        }

        $result = $this->conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $desc = "Gift Shop Sale #" . $row['sale_id'];
            $this->insertIfNotExists(
                $row['guest_id'],
                'GiftShop',
                $row['sale_id'],
                $desc,
                $row['total_amount'],
                $row['sale_date']
            );
        }
    }

    private function syncRoomDining($guest_id = null)
    {
        $sql = "SELECT order_id, guest_id, total_amount, order_date FROM room_dining_orders";
        if ($guest_id) {
            $sql .= " WHERE guest_id = " . intval($guest_id);
        }

        $result = $this->conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $desc = "Room Dining Order #" . $row['order_id'];
            $this->insertIfNotExists(
                $row['guest_id'],
                'RoomDining',
                $row['order_id'],
                $desc,
                $row['total_amount'],
                $row['order_date']
            );
        }
    }
}
