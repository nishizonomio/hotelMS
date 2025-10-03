<?php
require_once(__DIR__ . '/../utils/db.php');

$stmt = $pdo->query("SELECT * FROM recipes ORDER BY RAND() LIMIT 1");
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

$ingredient_text = '';
if ($recipe) {
    $ing_stmt = $pdo->prepare("SELECT quantity_needed, unit, ingredient_name FROM ingredients WHERE recipe_id = ?");
    $ing_stmt->execute([$recipe['id']]);
    $ingredients = $ing_stmt->fetchAll(PDO::FETCH_ASSOC);
    $ingredient_text = implode("; ", array_map(fn($i) => trim(($i['quantity_needed'] ?? '') . ' ' . ($i['unit'] ?? '') . ' ' . $i['ingredient_name']), $ingredients));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recipe Roulette</title>
<link rel="stylesheet" href="/hotel/kitchen/activities/roulette.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Recipe Roulette</h1>
      <p>Feeling stuck? Get a random recipe for inspiration!</p>
    </header>
    <?php if($recipe): ?>
      <div class="roulette-result">
        <h2><?= htmlspecialchars($recipe['recipe_name']) ?></h2>
        <p><strong>Category:</strong> <?= htmlspecialchars($recipe['category']) ?></p>
        <p><strong>Ingredients:</strong><br><?= nl2br(htmlspecialchars($ingredient_text)) ?></p>
        <p><strong>Instructions:</strong><br><?= nl2br(htmlspecialchars($recipe['instructions'])) ?></p>
        <p><strong>Price:</strong> ₱<?= number_format($recipe['price'],2) ?></p>
      </div>
      <div class="button-row" style="display:flex; gap:12px; margin-top:18px;">
        <a href="roulette.php" class="module-btn">🎲 Try Another</a>
        <a href="/hotel/kitchen/recipe_management/recipes.php" class="module-btn"><i class="fas fa-arrow-left"></i> Back to Recipes</a>
      </div>
    <?php else: ?>
      <p style="text-align:center; margin-top:20px;">No recipes found.</p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
