// MultipleFiles/tasks.js

// Function to show the task form modal
function showAddTaskForm() {
    const taskFormModal = document.getElementById('taskForm');
    taskFormModal.classList.add('show'); // Add 'show' class to make it visible

    // Clear form fields and set defaults for adding a new task
    document.getElementById('task_id').value = ''; // Clear hidden ID for new task
    document.getElementById('room_id').value = ''; // Clear selected room
    document.getElementById('task_type').value = 'Cleaning'; // Default task type
    document.getElementById('staff_id').value = ''; // Clear assigned staff
    document.getElementById('task_date').value = new Date().toISOString().slice(0, 10); // Set current date (YYYY-MM-DD)
    document.getElementById('status').value = 'Pending'; // Default status
    document.getElementById('remarks').value = ''; // Clear remarks

    // Update modal title and button text for "Add New Task"
    document.querySelector('#taskForm .form-title').textContent = 'Add New Task';
    document.querySelector('#taskForm .save-btn').textContent = 'Save Task';
    document.querySelector('#taskForm .save-btn').name = 'add_task'; // Set name for server-side handling
}

// Function to hide the task form modal
function hideTaskForm() {
    const taskFormModal = document.getElementById('taskForm');
    taskFormModal.classList.remove('show'); // Remove 'show' class to hide it
}

// Function to populate the form for editing an existing task
function editTask(taskId) {
    fetch(`get_task.php?taskId=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            // Populate form fields with existing task data
            document.getElementById('task_id').value = data.task_id;
            document.getElementById('room_id').value = data.room_id;
            document.getElementById('task_type').value = data.task_type;
            document.getElementById('staff_id').value = data.staff_id;
            document.getElementById('task_date').value = data.task_date;
            document.getElementById('status').value = data.status;
            document.getElementById('remarks').value = data.remarks;

            // Update modal title and button text for "Edit Task"
            document.querySelector('#taskForm .form-title').textContent = 'Edit Task';
            document.querySelector('#taskForm .save-btn').textContent = 'Update Task';
            document.querySelector('#taskForm .save-btn').name = 'update_task'; // Set name for server-side handling

            showAddTaskForm(); // Show the modal with pre-filled data
        })
        .catch(error => console.error('Error fetching task:', error));
}

// Function to show delete confirmation modal
// This function assumes you have a delete confirmation modal set up as per delete_confirmation.php
// and that the deleteTask function in that script is correctly linked.
// You would call this from your table's delete button: onclick="showDeleteConfirmation(<?= $row['task_id'] ?>)"
// The actual deletion logic is in delete_confirmation.php's script.
// So, this function just needs to call the one defined in delete_confirmation.php
// Make sure delete_confirmation.php is included in tasks.php
// Example: <button class="btn delete-btn" onclick="showDeleteConfirmation(<?= $row['task_id'] ?>)">
//             <i class="fas fa-trash"></i>
//          </button>
// The `deleteTask` function in `delete_confirmation.php` is already set up to be called this way.
// So, you don't need to redefine `deleteTask` here if `delete_confirmation.php` is included.


// Event listener for form submission (for both adding and updating tasks)
document.addEventListener('DOMContentLoaded', function() {
    const addTaskForm = document.getElementById('addTaskForm');
    if (addTaskForm) {
        addTaskForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(this);
            const jsonData = {};
            formData.forEach((value, key) => {
                jsonData[key] = value;
            });

            // Determine if it's an add or update operation based on task_id
            const url = 'save_task.php'; // Both use save_task.php

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    hideTaskForm();
                    location.reload(); // Reload the page to show changes
                } else {
                    alert('Failed to save task: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error saving task:', error);
                alert('An error occurred while saving the task.');
            });
        });
    }

    // Event listener for Mark Completed buttons (using AJAX)
    document.querySelectorAll('.complete-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const taskId = this.href.split('=').pop(); // Extract task ID from href (e.g., ?complete=ID)

            fetch(`complete_task.php?id=${taskId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload page to show updated status
                    } else {
                        alert('Failed to mark task as completed.');
                    }
                })
                .catch(error => console.error('Error marking task complete:', error));
        });
    });

    // Event listener for closing modal when clicking outside of it
    const taskFormModal = document.getElementById('taskForm');
    if (taskFormModal) {
        window.onclick = function(event) {
            if (event.target == taskFormModal) {
                hideTaskForm();
            }
        };
    }
});