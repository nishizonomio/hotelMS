

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title> Hotel La Vista </title>
  <link rel="stylesheet" href="index.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container"> 
      <header>
        <h1>Welcome to Hotel La Vista! </h1>
        <p> Your one-stop dashboard for complete Hotel Management </p>
      </header>

      <div class="grid">
        <a href="reservation.php" class="module">
          <i class="fas fa-concierge-bell"></i>
          <span>Reservation & Front Desk</span>
        </a>
        <a href="housekeeping.php" class="module">
          <i class="fas fa-broom"></i>
          <span>Housekeeping</span>
        </a>
        <a href="point.php" class="module" >
          <i class="fas fa-cash-register"></i>
          <span>Point of Sale</span>
        </a>
        <a href="billing.php" class="module">
          <i class="fas fa-file-invoice-dollar"></i>
          <span>Billing & Payments</span>
        </a>
        <a href="crm.php" class="module">
          <i class="fas fa-user-friends"></i>
          <span>Guest Relationship Management </span>
        </a>
        <a href="hr.php" class="module">
          <i class="fas fa-users-cog"></i>
          <span>HR & Staff</span>
        </a>
        <a href="inventory.php" class="module">
          <i class="fas fa-boxes"></i>
          <span>Inventory & Procurement</span>
        </a>
        <a href="kitchen.php" class="module">
          <i class="fas fa-utensils"></i>
          <span>Kitchen & Restaurant </span>
        </a>
        <a href="maintenance.php" class="module">
          <i class="fas fa-tools"></i>
          <span>Maintenance</span>
        </a>
        <a href="reports.php" class="module">
          <i class="fas fa-chart-line"></i>
          <span>Reports & Analytics</span>
        </a>
      </div>
    </div>
  </div>
</body>
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body, html {
  height: 100%;
  font-family: 'Outfit', sans-serif;
  background: url('hotel_room.jpg') no-repeat center center fixed;
  background-size: cover;
}


.overlay {
  background: rgba(0, 0, 0, 0.65);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center; 
  align-items: center;
  padding: 40px 20px;
}


.container {
  max-width: 1100px;
  width: 100%;
  text-align: center;
  color: #fff;
}


header h1 {
  font-size: 3rem;
  font-weight: 700;
  letter-spacing: 1px;
  margin-bottom: 10px;
}

header p {
  font-size: 1.1rem;
  opacity: 0.85;
  margin-bottom: 50px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr); 
  gap: 20px;
  max-width: 1200px; 
  margin: 0 auto;
  padding: 0 20px;
}


.module {
  background: rgba(255, 255, 255, 0.08);
  padding: 18px;
  border-radius: 12px;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  text-decoration: none;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 6px 25px rgba(0, 0, 0, 0.25);
  transition: all 0.3s ease;
}

.module:hover {
  background: rgba(255, 255, 255, 0.18);
  transform: translateY(-5px) scale(1.03);
}

.module i {
  font-size: 28px;
  margin-bottom: 10px;
  display: block;
  color: #ffd700;
}

.module span {
  font-size: 0.9rem;
  font-weight: 600;
  display: block;
}
</style>
</html>
