<?php
include '../db.php';

$result = $conn->query("
    SELECT 
        s.staff_id, 
        s.first_name, 
        s.last_name, 
        s.position_name,
        ed.contract_file, ed.sss_no, ed.philhealth_no, ed.pagibig_no, ed.tin_no,
        ed.nbi_clearance, ed.birth_certificate, ed.diploma, ed.tor,
        ed.barangay_clearance, ed.police_clearance
    FROM staff s
    LEFT JOIN employee_documents ed ON s.staff_id = ed.staff_id
    ORDER BY s.first_name, s.last_name
");

$employees = [];
while ($row = $result->fetch_assoc()) {
    $missing = [];
    if (empty($row['contract_file'])) $missing[] = 'Contract';
    if (empty($row['photo'])) $missing[] = 'Photo';
    if (empty($row['sss_no'])) $missing[] = 'SSS No';
    if (empty($row['philhealth_no'])) $missing[] = 'PhilHealth No';
    if (empty($row['pagibig_no'])) $missing[] = 'Pag-IBIG No';
    if (empty($row['tin_no'])) $missing[] = 'TIN No';
    if (empty($row['nbi_clearance'])) $missing[] = 'NBI Clearance';
    if (empty($row['birth_certificate'])) $missing[] = 'Birth Certificate';
    if (empty($row['diploma'])) $missing[] = 'Diploma';
    if (empty($row['tor'])) $missing[] = 'Transcript of Records';
    if (empty($row['barangay_clearance'])) $missing[] = 'Barangay Clearance';
    if (empty($row['police_clearance'])) $missing[] = 'Police Clearance';
    $row['missing_docs'] = $missing;
    $employees[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Documents</title>
<link rel="stylesheet" href="../css/document_submission.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
<script>
function filterEmployees() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let position = document.getElementById("positionFilter").value.toLowerCase();
    let rows = document.querySelectorAll("tbody tr");

    rows.forEach(row => {
        let name = row.querySelector("td:nth-child(2)").textContent.toLowerCase();
        let pos = row.querySelector("td:nth-child(3)").textContent.toLowerCase();

        if ((name.includes(input) || input === "") && (position === "" || pos === position)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
</script>
</head>
<body>
<div class="container">
    <a href="hr_employee_management.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
    <h1>Employee List</h1>

    <div class="filters">
        <input type="text" id="searchInput" placeholder="Search by name..." onkeyup="filterEmployees()">
        <select id="positionFilter" onchange="filterEmployees()">
            <option value="">All Positions</option>
            <?php 
            $positions = array_unique(array_column($employees, 'position_name'));
            foreach ($positions as $pos): ?>
                <option value="<?= strtolower(htmlspecialchars($pos)) ?>"><?= htmlspecialchars($pos) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Position</th>
                <th>Missing Documents</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
            <tr>
                <td><?= htmlspecialchars($emp['staff_id']) ?></td>
                <td><?= htmlspecialchars($emp['first_name'].' '.$emp['last_name']) ?></td>
                <td><?= htmlspecialchars($emp['position_name']) ?></td>
                <td class="missing">
                    <?php if (!empty($emp['missing_docs'])): ?>
                        <ul>
                            <?php foreach ($emp['missing_docs'] as $doc): ?>
                                <li><?= htmlspecialchars($doc) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><a class="view-btn" href="employee_documents.php?id=<?= urlencode($emp['staff_id']) ?>">View Documents</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
