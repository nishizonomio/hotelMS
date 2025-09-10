<!-- Add CSS and JavaScript dependencies -->
<link rel="stylesheet" href="../css/modals.css">
<script src="../js_assets/modals.js" defer></script>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
        <h2 class="form-title">Confirm Delete</h2>
        <div class="delete-confirmation">
            <i class="fas fa-exclamation-triangle warning-icon"></i>
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
