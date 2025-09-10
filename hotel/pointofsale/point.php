<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title> Point Of Sale | Hotel La Vista</title>
  <link rel="stylesheet" href="../index.css"> 
  <link rel="stylesheet" href="css/POS.css"> 
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Point Of Sale Management</h1>
        <p>It serves as the central hub where customers complete their purchases, whether through cash, credit cards, or digital wallets.</p>
      </header>

      <div class="grid">
        <a href="restaurant.php" class="module card">
          <i class="fas fa-utensils fa-2x"></i>
          <span>Restaurant/Buffet</span>
        </a>
        <a href="minibar.php" class="module card">
          <i class="fas fa-glass-martini-alt fa-2x"></i>
          <span>Mini-bar Tracking</span>
        </a>
        <a href="In-room.php" class="module card">
          <i class="fas fa-concierge-bell fa-2x"></i>
          <span>In-room dining Orders</span>
        </a>
        <a href="giftshop.php" class="module card">
          <i class="fas fa-gift fa-2x"></i>
          <span>Gift Shop Sale </span>
        </a>
        <a href="loungebar.php" class="module card">
          <i class="fas fa-cocktail fa-2x"></i>
          <span>Lounge/Bar</span>
        </a>
      </div>

      <footer>
        <a href="index.php" class="back-btn">
          <i class="fas fa-arrow-left"></i> Back to Home
        </a>
      </footer>
    </div>
  </div>
</body>
<style>
body {
  font-family: 'Outfit', sans-serif;
  background: url("hotel_room.jpg") no-repeat center center fixed;
  background-size: cover;
  margin: 0;
  padding: 0;
}

.overlay {
  background: rgba(0, 0, 0, 0.6);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.container {
  text-align: center;
  color: white;
  max-width: 1000px;
  width: 90%;
  padding: 30px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 25px;
  margin-top: 40px;
}

.module {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  border-radius: 12px;
  padding: 30px 20px;
  text-decoration: none;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.module:hover {
  background: rgba(255, 255, 255, 0.18);
  transform: translateY(-5px) scale(1.03);
}

footer {
  margin-top: 40px;
}

.back-btn {
  display: inline-block;
  background: #ffc107;
  color: #333;
  padding: 10px 18px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  transition: 0.3s;
}

.back-btn:hover {
  background: #e0a800;
  color: #fff;
}
</style>

</html>
</body>
</html>