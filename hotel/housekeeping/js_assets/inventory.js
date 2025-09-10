// Inventory Management Scripts
document.addEventListener('DOMContentLoaded', () => {
    initializeInventoryHandlers();
    initializeInventoryCharts();
});

function initializeInventoryHandlers() {
    // Bulk delete handling
    setupBulkDelete();
    // Search functionality
    setupSearch();
}

function setupBulkDelete() {
    const bulkForm = document.getElementById('bulkDeleteForm');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkDeleteBtn = document.querySelector('.bulk-delete-btn');
    
    if (!bulkForm || !bulkDeleteBtn) return;

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
            bulkDeleteBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
        });
    });

    bulkForm.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete the selected items?')) {
            return;
        }
        
        const selectedItems = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
        if (selectedItems.length === 0) return;

        const formData = new FormData();
        selectedItems.forEach(id => formData.append('selected_items[]', id));

        fetch('delete_supply.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove deleted items from the table
                selectedItems.forEach(id => {
                    const row = document.querySelector(`tr[data-item-id="${id}"]`);
                    if (row) row.remove();
                });
                
                // Update stats and charts
                updateInventoryStats(data.counts);
                updateInventoryCharts(data.supplies);
                
                // Reset bulk delete button
                bulkDeleteBtn.style.display = 'none';
            } else {
                alert('Error deleting items: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting items');
        });
    });
}

// Handle single item deletion
function deleteItem(itemId) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }

    const formData = new FormData();
    formData.append('item_id', itemId);

    fetch('delete_supply.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from the table
            const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
            if (row) row.remove();
            
            // Update stats and charts
            updateInventoryStats(data.counts);
            updateInventoryCharts(data.supplies);
        } else {
            alert('Error deleting item: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the item');
    });
}

function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

function initializeInventoryCharts() {
    const stockLevelCtx = document.getElementById('stockLevelChart')?.getContext('2d');
    const categoryDistCtx = document.getElementById('categoryDistributionChart')?.getContext('2d');
    
    if (stockLevelCtx) {
        window.stockLevelChart = createStockLevelChart(stockLevelCtx);
    }
    
    if (categoryDistCtx) {
        window.categoryDistChart = createCategoryDistributionChart(categoryDistCtx);
    }
}

// Update inventory statistics
function updateInventoryStats(counts) {
    // Update stat cards
    const totalEl = document.getElementById('totalItems');
    if (totalEl) totalEl.textContent = counts.total;
    const cleaningEl = document.getElementById('cleaningItems');
    if (cleaningEl) cleaningEl.textContent = counts.cleaning;
    const linenEl = document.getElementById('linenItems');
    if (linenEl) linenEl.textContent = counts.linen;
    const toiletryEl = document.getElementById('toiletryItems');
    if (toiletryEl) toiletryEl.textContent = counts.toiletry;
}

// Update chart data
function updateInventoryCharts(supplies) {
    // Prepare data for stock level chart
    const itemNames = supplies.map(s => s.item_name);
    const stockLevels = supplies.map(s => s.quantity);
    
    // Prepare data for category distribution
    const categories = ['Cleaning Supply', 'Linen', 'Toiletry'];
    const itemsPerCategory = categories.map(cat => 
        supplies.filter(s => s.category === cat).length
    );

    // Update stock level chart
    if (window.stockLevelChart) {
        updateChartData(window.stockLevelChart, {
            labels: itemNames,
            datasets: [{
                label: 'Current Stock Level',
                data: stockLevels,
                backgroundColor: chartColors.primary,
                borderColor: chartColors.primary,
                borderWidth: 1
            }]
        });
    }

    // Update category distribution chart
    if (window.categoryDistChart) {
        updateChartData(window.categoryDistChart, {
            labels: categories,
            datasets: [{
                label: 'Items per Category',
                data: itemsPerCategory,
                backgroundColor: Object.values(chartColors),
                borderWidth: 1
            }]
        });
    }

    // Also update the page-level supplyChart if present (pie showing Cleaning/Linen/Toiletry)
    if (window.supplyChart) {
        const counts = categories.map(cat => supplies.filter(s => s.category === cat).length);
        updateChartData(window.supplyChart, {
            labels: ['Cleaning', 'Linen', 'Toiletry'],
            datasets: [{ data: counts, backgroundColor: ['#4CAF50', '#f44336', '#ffc107'] }]
        });
    }
}

function createStockLevelChart(ctx) {
    const data = {
        labels: window.itemNames || [],
        datasets: [{
            label: 'Current Stock Level',
            data: window.stockLevels || [],
            backgroundColor: chartColors.primary,
            borderColor: chartColors.primary,
            borderWidth: 1
        }]
    };
    
    return createChart(ctx, 'bar', data);
}

function createCategoryDistributionChart(ctx) {
    const data = {
        labels: window.categories || [],
        datasets: [{
            label: 'Items per Category',
            data: window.itemsPerCategory || [],
            backgroundColor: Object.values(chartColors),
            borderWidth: 1
        }]
    };
    
    return createChart(ctx, 'pie', data, {
        plugins: {
            legend: {
                position: 'right'
            }
        }
    });
}
