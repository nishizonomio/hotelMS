// task_chart.js
const ctx = document.getElementById('taskChart').getContext('2d');
let taskChart;

function fetchTaskStats() {
    fetch('task_stats.php')
        .then(res => res.json())
        .then(taskData => {
            if (!taskChart) {
                taskChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(taskData),
                        datasets: [{
                            label: 'Task Status',
                            data: Object.values(taskData),
                            backgroundColor: ['#f39c12','#3498db','#2ecc71']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            } else {
                taskChart.data.labels = Object.keys(taskData);
                taskChart.data.datasets[0].data = Object.values(taskData);
                taskChart.update();
            }
        })
        .catch(err => console.error('Error fetching task stats:', err));
}

// Initial load
fetchTaskStats();
setInterval(fetchTaskStats, 5000); // optional auto-refresh
