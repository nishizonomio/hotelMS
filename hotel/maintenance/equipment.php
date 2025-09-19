<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "hotel";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $location = $_POST['location'];
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';
        $stmt = $conn->prepare("INSERT INTO equipment_assets (asset_name, asset_type, location, status, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $type, $location, $status, $notes);
        $stmt->execute();
        echo json_encode(["success" => true]);
        exit;
    }
    if ($action === 'update') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $type = $_POST['type'];
        $location = $_POST['location'];
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';
        $stmt = $conn->prepare("UPDATE equipment_assets SET asset_name=?, asset_type=?, location=?, status=?, notes=? WHERE asset_id=?");
        $stmt->bind_param("sssssi", $name, $type, $location, $status, $notes, $id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        exit;
    }
    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM equipment_assets WHERE asset_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        exit;
    }
    exit;
}
if (isset($_GET['fetch'])) {
    $result = $conn->query("SELECT * FROM equipment_assets ORDER BY asset_id DESC");
    $assets = [];
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }
    echo json_encode($assets);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Equipment & Assets Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
    .card {
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.25);
      padding: 30px;
      margin-bottom: 20px;
    }
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 10px;
      background: #111827;
      color: #f9fafb;
      font-size: 10px;
      border-top: 1px solid #374151;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      z-index: 100;
    }
  </style>
</head>
<body>
<div class="overlay">
  <div class="fixed top-4 right-4 z-50">
    <a href="maintenance.php" 
       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-md">
      ⬅ Back to Main
    </a>
  </div>

  <div class="container mx-auto max-w-7xl">
    <div class="card mb-8">
      <h1 style="font-size: 35px; text-align: center; font-weight: bold; color: white;">
        EQUIPMENT ASSETS AND REGISTER
      </h1>
      <p style="color: white; text-align: center; font-size: 14px;">
        Management system for equipment and inventory control
      </p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-1">
        <div class="card">
          <h2 class="text-2xl font-semibold text-white mb-6 flex items-center gap-3">
            Add New Asset
          </h2>
          <form id="assetForm" class="space-y-4">
            <div>
              <label class="block text-white font-medium mb-2">Asset Name</label>
              <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
              <label class="block text-white font-medium mb-2">Type</label>
              <input type="text" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
              <label class="block text-white font-medium mb-2">Location</label>
              <input type="text" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
              <label class="block text-white font-medium mb-2">Status</label>
              <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="available">Available</option>
                <option value="in-use">In Use</option>
                <option value="maintenance">Maintenance</option>
              </select>
            </div>
            <div>
              <label class="block text-white font-medium mb-2">Notes</label>
              <input type="text" name="notes" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 font-semibold">
              Register Asset
            </button>
          </form>
          <div id="formMessage" class="hidden mt-4 p-3 bg-green-100 text-green-800 rounded-lg">Asset added successfully!</div>
        </div>
      </div>
      <div class="lg:col-span-2">
        <div class="card">
          <h2 class="text-2xl font-semibold text-white mb-6">Asset Registry</h2>
          <div id="assetList" class="space-y-4 max-h-96 overflow-y-auto">
            <!-- Assets will be populated here -->
          </div>
        </div>
      </div>
    </div>
    <!-- Edit Modal -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-semibold mb-4" id="modalTitle">Edit Asset</h3>
        <form id="modalForm" class="space-y-4">
          <input type="hidden" id="modalAssetId">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Asset Name</label>
            <input type="text" id="modalName" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Type</label>
            <input type="text" id="modalType" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Location</label>
            <input type="text" id="modalLocation" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Status</label>
            <select id="modalStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
              <option value="available">Available</option>
              <option value="in-use">In Use</option>
              <option value="maintenance">Maintenance</option>
            </select>
          </div>
          <div>
            <label class="block text-white font-medium mb-2">Notes</label>
            <input type="text" id="modalNotes" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
          </div>
          <div class="flex gap-3 pt-4">
            <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700">
              Update Asset
            </button>
            <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
function fetchAssets() {
  fetch('?fetch=1')
    .then(res => res.json())
    .then(assets => {
      renderAssets(assets);
    });
}
function renderAssets(assets) {
  const assetList = document.getElementById('assetList');
  if (!assets.length) {
    assetList.innerHTML = `<div class="text-center py-12 text-gray-500">No assets found.</div>`;
    return;
  }
  assetList.innerHTML = assets.map(asset => `
    <div class="asset-item">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-start mb-2">
            <h3 class="font-semibold text-lg text-gray-900 truncate">${asset.asset_name}</h3>
            <div class="flex gap-2 ml-4">
              <button onclick="editAsset(${asset.asset_id})" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg">Edit</button>
              <button onclick="deleteAsset(${asset.asset_id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">Delete</button>
            </div>
          </div>
          <div class="space-y-2 text-sm text-white">
            <div>Type: ${asset.asset_type}</div>
            <div>Location: ${asset.location}</div>
            <div>Status: <span class="status-badge ${asset.status}">${asset.status.replace('-', ' ')}</span></div>
            <div>Notes: ${asset.notes || ''}</div>
          </div>
        </div>
      </div>
    </div>
  `).join('');
}
document.getElementById('assetForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('action', 'add');
  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      this.reset();
      document.getElementById('formMessage').classList.remove('hidden');
      setTimeout(() => document.getElementById('formMessage').classList.add('hidden'), 2000);
      fetchAssets();
    }
  });
});
function editAsset(id) {
  fetch('?fetch=1')
    .then(res => res.json())
    .then(assets => {
      const asset = assets.find(a => a.asset_id == id);
      if (!asset) return;
      document.getElementById('modalAssetId').value = asset.asset_id;
      document.getElementById('modalName').value = asset.asset_name;
      document.getElementById('modalType').value = asset.asset_type;
      document.getElementById('modalLocation').value = asset.location;
      document.getElementById('modalStatus').value = asset.status;
      document.getElementById('modalNotes').value = asset.notes;
      document.getElementById('modal').classList.remove('hidden');
    });
}
document.getElementById('modalForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData();
  formData.append('action', 'update');
  formData.append('id', document.getElementById('modalAssetId').value);
  formData.append('name', document.getElementById('modalName').value);
  formData.append('type', document.getElementById('modalType').value);
  formData.append('location', document.getElementById('modalLocation').value);
  formData.append('status', document.getElementById('modalStatus').value);
  formData.append('notes', document.getElementById('modalNotes').value);
  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      closeModal();
      fetchAssets();
    }
  });
});
function deleteAsset(id) {
  if (confirm('Are you sure you want to delete this asset?')) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    fetch('', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) fetchAssets();
    });
  }
}
function closeModal() {
  document.getElementById('modal').classList.add('hidden');
}
document.getElementById('modal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
fetchAssets();
</script>
<footer class="footer">
  <p>© 2025 Hotel Maintenance and Engineering | All Rights Reserved</p>
</footer>
</body>
</html>
