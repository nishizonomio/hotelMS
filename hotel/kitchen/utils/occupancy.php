<?php
$conn = new mysqli("localhost", "root", "", "hotel");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$total_query = "SELECT COUNT(*) as total FROM room_occupancy";
$total_result = $conn->query($total_query);
$total_rooms = $total_result->fetch_assoc()['total'] ?? 1;

$status_query = "SELECT occupancy_status, COUNT(*) as count FROM room_occupancy GROUP BY occupancy_status";
$status_result = $conn->query($status_query);

$labels = [];
$data = [];
$raw_counts = [];
// Pie chart colors
$pie_colors = ["#4CAF50", "#F44336", "#2196F3", "#FF9800"];
$status_label_colors = [
    'available' => "#4CAF50",    // green
    'occupied' => "#F44336",     // red
    'maintenance' => "#2196F3",  // blue
    'reserved' => "#FF9800"      // orange
];
$color_map = [];
$index = 0;

while ($row = $status_result->fetch_assoc()) {
    $status = strtolower($row['occupancy_status']);
    $count = $row['count'];
    $percentage = ($count / $total_rooms) * 100;
    $labels[] = "($count)";
    $data[] = round($percentage, 1);
    $color_map[$status] = $status_label_colors[$status] ?? $pie_colors[$index % count($pie_colors)];
    $raw_counts[$status] = $count;
    $index++;
}

$show_table = isset($_POST['show_table']) || isset($_POST['search']);
$table_rows = [];

if ($show_table) {
    $conditions = [];

    if (!empty($_POST['room_number'])) {
        $room_number = $conn->real_escape_string($_POST['room_number']);
        $conditions[] = "room_number LIKE '%$room_number%'";
    }

    if (!empty($_POST['room_type'])) {
        $room_type = $conn->real_escape_string($_POST['room_type']);
        $conditions[] = "room_type LIKE '%$room_type%'";
    }

    if (!empty($_POST['occupancy_status'])) {
        $occupancy_status = $conn->real_escape_string($_POST['occupancy_status']);
        $conditions[] = "occupancy_status LIKE '%$occupancy_status%'";
    }

    if (!empty($_POST['guest_name'])) {
        $guest_name = $conn->real_escape_string($_POST['guest_name']);
        $conditions[] = "guest_name LIKE '%$guest_name%'";
    }

    $where_clause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
    $table_query = "SELECT id, room_number, room_type, floor, price, occupancy_status, guest_name, check_in_date, check_out_date, created_at FROM room_occupancy $where_clause";
    $table_result = $conn->query($table_query);

    while ($row = $table_result->fetch_assoc()) {
        $table_rows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Occupancy Report - Hotel La Vista</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <style>
        body, html {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('hotel.jfif') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.88);
            min-height: 100vh;
            padding: 40px 20px;
            color: #fff;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
            font-weight: 600;
        }

        .chart-section {
            display: flex;
            justify-content: center;
            gap: 50px;
            flex-wrap: wrap;
        }

        .chart-container {
            width: 340px;
            height: 340px;
            background-color: #1f1f1f;
            padding: 20px;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0 25px rgba(0,0,0,0.7);
        }

        canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .legend-box {
            background: #1f1f1f;
            padding: 20px;
            border-radius: 16px;
            max-width: 300px;
            height: fit-content;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 4px;
        }

        .total-count {
            text-align: center;
            font-size: 18px;
            margin-top: 15px;
        }

        .button-group {
            text-align: center;
            margin-top: 30px;
        }

        button {
            padding: 12px 24px;
            background-color: #FF9800;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #e67e22;
        }

        form.search-form {
            margin: 30px auto 10px;
            width: 95%;
            text-align: center;
        }

        .search-form input {
            padding: 8px 12px;
            margin: 4px;
            width: 180px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
        }

        .search-form button {
            padding: 8px 16px;
            margin-left: 8px;
        }

        table {
            margin: 20px auto;
            border-collapse: separate;
            border-spacing: 0;
            width: 95%;
            background-color: #23272f;
            color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 18px rgba(0,0,0,0.15);
            opacity: 0.85;
        }
        th, td {
            padding: 14px 12px;
            text-align: center;
            font-size: 15px;
            border: none;
        }
        th {
            background-color: #303642;
            font-weight: 700;
            font-size: 16px;
            color: #FF9800;
        }
        tr:hover td {
            background-color: #2e3440;
            transition: background 0.2s;
        }

        /* Status font colors */
        .status-available   { color: #4CAF50; font-weight: bold; background: transparent !important; }
        .status-occupied    { color: #F44336; font-weight: bold; background: transparent !important; }
        .status-maintenance { color: #2196F3; font-weight: bold; background: transparent !important; }
        .status-reserved    { color: #FF9800; font-weight: bold; background: transparent !important; }

        @media (max-width: 900px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead {
                display: none;
            }
            tr {
                background: #222;
                margin-bottom: 10px;
                border-radius: 12px;
                box-shadow: 0 1px 6px rgba(0,0,0,0.08);
            }
            td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }
            td:before {
                position: absolute;
                left: 16px;
                top: 16px;
                white-space: nowrap;
                font-weight: bold;
                color: #FF9800;
                content: attr(data-label);
                font-size: 14px;
                text-align: left;
            }
        }

        #roomModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: transparent;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        #roomModal .modal-content {
            background: none;
            padding: 0;
            border-radius: 0;
            max-width: 95vw;
            max-height: 90vh;
            overflow: auto;
            color: #fff;
            position: relative;
            pointer-events: all;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        #roomModal .close-btn {
            display: none;
        }
        #roomModal table {
            margin: 0;
            background: #000;
            opacity: 1;
            color: #fff;
            box-shadow: 0 2px 18px rgba(0,0,0,0.15);
        }
        #roomModal th, #roomModal td {
            border: none;
            background: #000;
        }
        #roomModal tr:hover td {
            background-color: #222;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <h1>Room Occupancy Report</h1>

        <div class="chart-section">
            <div class="chart-container">
                <canvas id="occupancyChart"></canvas>
            </div>

            <div class="legend-box">
                <h3 style="margin-bottom: 15px; font-size: 20px;">Legend</h3>
                <?php foreach ($labels as $i => $label): ?>
                    <?php
                        $statuses = array_keys($color_map);
                        $status = $statuses[$i];
                        $count = $raw_counts[$status];
                    ?>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: <?= $color_map[$status] ?>"></div>
                        <span class="status-<?= strtolower($status) ?>"><?= strtolower($status) ?></span>
                        — <strong><?= $count ?> room<?= $count == 1 ? '' : 's' ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="total-count"><strong>Total Rooms:</strong> <?= $total_rooms ?></p>

        <div class="button-group">
            <form method="POST" style="display:inline;">
                <button type="submit" name="show_table">📋 View Full Room Occupancy Table</button>
            </form>
            <a href="reports.php"><button>⬅️ Back to Dashboard</button></a>
        </div>

        <?php if ($show_table): ?>
            <form method="POST" class="search-form">
                <input type="text" name="room_number" placeholder="Room Number" value="<?= $_POST['room_number'] ?? '' ?>">
                <input type="text" name="room_type" placeholder="Room Type" value="<?= $_POST['room_type'] ?? '' ?>">
                <input type="text" name="occupancy_status" placeholder="Status" value="<?= $_POST['occupancy_status'] ?? '' ?>">
                <input type="text" name="guest_name" placeholder="Guest Name" value="<?= $_POST['guest_name'] ?? '' ?>">
                <button type="submit" name="search">🔍 Search</button>
            </form>

            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Room #</th>
                    <th>Room Type</th>
                    <th>Floor</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Guest</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($table_rows as $row): ?>
                    <tr>
                        <td data-label="ID"><?= $row['id'] ?></td>
                        <td data-label="Room #"><?= $row['room_number'] ?></td>
                        <td data-label="Room Type"><?= $row['room_type'] ?></td>
                        <td data-label="Floor"><?= $row['floor'] ?></td>
                        <td data-label="Price">₱<?= number_format($row['price'], 2) ?></td>
                        <td data-label="Status">
                            <?php $status_lower = strtolower($row['occupancy_status']); ?>
                            <span class="status-<?= $status_lower ?>">
                                <?= $status_lower ?>
                            </span>
                        </td>
                        <td data-label="Guest"><?= $row['guest_name'] ?></td>
                        <td data-label="Check-in"><?= $row['check_in_date'] ?></td>
                        <td data-label="Check-out"><?= $row['check_out_date'] ?></td>
                        <td data-label="Created"><?= $row['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div id="roomModal">
            <div class="modal-content">
                <div id="modalTable"></div>
            </div>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('occupancyChart').getContext('2d');
    const occupancyChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                data: <?= json_encode($data) ?>,
                backgroundColor: <?= json_encode(array_values($color_map)) ?>
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                datalabels: {
                    color: '#ffffff',
                    font: { size: 14, weight: 'bold' },
                    formatter: function(value, context){
                        const label = context.chart.data.labels[context.dataIndex];
                        const matches = label.match(/\((\d+)\)/);
                        const count = matches ? matches[1] : '?';
                        return count + ' rooms\n(' + value + '%)';
                    },
                    textAlign: 'center'
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    function closeModal() {
        document.getElementById('roomModal').style.display = 'none';
        document.getElementById('modalTable').innerHTML = '';
    }

    function showRoomTable(status) {
        document.getElementById('modalTable').innerHTML = "<p style='color:#fff;text-align:center;'>Loading...</p>";
        document.getElementById('roomModal').style.display = 'flex';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'get_rooms.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status == 200) {
                let response = JSON.parse(xhr.responseText);
                document.getElementById('modalTable').innerHTML = response.table;
            } else {
                document.getElementById('modalTable').innerHTML = "<p style='color:red;text-align:center;'>Error loading data.</p>";
            }
        };
        xhr.send('status=' + encodeURIComponent(status));
    }

    occupancyChart.options.onClick = function(evt, elements) {
        if (elements.length > 0) {
            const chartElemIdx = elements[0].index;
            const statuses = Object.keys(<?= json_encode($color_map) ?>);
            let status = statuses[chartElemIdx];
            showRoomTable(status);
        }
    };
    </script>
</body>
</html>