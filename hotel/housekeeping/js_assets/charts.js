// Common chart configuration and utilities
const chartColors = {
    primary: '#4e73df',
    success: '#1cc88a',
    info: '#36b9cc',
    warning: '#f6c23e',
    danger: '#e74a3b',
    secondary: '#858796',
    light: '#f8f9fc',
    dark: '#5a5c69'
};

const defaultChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#fff'
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                color: '#fff'
            },
            grid: {
                color: 'rgba(255,255,255,0.1)'
            }
        },
        x: {
            ticks: {
                color: '#fff'
            },
            grid: {
                color: 'rgba(255,255,255,0.1)'
            }
        }
    }
};

// Utility function to create a new chart
function createChart(ctx, type, data, customOptions = {}) {
    return new Chart(ctx, {
        type: type,
        data: data,
        options: { ...defaultChartOptions, ...customOptions }
    });
}

// Utility function to update chart data
function updateChartData(chart, newData) {
    chart.data = newData;
    chart.update();
}
