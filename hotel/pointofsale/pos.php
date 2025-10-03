<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title> Hotel Turista! </title>
  <link rel="stylesheet" href="/hotel/pointofsale/index.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Welcome to Hotel Turista! </h1>
        <p> Your one-stop dashboard for complete Hotel Management </p>
      </header>

      <div class="grid">
        <a href="/hotel/pointofsale/restaurant/restaurant.php" class="module">
          <i class="fas fa-concierge-bell"></i>
          <span>Restaurant/Buffet Billing</span>
        </a>
        <a href="minibar/minibar.php" class="module">
          <i class="fas fa-broom"></i>
          <span>Mini-bar Tracking</span>
        </a>
        <a href="room_dining/room_dining.php" class="module">
          <i class="fas fa-cash-register"></i>
          <span>In-room Dining Orders</span>
        </a>
        <a href="giftshop/giftshop.php" class="module">
          <i class="fas fa-file-invoice-dollar"></i>
          <span>Gift Shop Sales</span>
        </a>
        <a href="Lounge/lounge.php" class="module">
          <i class="fas fa-user-friends"></i>
          <span> Lounge/Bar POS </span>
        </a>
        <a href="#" id="openChartModal" class="module" style="justify-self:center; grid-column: 1 / -1; max-width: 260px;">
          <i class="fas fa-chart-line"></i>
          <span>Sales Chart</span>
        </a>
</div>
        <br>   

      <a href="../homepage/index.php" class="module" style="display:inline-block; background:rgba(255,255,255,0.15);">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Main Dashboard</span>
      </a>

      <!-- Chart Modal -->
      <div id="chartModal" style="display:none; position:fixed; inset:0; background: rgba(0,0,0,0.6); z-index: 9999; align-items:center; justify-content:center; padding: 20px;">
        <div style="background:#121212; color:#fff; width: 100%; max-width: 900px; border-radius: 12px; border:1px solid rgba(255,255,255,0.12); box-shadow: 0 6px 25px rgba(0,0,0,0.4);">
          <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.12);">
            <h2 style="font-size:1.2rem; margin:0;">x` Sales Chart</h2>
            <button id="closeChartModal" aria-label="Close" style="background:transparent; color:#fff; border:none; font-size:1.2rem; cursor:pointer;">✕</button>
          </div>
          <div style="padding:16px 20px;">
            <form id="chartForm" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; align-items:end;">
              <label style="display:flex; flex-direction:column; gap:6px; font-size:0.9rem;">
                <span>Source</span>
                <select id="sourceSelect" style="padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.18); background:#1b1b1b; color:#fff;">
                  <option value="restaurant">Restaurant</option>
                  
                  
                  <option value="minibar">Mini-bar</option>
                  <option value="room_dining">Room Dining</option>
                  <option value="all" selected>All POS</option>
                </select>
              </label>
              <label style="display:flex; flex-direction:column; gap:6px; font-size:0.9rem;">
                <span>Date From</span>
                <input id="dateFrom" type="date" style="padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.18); background:#1b1b1b; color:#fff;">
              </label>
              <label style="display:flex; flex-direction:column; gap:6px; font-size:0.9rem;">
                <span>Date To</span>
                <input id="dateTo" type="date" style="padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.18); background:#1b1b1b; color:#fff;">
              </label>
              <label style="display:flex; flex-direction:column; gap:6px; font-size:0.9rem;">
                <span>Chart Type</span>
                <select id="chartType" style="padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.18); background:#1b1b1b; color:#fff;">
                  <option value="line" selected>Line</option>
                  <option value="bar">Bar</option>
                  <option value="pie">Pie</option>
                </select>
              </label>
              <button type="submit" class="module" style="border:none; cursor:pointer; text-align:center;">Generate</button>
            </form>
            <div style="margin-top:16px; background:#0f0f0f; border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:12px;">
              <canvas id="salesChart" height="140"></canvas>
            </div>
          </div>
        </div>
      </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    (function(){
      const openBtn = document.getElementById('openChartModal');
      const modal = document.getElementById('chartModal');
      const closeBtn = document.getElementById('closeChartModal');
      const form = document.getElementById('chartForm');
      const canvas = document.getElementById('salesChart');
      let chartInstance = null;

      function openModal(){ modal.style.display = 'flex'; }
      function closeModal(){ modal.style.display = 'none'; }

      if (openBtn) openBtn.addEventListener('click', function(e){ e.preventDefault(); openModal(); });
      if (closeBtn) closeBtn.addEventListener('click', function(){ closeModal(); });
      if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

      function generateLabels(dateFrom, dateTo) {
        const labels = [];
        if (!dateFrom || !dateTo) {
          return ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        }
        const start = new Date(dateFrom);
        const end = new Date(dateTo);
        for (let d = new Date(start); d <= end; d.setDate(d.getDate()+1)) {
          labels.push(d.toISOString().slice(0,10));
        }
        return labels.slice(0, 31);
      }

      function generateData(len) {
        const data = [];
        for (let i = 0; i < len; i++) {
          data.push(Math.round(2000 + Math.random()*8000));
        }
        return data;
      }

      function buildDataset(source, length) {
        const base = generateData(length);
        const colorMap = {
          restaurant: '#4caf50',
          
          
          minibar: '#e91e63',
          room_dining: '#9c27b0',
          all: '#ffd700'
        };
        return {
          label: (source === 'all' ? 'All POS' : source.replace('_',' ').replace(/\b\w/g, c=>c.toUpperCase())) + ' Sales',
          data: base,
          backgroundColor: colorMap[source] + '33',
          borderColor: colorMap[source],
          borderWidth: 2,
          tension: 0.3
        };
      }

      function renderChart(type, labels, dataset) {
        if (chartInstance) {
          chartInstance.destroy();
        }
        const data = {
          labels: labels,
          datasets: type === 'pie' ? [
            { label: dataset.label, data: dataset.data.slice(0, Math.min(6, labels.length)), backgroundColor: ['#4caf50','#ff9800','#03a9f4','#e91e63','#9c27b0','#ffd700'] }
          ] : [dataset]
        };
        const options = {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top', labels: { color: '#fff' } },
            tooltip: { enabled: true }
          },
          scales: type === 'pie' ? {} : {
            x: { ticks: { color: '#ddd' }, grid: { color: 'rgba(255,255,255,0.08)' } },
            y: { ticks: { color: '#ddd' }, grid: { color: 'rgba(255,255,255,0.08)' } }
          }
        };
        const ctx = canvas.getContext('2d');
        chartInstance = new Chart(ctx, { type: type, data: data, options: options });
      }

      if (form) form.addEventListener('submit', function(e){
        e.preventDefault();
        const source = document.getElementById('sourceSelect').value;
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const chartType = document.getElementById('chartType').value;

        const labels = generateLabels(dateFrom, dateTo);
        const dataset = buildDataset(source, labels.length || 7);
        renderChart(chartType, labels, dataset);
      });

      // Auto-open with a default chart for convenience
      window.addEventListener('load', function(){
        const labels = generateLabels(null, null);
        const dataset = buildDataset('all', labels.length);
        openModal();
        renderChart('line', labels, dataset);
      });
    })();
  </script>
</body>
</html>
