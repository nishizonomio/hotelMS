<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu Dashboard - Hotel La Vista</title>
    <link rel="stylesheet" href="menu.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
    <div class="container">
        <header>
            <h1>DISPLAY MENU ITEMS</h1>
            <p>View items available per category</p>
        </header>

        <div class="grid top-row">
            <a href="restaurant_menu.php" class="module">
                <i class="fas fa-utensils"></i>
                <span>Restaurant</span>
            </a>
            <a href="minibar_menu.php" class="module">
                <i class="fas fa-wine-bottle"></i>
                <span>Mini Bar</span>
            </a>
        </div>

        <div class="grid bottom-row">
            <a href="loungebar_menu.php" class="module">
                <i class="fas fa-cocktail"></i>
                <span>Lounge Bar</span>
            </a>
            <a href="giftstore_menu.php" class="module">
                <i class="fas fa-gift"></i>
                <span>Gift Store</span>
            </a>
        </div>

        <a href="../pos.php" class="module back">
            <i class="fas fa-arrow-left"></i>
            <span>Back to POS Dashboard</span>
        </a>
    </div>
</div>
</body>
</html>
