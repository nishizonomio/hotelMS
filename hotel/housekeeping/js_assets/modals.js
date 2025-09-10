// Global variable to store task ID for deletion
let taskToDelete = null;

// Show delete confirmation modal
function showDeleteConfirmation(taskId) {
    taskToDelete = taskId;
    document.getElementById('deleteModal').classList.add('show');
}

// Close delete confirmation modal
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    taskToDelete = null;
}

// Confirm deletion of task
function confirmDelete() {
    if (taskToDelete) {
        // Make AJAX call to delete task
        fetch('delete_task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'task_id=' + encodeURIComponent(taskToDelete)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the task element from DOM
                const taskElement = document.getElementById('task-' + taskToDelete);
                if (taskElement) {
                    taskElement.remove();
                }
                closeDeleteModal();
            } else {
                alert('Error deleting task: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the task');
        });
    }
}
