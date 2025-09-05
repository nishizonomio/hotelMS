<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
        <h2 class="form-title">Confirm Delete</h2>
        <div class="delete-confirmation">
            <i class="fas fa-exclamation-triangle" style="font-size: 3em; color: #dc3545; margin-bottom: 20px;"></i>
            <p>Are you sure you want to delete this task?</p>
            <p class="delete-warning">This action cannot be undone.</p>
        </div>
        <div class="form-buttons">
            <button type="button" class="cancel-btn" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="delete-confirm-btn" onclick="confirmDelete()">
                <i class="fas fa-trash"></i> Delete Task
            </button>
        </div>
    </div>
</div>

<style>
.delete-confirmation {
    text-align: center;
    color: #fff;
    margin: 20px 0;
}

.delete-warning {
    color: #dc3545;
    font-size: 0.9em;
    margin-top: 10px;
}

#deleteModal .modal-content {
    max-width: 400px;
}
</style>

<script>
let taskToDelete = null;

function showDeleteConfirmation(taskId) {
    taskToDelete = taskId;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    taskToDelete = null;
}

function confirmDelete() {
    if (taskToDelete === null) return;
    
    fetch(`delete_task.php?taskId=${taskToDelete}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeDeleteModal();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the task');
    });
}

// Update delete function to show confirmation
function deleteTask(taskId) {
    showDeleteConfirmation(taskId);
}
</script>
