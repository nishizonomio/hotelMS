<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "hotel";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_technician') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $shift = $_POST['shift'];
    $status = "Active";
    $date_joined = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO technicians (Full_Name, Email, Phone, Department, Status, Date_Joined) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $phone, $department, $status, $date_joined);
    $stmt->execute();
    echo json_encode(["success" => true]);
    exit;
}
if (isset($_GET['fetch_technicians'])) {
    $result = $conn->query("SELECT * FROM technicians ORDER BY Technician_ID DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode($rows);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_technician') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM technicians WHERE Technician_ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["success" => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Maintenance System - Technician Dashboard</title>
  <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔧</text></svg>">
  <style>
    body {
      height: 100%;
      font-family: 'Outfit', sans-serif;
      background: url('hotel_room.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    .overlay {
      background: rgba(0, 0, 0, 0.65);
      min-height: 100vh;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    .header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 32px 24px 24px 24px;
      border-radius: 14px;
      margin-bottom: 32px;
      text-align: center;
      box-shadow: 0 2px 12px rgba(0,0,0,0.10);
    }
    .nav {
      display: flex;
      gap: 15px;
      margin-top: 15px;
      justify-content: center;
    }
    .nav button {
      background: rgba(255,255,255,0.2);
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
    }
    .nav button:hover, .nav button.active {
      background: rgba(255,255,255,0.3);
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 32px;
      margin-bottom: 32px;
    }
    .card {
      background: rgba(255,255,255,0.97);
      border-radius: 14px;
      padding: 32px 24px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.10);
      border: 1px solid #e2e8f0;
      min-height: 320px;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
    }
    .card h3 {
      color: #2d3748;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.4em;
      text-align: center;
    }
    .badge {
      display: inline-block;
      padding: 4px 8px;
      background: #f3f4f6;
      border-radius: 4px;
      font-size: 12px;
      margin: 2px;
    }
    .form input, .form select {
      padding: 12px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 15px;
      margin-bottom: 12px;
      width: 100%;
      box-sizing: border-box;
      background: #f9fafb;
    }
 .table {
  width: 100%;
  table-layout: fixed;
  border: 1px solid #e2e8f0;
  border-radius: 15px;
  overflow: hidden;
  background: #fff;
}
.table th, .table td {
  padding: 12px 10px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
  word-break: break-word;
}
.table th {
  background: #f3f4f6;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e2e8f0;
}
.table tr:last-child td {
  border-bottom: none;
}
    .table td button {
      min-width: 70px;
    }
    .form button {
      background: #4f46e5;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      margin-top: 8px;
      width: 100%;
      font-size: 1em;
    }
    .form button:hover {
      background: #4338ca;
    }
    .hidden {
      display: none;
    }
    .back-btn-header {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #fff;
      color: #374151;
      border: 1px solid #e5e7eb;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      box-shadow: 0 2px 8px rgba(59,130,246,0.08);
      transition: background 0.2s, color 0.2s, box-shadow 0.2s;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
      z-index: 1000;
    }
    .back-btn-header:hover {
      background: #f3f4f6;
      color: #2563eb;
      box-shadow: 0 4px 16px rgba(59,130,246,0.12);
    }
    .footer {
      position: bottom;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 5px;
      background: #111827;
      color: #f9fafb;
      font-size: 10px;
      border-top: 1px  #374151;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      z-index: 100;
    }
    @media (max-width: 700px) {
      .container {
        padding: 12px 4px;
      }
      .header {
        padding: 18px 8px;
      }
      .card {
        padding: 18px 8px;
      }
    }
  </style>
</head>
<body>
<div class="overlay">
  <div class="container">
    <div class="header">
      <a href="maintenance.php" class="back-btn-header">
  <i class="fas fa-arrow-left"></i> Back to Main
</a>
      <h1>
          
          TECHNICIAN ASSIGNMENT DASHBOARD
        </a>
      </h1>
      <p>Technician Assignment & Management Dashboard</p>
      <div class="nav">
        <button onclick="showTab('technicians')" class="active" id="tab-technicians">Technicians</button>
      </div>
    </div>
    <div id="technicians-tab">
      <div class="grid">
        <div class="card">
          <h3>👷 Technician Roster</h3>
          <table class="table" id="technicianTable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Date Joined</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
         
            </tbody>
          </table>
        </div>
        <div class="card">
          <h3>🆕 Add Technician</h3>
          <form class="form" id="addTechnicianForm">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <select name="department" required>
              <option value="">Specialization</option>
              <option value="HVAC">HVAC</option>
              <option value="Plumbing">Plumbing</option>
              <option value="Electrical">Electrical</option>
              <option value="General Maintenance">General Maintenance</option>
              <option value="Housekeeping">Housekeeping</option>
            </select>
            <select name="shift" required>
              <option value="">Shift</option>
              <option value="Morning">Morning (6AM-2PM)</option>
              <option value="Afternoon">Afternoon (2PM-10PM)</option>
              <option value="Night">Night (10PM-6AM)</option>
            </select>
            <button type="submit">Add Technician</button>
          </form>
          <div id="techMessage" class="hidden" style="margin-top:10px; color:green;">Technician added successfully!</div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function showTab(tabName) {
  document.querySelectorAll('[id$="-tab"]').forEach(tab => tab.classList.add('hidden'));
  document.getElementById(tabName + '-tab').classList.remove('hidden');
  document.querySelectorAll('.nav button').forEach(btn => btn.classList.remove('active'));
  document.getElementById('tab-' + tabName).classList.add('active');
}


function fetchTechnicians() {
  fetch('?fetch_technicians=1')
    .then(res => res.json())
    .then(techs => {
      const tbody = document.querySelector('#technicianTable tbody');
      if (!techs.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No technicians found.</td></tr>`;
        return;
      }
      tbody.innerHTML = techs.map(t => `
        <tr>
          <td>${t.Full_Name}</td>
          <td>${t.Email}</td>
          <td>${t.Phone}</td>
          <td>${t.Department}</td>
          <td>${t.Date_Joined}</td>
          <td>
            <button onclick="deleteTechnician(${t.Technician_ID})"
              style="background:#dc2626;color:#fff;border:none;padding:6px 12px;
              border-radius:6px;cursor:pointer;font-size:0.9em;">
              Delete
            </button>
          </td>
        </tr>
      `).join('');
    });
}


document.getElementById('addTechnicianForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('action', 'add_technician');
  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      this.reset();
      document.getElementById('techMessage').classList.remove('hidden');
      setTimeout(() => document.getElementById('techMessage').classList.add('hidden'), 2000);
      fetchTechnicians();
    }
  });
});


function deleteTechnician(id) {
  if (!confirm("Are you sure you want to delete this technician?")) return;
  const formData = new FormData();
  formData.append('action', 'delete_technician');
  formData.append('id', id);
  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      fetchTechnicians();
    }
  });
}


showTab('technicians');
fetchTechnicians();
</script>
  <footer class="footer">
  <p> © 2025 Hotel La Vista  | All Rights Reserved</p>
</footer>   
</body>
</html>