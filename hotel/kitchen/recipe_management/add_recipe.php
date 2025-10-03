<?php
session_start();
$message = '';
require_once(__DIR__ . '/../utils/db.php');

$categories = ['Appetizer','Main Course','Dessert','Beverage'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_name = $_POST['recipe_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $ingredients = $_POST['ingredients'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $preparation_time = $_POST['preparation_time'] ?? null;
    $price = $_POST['price'] ?? 0;

    if ($recipe_name) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO recipes (recipe_name, category, instructions, preparation_time, price, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$recipe_name, $category, $instructions, $preparation_time, $price]);
            $recipe_id = $pdo->lastInsertId();

            $ingredient_lines = array_filter(array_map('trim', explode("\n", $ingredients)));
            if ($ingredient_lines) {
                $stmt_ing = $pdo->prepare("INSERT INTO ingredients (recipe_id, ingredient_name, quantity_needed, unit, created_at) VALUES (?, ?, ?, ?, NOW())");
                foreach ($ingredient_lines as $line) {
                    preg_match('/^(.+?)\s+([\d\.]+)?\s*(\w+)?$/', $line, $matches);
                    $name = $matches[1] ?? $line;
                    $qty = $matches[2] ?? '';
                    $unit = $matches[3] ?? '';
                    $stmt_ing->execute([$recipe_id, $name, $qty, $unit]);
                }
            }

            $pdo->commit();
            $_SESSION['message'] = "Recipe added successfully!";
            header("Location: add_recipe.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error adding recipe.";
        }
    } else {
        $message = "Recipe name is required.";
    }
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add New Recipe</title>
<link rel="stylesheet" href="add_recipe.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Add New Recipe</h1>
      <p>Fill out the form to add a new recipe.</p>
    </header>

    <?php if($message): ?>
        <p class="message" style="color:green; font-weight:bold;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="form-container">
      <form method="POST" class="add-form">
        <label>Recipe Name</label>
        <input type="text" name="recipe_name" required>

        <label>Category</label>
        <select name="category" required>
          <option value="">Select Category</option>
          <?php foreach($categories as $cat): ?>
            <option value="<?= $cat ?>"><?= $cat ?></option>
          <?php endforeach; ?>
        </select>

        <label>Ingredients (one per line, e.g., "Sugar 2 tbsp")</label>
        <textarea name="ingredients" rows="5"></textarea>

        <label>Instructions</label>
        <textarea name="instructions" rows="5"></textarea>

        <label>Preparation Time (minutes)</label>
        <input type="number" name="preparation_time" min="1">

        <label>Price</label>
        <input type="number" step="0.01" name="price">

        <div class="form-actions">
          <button type="submit"><i class="fas fa-plus"></i> Add Recipe</button>
          <a href="recipes.php" class="module back cancel-btn">
            <i class="fas fa-times"></i> Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
