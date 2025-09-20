<?php
require_once __DIR__ . '/../db_connector/db_connect.php';

$db = new Database();  // Will use the main 'hotel' database
final class RoomStatus
{
    public int $room_id;
    public string $status;          // Available | Occupied | Cleaning | Maintenance
    public ?string $last_cleaned;   // Y-m-d or null
    public ?string $remarks;        // text or null
    public string $room_number;     // from rooms (or 'Pending')
    public string $room_type;       // from rooms (or 'Pending')

    public function __construct(array $row)
    {
        $this->room_id      = (int)$row['room_id'];
        $this->status       = $row['status'];
        $this->last_cleaned = $row['last_cleaned'] ?? null;
        $this->remarks      = $row['remarks'] ?? null;
        $this->room_number  = $row['room_number'];
        $this->room_type    = $row['room_type'];
    }
}

// ============== REPOSITORY (SQL ONLY) =============
final class RoomStatusRepository
{
    private mysqli $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    /** Fetch all rooms with housekeeping status + room info (left join) */
    public function getAll(): array
    {
        // Select from rooms and left-join housekeeping so newly created rooms
        // (which may not yet have a housekeeping row) are shown in the panel.
        $sql = "
            SELECT
                r.room_id AS room_id,
                COALESCE(hrs.status,
                    CASE
                        WHEN LOWER(r.status) = 'available' THEN 'Available'
                        WHEN LOWER(r.status) = 'occupied'  THEN 'Occupied'
                        ELSE 'Available'
                    END
                ) AS status,
                hrs.last_cleaned,
                hrs.remarks,
                COALESCE(r.room_number, 'Pending') AS room_number,
                COALESCE(r.room_type, 'Pending')   AS room_type
            FROM rooms r
            LEFT JOIN housekeeping_room_status hrs ON r.room_id = hrs.room_id
            ORDER BY
                CASE WHEN r.room_number IS NOT NULL THEN 0 ELSE 1 END,
                r.room_number + 0 ASC, r.room_number ASC, r.room_id ASC
        ";
        $res = $this->db->query($sql);
        if (!$res) { throw new RuntimeException("Query failed: ".$this->db->error); }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = new RoomStatus($row);
        }
        return $rows;
    }

    /** Count per status for summary/cards/chart */
    public function getCounts(): array
    {
        $defaults = [
            'Available'   => 0,
            'Occupied'    => 0,
            'Cleaning'    => 0,
            'Maintenance' => 0,
        ];
        // Count statuses across all rooms. If a room has no housekeeping row,
        // derive a sensible default from the rooms.status column.
        $sql = "
            SELECT resolved_status AS status, COUNT(*) AS total FROM (
                SELECT
                    r.room_id,
                    COALESCE(hrs.status,
                        CASE
                            WHEN LOWER(r.status) = 'available' THEN 'Available'
                            WHEN LOWER(r.status) = 'occupied'  THEN 'Occupied'
                            ELSE 'Available'
                        END
                    ) AS resolved_status
                FROM rooms r
                LEFT JOIN housekeeping_room_status hrs ON r.room_id = hrs.room_id
            ) t
            GROUP BY resolved_status
        ";
        $res = $this->db->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if (isset($defaults[$row['status']])) {
                    $defaults[$row['status']] = (int)$row['total'];
                }
            }
        }
        return $defaults;
    }

    /** Upsert a housekeeping row by room_id */
    public function upsert(int $room_id, string $status, ?string $remarks, ?string $last_cleaned): void
    {
        // Try UPDATE first
        $sql = "UPDATE housekeeping_room_status 
                SET status=?, remarks=?, last_cleaned=?, updated_at=NOW()
                WHERE room_id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssi", $status, $remarks, $last_cleaned, $room_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            // If no row exists yet, INSERT it
            $sql2 = "INSERT INTO housekeeping_room_status (room_id, status, remarks, last_cleaned, updated_at)
                     VALUES (?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE 
                        status=VALUES(status),
                        remarks=VALUES(remarks),
                        last_cleaned=VALUES(last_cleaned),
                        updated_at=NOW()";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bind_param("isss", $room_id, $status, $remarks, $last_cleaned);
            $stmt2->execute();
        }
    }
}

// ============== SERVICE (BUSINESS RULES) ==========
final class RoomStatusService
{
    private RoomStatusRepository $repo;
    /** Housekeeping statuses we allow */
    private array $allowedStatuses = ['Available','Occupied','Cleaning','Maintenance'];

    public function __construct(RoomStatusRepository $repo) { $this->repo = $repo; }

    public function list(): array
    {
        return $this->repo->getAll();
    }

    public function counts(): array
    {
        return $this->repo->getCounts();
    }

    public function update(int $room_id, string $status, ?string $remarks, ?string $last_cleaned): void
    {
        if (!in_array($status, $this->allowedStatuses, true)) {
            throw new InvalidArgumentException("Invalid status value.");
        }
        // Basic date validation (YYYY-MM-DD) or null
        if ($last_cleaned !== null && $last_cleaned !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $last_cleaned)) {
            throw new InvalidArgumentException("Invalid date format for last_cleaned.");
        }
        $last_cleaned = $last_cleaned ?: null;
        $remarks = ($remarks !== null && $remarks !== '') ? $remarks : null;

        $this->repo->upsert($room_id, $status, $remarks, $last_cleaned);
    }
}

// ================= CONTROLLER =====================
// Resolve a mysqli connection safely. Prefer $db->getConnection(), fall back to
// global $conn created by the connector, or create a new Database as last resort.
$mysqli = null;
if (isset($db) && is_object($db) && method_exists($db, 'getConnection')) {
    try {
        $mysqli = $db->getConnection();
    } catch (Throwable $e) {
        // swallow and allow fallbacks
        $mysqli = null;
    }
}

if (!$mysqli instanceof mysqli) {
    if (isset($conn) && $conn instanceof mysqli) {
        $mysqli = $conn;
    } else {
        // Try to create a new Database instance and get connection
        try {
            $db = new Database();
            $mysqli = $db->getConnection();
        } catch (Throwable $e) {
            // Fatal: cannot proceed without DB
            die('Database connection unavailable: ' . $e->getMessage());
        }
    }
}

$repo    = new RoomStatusRepository($mysqli);
$service = new RoomStatusService($repo);

// Handle POST (update from form)
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    try {
        $room_id      = (int)($_POST['room_id'] ?? 0);
        $status       = trim($_POST['status'] ?? '');
        $remarks      = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;
        $last_cleaned = isset($_POST['last_cleaned']) ? trim($_POST['last_cleaned']) : null;

        $service->update($room_id, $status, $remarks, $last_cleaned);
        $flash = ['type' => 'success', 'msg' => 'Room status updated successfully.'];
    } catch (Throwable $e) {
        $flash = ['type' => 'error', 'msg' => $e->getMessage()];
    }
}

// Fetch data for view
try {
    $rooms        = $service->list();
    $statusCounts = $service->counts();
} catch (Throwable $e) {
    die("Error loading data: ".$e->getMessage());
}
