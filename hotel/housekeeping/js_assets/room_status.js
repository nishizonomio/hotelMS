document.addEventListener("DOMContentLoaded", () => {
  // EDIT BUTTON: fill update form
  document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.getElementById("updateRoomId").value = btn.dataset.id || "";
      document.getElementById("updateStatus").value = btn.dataset.status || "Available";
      document.getElementById("updateRemarks").value = btn.dataset.remarks || "";
      // Normalize date to yyyy-mm-dd if needed
      const raw = (btn.dataset.lastcleaned || "").slice(0, 10);
      document.getElementById("updateLastCleaned").value = raw;
      window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    });
  });

  // (Optional) DELETE BUTTONS if you add them later
  document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.getAttribute("data-id");
      if (confirm("Are you sure you want to delete room " + id + "?")) {
        // TODO: Send AJAX to delete room
      }
    });
  });

  // CHART: build from data-* attributes
  const canvas = document.getElementById("statusChart");
  if (canvas && window.Chart) {
    const d = canvas.dataset;
    const data = [
      parseInt(d.available || "0", 10),
      parseInt(d.occupied || "0", 10),
      parseInt(d.cleaning || "0", 10),
      parseInt(d.maintenance || "0", 10),
    ];

    const ctx = canvas.getContext("2d");
    new Chart(ctx, {
      type: "pie",
      data: {
        labels: ["Available", "Occupied", "Cleaning", "Maintenance"],
        datasets: [{
          data,
          backgroundColor: ["#28a745", "#dc3545", "#ffc107", "#6c757d"]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              color: '#ffffff',  // White text color
              padding: 20,       // More padding between items
              font: {
                size: 14,       // Larger font size
                family: "'Outfit', sans-serif",
                weight: 'bold'  // Bold text
              },
              boxWidth: 40,     // Wider color boxes
              boxHeight: 20     // Taller color boxes
            }
          },
          datalabels: {
            color: '#ffffff',
            font: {
              weight: 'bold',
              size: 14
            },
            formatter: (value, ctx) => {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = Math.round((value / total) * 100);
              return `${value}\n(${percentage}%)`;
            }
          }
        }
      }
    });
  }
});



