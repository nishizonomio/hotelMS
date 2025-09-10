// Staff Performance Charts and Data Handling
document.addEventListener('DOMContentLoaded', () => {
    // Initialize performance charts
    initializePerformanceCharts();
});

function initializePerformanceCharts() {
    const performanceCtx = document.getElementById('performanceChart')?.getContext('2d');
    const taskDistributionCtx = document.getElementById('taskDistributionChart')?.getContext('2d');
    
    if (performanceCtx) {
        createPerformanceChart(performanceCtx);
    }
    
    if (taskDistributionCtx) {
        createTaskDistributionChart(taskDistributionCtx);
    }
}

function createPerformanceChart(ctx) {
    const data = {
        labels: window.performanceLabels || [],
        datasets: [{
            label: 'Tasks Completed',
            data: window.performanceData || [],
            backgroundColor: chartColors.primary,
            borderColor: chartColors.primary,
            borderWidth: 1
        }]
    };
    
    createChart(ctx, 'bar', data);
}

function createTaskDistributionChart(ctx) {
    const data = {
        labels: window.taskTypes || [],
        datasets: [{
            label: 'Task Distribution',
            data: window.taskCounts || [],
            backgroundColor: Object.values(chartColors),
            borderWidth: 1
        }]
    };
    
    createChart(ctx, 'pie', data, {
        plugins: {
            legend: {
                position: 'right'
            }
        }
    });
}
