<?php
// Include the database connection file.
include '../../db_connect.php';

// Initialize filters
$where_clauses = [];
$params = [];
$param_types = '';

// Handle search and filter parameters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $where_clauses[] = '(g.first_name LIKE ? OR g.last_name LIKE ? OR ro.room_number LIKE ?)';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $param_types .= 'sss';
    }
    
    if (!empty($_GET['status'])) {
        $where_clauses[] = 'r.status = ?';
        $params[] = $_GET['status'];
        $param_types .= 's';
    }
    
    if (!empty($_GET['date_from'])) {
        $where_clauses[] = 'r.check_in >= ?';
        $params[] = $_GET['date_from'];
        $param_types .= 's';
    }
    
    if (!empty($_GET['date_to'])) {
        $where_clauses[] = 'r.check_out <= ?';
        $params[] = $_GET['date_to'];
        $param_types .= 's';
    }
}

// Build the SQL query
$sql = "SELECT r.reservation_id, g.first_name, g.last_name, ro.room_number, r.status, 
        r.check_in, r.check_out, r.remarks
        FROM reservations r
        JOIN guests g ON r.guest_id = g.guest_id
        JOIN rooms ro ON r.room_id = ro.room_id";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY r.check_in DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all unique statuses for the filter dropdown
$status_sql = "SELECT DISTINCT status FROM reservations";
$status_result = $conn->query($status_sql);
$statuses = [];
while ($status_row = $status_result->fetch_assoc()) {
    $statuses[] = $status_row['status'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Reservations</title>
    <link rel="stylesheet" href="../reservation_css/back_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background-image: url('../reservation_img/reservation_background.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 0;
        }

        .main-container {
            position: relative;
            z-index: 1;
            width: 95%;
            max-width: 1400px;
            background: rgba(0, 0, 0, 0.75);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin: 20px;
        }

        h2 {
            text-align: center;
            color: #FFD700;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .search-filters {
            background: rgba(255, 215, 0, 0.1);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .search-filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            color: #ffd700;
            font-size: 0.9em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #1c1c1c;
            border-radius: 4px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #333;
            padding: 12px;
            text-align: left;
            color: #fff;
        }

        th {
            background-color: #242424;
            color: #ffd700;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #242424;
        }

        .status-pending { color: #ffc107; }
        .status-confirmed { color: #3498db; }
        .status-checked_in { color: #2ecc71; }
        .status-checked_out { color: #e74c3c; }

        .search-button {
            background: #ffd700;
            color: #1c1c1c;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background: #fff;
            transform: translateY(-2px);
        }

        .clear-filters {
            background: transparent;
            border: 1px solid #ffd700;
            color: #ffd700;
        }

        .clear-filters:hover {
            background: #ffd700;
            color: #1c1c1c;
        }

        .button-group {
            display: flex;
            gap: 15px;
        }

        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.2);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 15px;
            overflow: hidden;
        }

        th, td {
            padding: 15px;
            text-align: left;
            color: #fff;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        }

        th {
            background: rgba(0, 0, 0, 0.3);
            color: #FFD700;
            font-weight: 600;
            font-size: 0.95em;
            text-transform: uppercase;
        }

        tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .search-button {
            background: rgba(255, 215, 0, 0.9);
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .search-button:hover {
            background: #FFD700;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
        }

        .clear-filters {
            background: transparent;
            border: 1px solid #FFD700;
            color: #FFD700;
        }

        .clear-filters:hover {
            background: rgba(255, 215, 0, 0.15);
            color: #FFD700;
        }

        @media (max-width: 1200px) {
            body {
                align-items: flex-start;
                padding: 20px 0;
            }
            .main-container {
                margin: 0 20px;
            }
        }

        @media (max-width: 768px) {
            .search-filters form {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .main-container {
                padding: 20px;
                margin: 10px;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<a href="../reservation.php" class="back-button">
    <img src="../reservation_img/back_icon.png" alt="Back">
</a>

<div class="main-container">
    <h2>All Reservations</h2>
    
    <div class="search-filters">
        <form method="GET" action="">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" placeholder="Search guest name or room..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>

            <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo (isset($_GET['status']) && $_GET['status'] === $status) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" 
                       value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
            </div>

            <div class="filter-group">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to"
                       value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
            </div>

            <div class="filter-group">
                <div class="button-group">
                    <button type="submit" class="search-button">Search</button>
                    <button type="button" class="search-button clear-filters" onclick="window.location.href='view_reservations.php'">Clear Filters</button>
                </div>
            </div>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Reservation ID</th>
                <th>Guest Name</th>
                <th>Room Number</th>
                <th>Status</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Dynamically apply a class based on the reservation status for styling
                    $status_class = "status-" . str_replace(' ', '_', strtolower($row["status"]));
                    
                    echo "<tr>";
                    echo "<td>" . $row["reservation_id"] . "</td>";
                    echo "<td>" . $row["first_name"] . " " . $row["last_name"] . "</td>";
                    echo "<td>" . $row["room_number"] . "</td>";
                    echo "<td class='" . $status_class . "'>" . $row["status"] . "</td>";
                    echo "<td>" . $row["check_in"] . "</td>";
                    echo "<td>" . $row["check_out"] . "</td>";
                    echo "<td>" . $row["remarks"] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No reservations found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for date_to based on date_from
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    dateFrom.addEventListener('change', function() {
        dateTo.min = this.value;
    });

    dateTo.addEventListener('change', function() {
        dateFrom.max = this.value;
    });
});
</script>

</body>
</html>