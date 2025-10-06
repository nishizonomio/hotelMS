<?php
require_once('../db.php');

$selected_date = $_GET['date'] ?? date('Y-m-d');
$categories = ['Restaurant','Mini Bar','Lounge Bar','Gift Store'];
$category_sales = [];
$total_sales = 0;

foreach($categories as $cat) {
    $stmt = $conn->prepare("
        SELECT gb.order_id, gb.guest_id, gb.guest_name, gb.order_type, gb.total_amount, gb.payment_option, gb.payment_method, gb.created_at
        FROM guest_billing gb
        WHERE DATE(gb.created_at) = ? AND gb.order_type = ?
        ORDER BY gb.created_at ASC
    ");
    $stmt->execute([$selected_date, $cat]);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $category_sales[$cat] = $sales;

    foreach($sales as $sale) {
        $total_sales += $sale['total_amount'];
    }
}

$daily_sales = [];
$daily_sales_last_month = [];

for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("$selected_date -$i days"));
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM guest_billing WHERE DATE(created_at)=?");
    $stmt->execute([$day]);
    $daily_sales[$day] = (float)($stmt->fetchColumn() ?? 0);

    $day_last_month = date('Y-m-d', strtotime("$day -1 month"));
    $stmt->execute([$day_last_month]);
    $daily_sales_last_month[$day] = (float)($stmt->fetchColumn() ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Daily Sales Report - Hotel La Vista</title>
<link rel="stylesheet" href="daily_sales_report.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
    <h1>Daily Sales Report</h1>
    <div class="header-buttons">
        <a href="../pos.php"><button type="button">Back</button></a>
        <button id="export_csv">Export CSV</button>
    </div>
</header>

<div class="container">
    <form class="date-filter" method="get">
        <label for="date">Select Date:</label>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($selected_date) ?>">
        <button type="submit">Filter</button>
    </form>

    <?php foreach($category_sales as $cat => $sales): ?>
    <div class="category-section">
        <h2><?= $cat ?></h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Guest ID</th>
                <th>Guest Name</th>
                <th>Total Amount</th>
                <th>Payment Option</th>
                <th>Payment Method</th>
                <th>Date/Time</th>
            </tr>
            <?php 
                $cat_total = 0;
                if(empty($sales)): ?>
            <tr>
                <td colspan="7" style="text-align:center;">No sales in this category.</td>
            </tr>
            <?php else: ?>
                <?php foreach($sales as $sale):
                    $cat_total += $sale['total_amount'];
                ?>
            <tr>
                <td><?= $sale['order_id'] ?></td>
                <td><?= $sale['guest_id'] ?></td>
                <td><?= htmlspecialchars($sale['guest_name']) ?></td>
                <td>₱<?= number_format($sale['total_amount'],2) ?></td>
                <td><?= htmlspecialchars($sale['payment_option']) ?></td>
                <td><?= htmlspecialchars($sale['payment_method']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($sale['created_at'])) ?></td>
            </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
        <div class="category-total">Category Total: ₱<?= number_format($cat_total,2) ?></div>
    </div>
    <?php endforeach; ?>

    <div class="overall-total">Overall Total Sales: ₱<?= number_format($total_sales,2) ?></div>
</div>

<div id="salesModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Sales Comparison (This Month vs Last Month)</h2>
    <canvas id="salesChart" width="600" height="300"></canvas>
  </div>
</div>

<script>
document.getElementById('export_csv').addEventListener('click', function() {
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Category,Order ID,Guest ID,Guest Name,Total Amount,Payment Option,Payment Method,Date/Time\n";

    <?php foreach($category_sales as $cat => $sales): ?>
        <?php foreach($sales as $sale): ?>
            csvContent += "<?= $cat ?>,<?= $sale['order_id'] ?>,<?= $sale['guest_id'] ?>,<?= addslashes($sale['guest_name']) ?>,<?= number_format($sale['total_amount'],2) ?>,<?= $sale['payment_option'] ?>,<?= $sale['payment_method'] ?>,<?= date('Y-m-d H:i', strtotime($sale['created_at'])) ?>\n";
        <?php endforeach; ?>
    <?php endforeach; ?>

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "daily_sales_<?= $selected_date ?>.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});

let modal = document.getElementById("salesModal");
let span = document.getElementsByClassName("close")[0];
window.onload = function() {
    modal.style.display = "block";
};
span.onclick = function() {
    modal.style.display = "none";
};
window.onclick = function(event) {
    if(event.target == modal) modal.style.display = "none";
};

const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($daily_sales)) ?>,
        datasets: [
            {
                label: 'This Month',
                data: <?= json_encode(array_values($daily_sales)) ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0,123,255,0.2)',
                fill: true
            },
            {
                label: 'Last Month',
                data: <?= json_encode(array_values($daily_sales_last_month)) ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40,167,69,0.2)',
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            x: { display: true, title: { display: true, text: 'Date' } },
            y: { display: true, title: { display: true, text: 'Sales (₱)' } }
        }
    }
});
</script>
</body>
</html>
