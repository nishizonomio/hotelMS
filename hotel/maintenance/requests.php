<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "hotel";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = $_POST['requesterName'] ?? '';
        $email = $_POST['requesterEmail'] ?? '';
        $department = $_POST['department'] ?? '';
        $location = $_POST['location'] ?? '';
        $category = $_POST['category'] ?? '';
        $issueTitle = $_POST['issueTitle'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $requestedDate = $_POST['requestedDate'] ?: null;
        $submittedDate = date("Y-m-d");
        $status = 'pending';

        // Validate required fields
        if (empty($name) || empty($email) || empty($location) || empty($category) || empty($issueTitle) || empty($description) || empty($priority)) {
            echo json_encode(["success" => false, "message" => "Please fill in all required fields"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO maintenance_request_logging (Reported_By, Requester_Email, Department, Location, Category, Issue_Title, Issue_Description, Priority_Level, Request_Status, Completion_Date, Submitted_Date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssssssss", $name, $email, $department, $location, $category, $issueTitle, $description, $priority, $status, $requestedDate, $submittedDate);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Maintenance request submitted successfully!"]);
            } else {
                echo json_encode(["success" => false, "message" => "Error inserting data: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Error preparing statement: " . $conn->error]);
        }
        exit;
    }
    if ($action === 'update') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE maintenance_request_logging SET Request_Status=? WHERE Request_ID=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        exit;
    }
    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM maintenance_request_logging WHERE Request_ID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        exit;
    }
    exit;
}

if (isset($_GET['fetch'])) {
    $result = $conn->query("SELECT * FROM maintenance_request_logging ORDER BY Request_ID DESC");
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    echo json_encode($requests);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Request System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
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
            background: transparent;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .header h1 { font-size: 2.5em; font-weight: 700; margin-bottom: 10px; }
        .header p { font-size: 1.1em; opacity: 0.9; }
        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .nav-tab {
            flex: 1;
            padding: 20px;
            background: none;
            border: none;
            font-size: 1.1em;
            font-weight: 600;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .nav-tab.active {
            color: #667eea;
            background: white;
        }
        .tab-content { display: none; padding: 40px; animation: fadeIn 0.5s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 0.95em;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #fff;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .full-width { grid-column: 1 / -1; }
        .priority-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .priority-btn {
            padding: 12px 24px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            flex: 1;
            min-width: 120px;
            outline: none;
        }
        .priority-btn.low { border-color: #28a745; color: #28a745; }
        .priority-btn.low.active, .priority-btn.low:hover { background: #28a745; color: white; }
        .priority-btn.medium { border-color: #ffc107; color: #ffc107; }
        .priority-btn.medium.active, .priority-btn.medium:hover { background: #ffc107; color: white; }
        .priority-btn.high { border-color: #dc3545; color: #dc3545; }
        .priority-btn.high.active, .priority-btn.high:hover { background: #dc3545; color: white; }
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.2em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .requests-list { display: grid; gap: 20px; }
        .request-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .request-card.priority-low { border-left-color: #28a745; }
        .request-card.priority-medium { border-left-color: #ffc107; }
        .request-card.priority-high { border-left-color: #dc3545; }
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .request-title { font-size: 1.3em; font-weight: 700; color: #2c3e50; margin: 0; }
        .request-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
            color: #6c757d;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #cce5ff; color: #0066cc; }
        .status-completed { background: #d4edda; color: #155724; }
        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-low-badge { background: #d4edda; color: #155724; }
        .priority-medium-badge { background: #fff3cd; color: #856404; }
        .priority-high-badge { background: #f8d7da; color: #721c24; }
        .request-description { color: #495057; line-height: 1.6; margin-bottom: 15px; }
        .request-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .action-btn.update { background: #007bff; color: white; }
        .action-btn.delete { background: #dc3545; color: white; }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .stat-number { font-size: 2.5em; font-weight: 700; margin-bottom: 5px; }
        .stat-label { font-size: 0.9em; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
        .empty-state { text-align: center; padding: 60px; color: #6c757d; }
        .empty-state h3 { font-size: 1.5em; margin-bottom: 10px; }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 768px) {
            .container { margin: 10px; border-radius: 15px; }
            .header { padding: 20px; }
            .header h1 { font-size: 2em; }
            .tab-content { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; gap: 20px; }
            .request-header { flex-direction: column; align-items: flex-start; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 15px;
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
        <div class="container">
            <div class="header">
                <h1>
                    <a href="maintenance.php" style="text-decoration: none; color: white;">
                        MAINTENANCE HUB
                    </a>
                </h1>
                <p>Streamlined facility maintenance request management</p>
            </div>
            <div class="nav-tabs">
                <button class="nav-tab active" type="button">New Request</button>
                <button class="nav-tab" type="button">View Requests</button>
            </div>
            <div id="new-request" class="tab-content active">
                <div id="alertContainer"></div>
                <form id="maintenanceForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="requesterName">Requester Name *</label>
                            <input type="text" id="requesterName" name="requesterName" required>
                        </div>
                        <div class="form-group">
                            <label for="requesterEmail">Email Address *</label>
                            <input type="email" id="requesterEmail" name="requesterEmail" required>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">Select Department</option>
                                <option value="IT">Information Technology</option>
                                <option value="HR">Human Resources</option>
                                <option value="Finance">Finance</option>
                                <option value="Operations">Operations</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Facilities">Facilities</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Location *</label>
                            <input type="text" id="location" name="location" placeholder="Building, Floor, Room Number" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Issue Category *</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Electrical">Electrical</option>
                                <option value="Plumbing">Plumbing</option>
                                <option value="HVAC">HVAC (Heating/Cooling)</option>
                                <option value="Cleaning">Cleaning</option>
                                <option value="Security">Security</option>
                                <option value="Equipment">Equipment</option>
                                <option value="Structural">Structural</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="requestedDate">Requested Completion Date</label>
                            <input type="date" id="requestedDate" name="requestedDate">
                        </div>
                        <div class="form-group full-width">
                            <label for="issueTitle">Issue Title *</label>
                            <input type="text" id="issueTitle" name="issueTitle" placeholder="Brief description of the issue" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="description">Detailed Description *</label>
                            <textarea id="description" name="description" placeholder="Please provide a detailed description of the maintenance issue, including any relevant details that might help our team address it quickly and effectively." required></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Priority Level *</label>
                            <div class="priority-buttons">
                                <button type="button" class="priority-btn low" onclick="setPriority('low')">Low</button>
                                <button type="button" class="priority-btn medium" onclick="setPriority('medium')">Medium</button>
                                <button type="button" class="priority-btn high" onclick="setPriority('high')">High</button>
                            </div>
                            <input type="hidden" id="priority" name="priority" required>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn" id="submitBtn">Submit Maintenance Request</button>
                </form>
            </div>
            <div id="view-requests" class="tab-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" id="totalRequests">0</div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="pendingRequests">0</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="inProgressRequests">0</div>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="completedRequests">0</div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                <div id="requestsList" class="requests-list">
                    <div class="empty-state">
                        <h3>No Maintenance Requests Yet</h3>
                        <p>Submit your first maintenance request to get started!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        let currentPriority = '';

        // Show alert function
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type}">
                    ${message}
                </div>
            `;
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        // Tab switching
        document.querySelectorAll('.nav-tab').forEach((tab, idx) => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
                this.classList.add('active');
                if (idx === 0) {
                    document.getElementById('new-request').classList.add('active');
                } else {
                    document.getElementById('view-requests').classList.add('active');
                    fetchRequests();
                }
            });
        });

        // Set priority function
        function setPriority(priority) {
            currentPriority = priority;
            document.getElementById('priority').value = priority;
            document.querySelectorAll('.priority-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.priority-btn.${priority}`).classList.add('active');
        }

        // Form submission
        document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate priority selection
            if (!currentPriority) {
                showAlert('Please select a priority level', 'error');
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            // Create form data
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            // Make sure priority is included
            formData.set('priority', currentPriority);

            // Debug: Log form data
            console.log('Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response:', data);
                
                if (data.success) {
                    showAlert(data.message || 'Maintenance request submitted successfully!', 'success');
                    
                    // Reset form
                    this.reset();
                    currentPriority = '';
                    document.getElementById('priority').value = '';
                    document.querySelectorAll('.priority-btn').forEach(btn => btn.classList.remove('active'));
                    
                    // Switch to view requests tab after a delay
                    setTimeout(() => {
                        document.querySelectorAll('.nav-tab')[1].click();
                    }, 2000);
                } else {
                    showAlert(data.message || 'Error submitting request. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error submitting request. Please check your connection and try again.', 'error');
            })
            .finally(() => {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        // Fetch requests function
        function fetchRequests() {
            fetch(window.location.href + '?fetch=1')
                .then(response => response.json())
                .then(requests => {
                    console.log('Fetched requests:', requests);
                    displayRequests(requests);
                    updateStats(requests);
                })
                .catch(error => {
                    console.error('Error fetching requests:', error);
                });
        }

        // Display requests function
        function displayRequests(requests) {
            const requestsList = document.getElementById('requestsList');
            if (!requests || !requests.length) {
                requestsList.innerHTML = `
                    <div class="empty-state">
                        <h3>No Maintenance Requests Yet</h3>
                        <p>Submit your first maintenance request to get started!</p>
                    </div>
                `;
                return;
            }
            requestsList.innerHTML = requests.map(request => `
                <div class="request-card priority-${request.Priority_Level}">
                    <div class="request-header">
                        <h3 class="request-title">${request.Issue_Title}</h3>
                        <div>
                            <span class="status-badge status-${request.Request_Status}">${request.Request_Status.replace('-', ' ')}</span>
                            <span class="priority-badge priority-${request.Priority_Level}-badge">${request.Priority_Level} Priority</span>
                        </div>
                    </div>
                    <div class="request-meta">
                        <div class="meta-item"><strong>ID:</strong> ${request.Request_ID}</div>
                        <div class="meta-item"><strong>Category:</strong> ${request.Category}</div>
                        <div class="meta-item"><strong>Location:</strong> ${request.Location}</div>
                        <div class="meta-item"><strong>Submitted:</strong> ${request.Submitted_Date}</div>
                    </div>
                    <p class="request-description">${request.Issue_Description}</p>
                    <div class="request-meta">
                        <div class="meta-item"><strong>Requester:</strong> ${request.Reported_By} (${request.Department || 'N/A'})</div>
                        <div class="meta-item"><strong>Email:</strong> ${request.Requester_Email}</div>
                        <div class="meta-item"><strong>Requested Date:</strong> ${request.Completion_Date || 'N/A'}</div>
                    </div>
                    <div class="request-actions">
                        <button class="action-btn update" type="button" onclick="updateStatus(${request.Request_ID}, '${request.Request_Status}')">Update Status</button>
                        <button class="action-btn delete" type="button" onclick="deleteRequest(${request.Request_ID})">Delete</button>
                    </div>
                </div>
            `).join('');
        }

        // Update stats function
        function updateStats(requests) {
            const total = requests ? requests.length : 0;
            const pending = requests ? requests.filter(r => r.Request_Status === 'pending').length : 0;
            const inProgress = requests ? requests.filter(r => r.Request_Status === 'in-progress').length : 0;
            const completed = requests ? requests.filter(r => r.Request_Status === 'completed').length : 0;
            
            document.getElementById('totalRequests').textContent = total;
            document.getElementById('pendingRequests').textContent = pending;
            document.getElementById('inProgressRequests').textContent = inProgress;
            document.getElementById('completedRequests').textContent = completed;
        }

        // Update status function
        function updateStatus(id, currentStatus) {
            const statuses = ['pending', 'in-progress', 'completed'];
            const nextStatus = statuses[(statuses.indexOf(currentStatus) + 1) % statuses.length];
            
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', id);
            formData.append('status', nextStatus);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchRequests();
                }
            })
            .catch(error => {
                console.error('Error updating status:', error);
            });
        }

        // Delete request function
        function deleteRequest(id) {
            if (confirm('Are you sure you want to delete this maintenance request?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchRequests();
                    }
                })
                .catch(error => {
                    console.error('Error deleting request:', error);
                });
            }
        }

        // Set minimum date for requested date field
        document.getElementById('requestedDate').min = new Date().toISOString().split('T')[0];

        // Load requests when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initial load of requests for stats
            fetchRequests();
        });
    </script>
    <footer class="footer">
        <p>© 2025 Hotel Maintenance and Engineering | All Rights Reserved</p>
    </footer>
</body>
</html>