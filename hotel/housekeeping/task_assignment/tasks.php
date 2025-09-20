<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
require_once __DIR__ . '/../repo/taskmanager.php';

$db = new Database();
$taskManager = new TaskManager($db->getConnection());

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["add_task"])) {
        $taskManager->saveTask([
            'room_id' => $_POST['room_id'],
            'task_type' => $_POST['task_type'],
            'staff_id' => $_POST['staff_id'],
            'task_date' => $_POST['task_date'],
            'status' => $_POST['status'],
            'remarks' => $_POST['remarks']
        ]);
    } elseif (isset($_POST["update_task"])) {
        $taskManager->saveTask([
            'task_id' => $_POST['task_id'],
            'room_id' => $_POST['room_id'],
            'task_type' => $_POST['task_type'],
            'staff_id' => $_POST['staff_id'],
            'task_date' => $_POST['task_date'],
            'status' => $_POST['status'],
            'remarks' => $_POST['remarks']
        ]);
    } elseif (isset($_POST["delete_task"])) {
        $taskManager->deleteTask($_POST['task_id']);
    }
}

$tasks_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $tasks_per_page;

$total_tasks = $taskManager->getTaskCount();
$total_pages = ceil($total_tasks / $tasks_per_page);

$tasks = $taskManager->getTasks($tasks_per_page, $offset);
$taskStats = $taskManager->getTaskStats();

// Fetch rooms and staff into arrays for multiple iterations
$roomsResult = $taskManager->getRooms();
$rooms = [];
while ($r = $roomsResult->fetch_assoc()) {
    $rooms[] = $r;
}

$staffResult = $taskManager->getStaff();
$staffList = [];
while ($s = $staffResult->fetch_assoc()) {
    $staffList[] = $s;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Assignment | Housekeeping</title>
    <link rel="stylesheet" href="../../homepage/index.css">
    <link rel="stylesheet" href="../css/tasks_new.css">
    <link rel="stylesheet" href="../css/tasks.css">
    <link rel="stylesheet" href="../css/pagination.css">
    <link rel="stylesheet" href="../css/tasks_form.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
</head>
<body>
    <div class="overlay">
    <div class="container">
        <header>
            <div class="nav-buttons">
                <a href="../housekeeping.php" class="nav-btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="../../index.php" class="nav-btn back-btn"><i class="fas fa-home"></i> Home</a>
            </div>
            <h1>Task Assignment Dashboard</h1>
        </header>
        <div class="stats-summary">
            <div class="stat-card pending-card"><i class="fas fa-clock"></i><div><span>Pending </span><span><?= $taskStats['Pending'] ?></span></div></div>
            <div class="stat-card progress-card"><i class="fas fa-spinner"></i><div><span>In Progress </span><span><?= $taskStats['In Progress'] ?></span></div></div>
            <div class="stat-card completed-card"><i class="fas fa-check-circle"></i><div><span>Completed </span><span><?= $taskStats['Completed'] ?></span></div></div>
            <div class="stat-card total-card"><i class="fas fa-tasks"></i><div><span>Total </span><span><?= array_sum($taskStats) ?></span></div></div>
        </div>

        <!-- Task List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Task List</h2>
                <button class="add-task-btn" onclick="showAddTaskForm()"><i class="fas fa-plus"></i> Add New Task</button>
            </div>
            <table class="task-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Task Type</th>
                        <th>Assigned To</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($tasks && $tasks->num_rows > 0): ?>
                        <?php while($row = $tasks->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['room_number']) ?></td>
                                <td><?= htmlspecialchars($row['task_type']) ?></td>
                                <td><?= htmlspecialchars($row['staff_name']) ?></td>
                                <td><?= htmlspecialchars($row['task_date']) ?></td>
                                <td class="status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>"><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['remarks']) ?></td>
                                <td class="action-buttons">
                                    <!-- Edit -->
                                    <button class="btn edit-btn" onclick="editTask(<?= $row['task_id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Mark Completed -->
                                    <a href="?complete=<?= $row['task_id'] ?>" class="btn complete-btn">
                                        <i class="fas fa-check"></i>
                                    </a>

                                    <!-- Delete -->
                                    <button class="btn delete-btn" onclick="deleteTask(<?= $row['task_id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No tasks found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="page-btn">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <a href="?page=<?= $p ?>" class="page-btn <?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="page-btn">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Task Form Modal -->
        <div id="taskForm" class="modal">
            <div class="modal-content">
                <button class="close-btn" onclick="hideTaskForm()">&times;</button>
                <h2 class="form-title">Add New Task</h2>
                <form id="addTaskForm" method="POST" onsubmit="return validateTaskForm()">
                    <input type="hidden" name="task_id" id="task_id">

                    <div class="form-group">
                        <label for="room_id">Room Number</label>
                        <select name="room_id" id="room_id" required>
                            <option value="">Select a room</option>
                            <?php foreach($rooms as $r): ?>
                                <option value="<?= $r['room_id'] ?>"><?= htmlspecialchars($r['room_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="task_type">Task Type</label>
                        <select name="task_type" id="task_type" required>
                            <option value="">Select task type</option>
                            <option value="Cleaning">Room Cleaning</option>
                            <option value="Laundry">Laundry Service</option>
                            <option value="Turn-down">Turn-down Service</option>
                            <option value="Deep Cleaning">Deep Cleaning</option>
                            <option value="Maintenance">Maintenance Check</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="staff_id">Assign Staff Member</label>
                        <select name="staff_id" id="staff_id" required>
                            <option value="">Select staff member</option>
                            <?php foreach($staffList as $s): ?>
                                <option value="<?= $s['staff_id'] ?>"><?= htmlspecialchars($s['staff_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="task_date">Schedule Date</label>
                        <input type="date" name="task_date" id="task_date" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Remarks:</label>
                        <textarea name="remarks" id="remarks" rows="3"></textarea>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" name="save_task" class="save-btn">Save Task</button>
                        <button type="button" class="cancel-btn" onclick="hideTaskForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    </div>

    <?php // Include the delete confirmation modal so deleteTask() is available ?>
    <?php include_once __DIR__ . '/delete_confirmation.php'; ?>
    <script src="../js_assets/tasks.js"></script>
</body>
</html>