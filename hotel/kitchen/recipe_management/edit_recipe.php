<?php
require_once(__DIR__ . '/../utils/db.php');
$message = '';

$id = $_GET['id'] ?? 0;
$id = (int)$id;
if (!$id) {
    header("Location: recipes.php");
    exit;
}

$categories = ['Appetizer','Main Course','Dessert','Beverage'];

$stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ?");
$stmt->execute([$id]);
$recipe = $stmt->fetch();

if (!$recipe) {
    header("Location: recipes.php");
    exit;
}

$stmt_ing = $pdo->prepare("SELECT * FROM ingredients WHERE recipe_id = ?");
$stmt_ing->execute([$id]);
$ingredients = $stmt_ing->fetchAll();
$ingredients_text = implode("\n", array_map(function($ing){
    $qty = isset($ing['quantity_needed']) ? $ing['quantity_needed'] : '';
    $unit = isset($ing['unit']) ? $ing['unit'] : '';
    return trim($ing['ingredient_name'] . ' ' . $qty . ' ' . $unit);
}, $ingredients));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_name = $_POST['recipe_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $price = $_POST['price'] ?? 0;
    $ingredients_input = $_POST['ingredients'] ?? '';

    if ($recipe_name) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE recipes SET recipe_name=?, category=?, instructions=?, price=? WHERE id=?");
        $stmt->execute([$recipe_name, $category, $instructions, $price, $id]);

        $pdo->prepare("DELETE FROM ingredients WHERE recipe_id=?")->execute([$id]);

        $ingredient_lines = array_filter(array_map('trim', explode("\n", $ingredients_input)));
        if ($ingredient_lines) {
            $stmt_ing_insert = $pdo->prepare("INSERT INTO ingredients (recipe_id, ingredient_name, quantity_needed, unit) VALUES (?, ?, ?, ?)");
            foreach ($ingredient_lines as $line) {
                preg_match('/^(.+?)\s+([\d\.]+)?\s*(\w+)?$/', $line, $matches);
                $name = $matches[1] ?? $line;
                $qty = $matches[2] ?? '';
                $unit = $matches[3] ?? '';
                $stmt_ing_insert->execute([$id, $name, $qty, $unit]);
            }
        }

        $pdo->commit();

        $message = "Recipe updated successfully!";
        $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ?");
        $stmt->execute([$id]);
        $recipe = $stmt->fetch();
        $stmt_ing->execute([$id]);
        $ingredients = $stmt_ing->fetchAll();
        $ingredients_text = implode("\n", array_map(function($ing){
            $qty = isset($ing['quantity_needed']) ? $ing['quantity_needed'] : '';
            $unit = isset($ing['unit']) ? $ing['unit'] : '';
            return trim($ing['ingredient_name'] . ' ' . $qty . ' ' . $unit);
        }, $ingredients));
    } else {
        $message = "Recipe name is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Recipe</title>
<link rel="stylesheet" href="/hotel/kitchen/recipe_management/edit_recipe.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Edit Recipe</h1>
      <p>Update the recipe details below.</p>
    </header>

    <?php if($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST" class="add-form">
      <label>Recipe Name</label>
      <input type="text" name="recipe_name" value="<?= htmlspecialchars($recipe['recipe_name']) ?>" required>

      <label>Category</label>
      <select name="category" required>
        <option value="">Select Category</option>
        <?php foreach($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= ($recipe['category'] === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Ingredients (one per line, e.g., "Sugar 2 tbsp")</label>
      <textarea name="ingredients" rows="5"><?= htmlspecialchars($ingredients_text) ?></textarea>

      <label>Instructions</label>
      <textarea name="instructions" rows="5"><?= htmlspecialchars($recipe['instructions']) ?></textarea>

      <label>Price</label>
      <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($recipe['price']) ?>">

      <div class="button-row">
        <button type="submit" class="update-btn"><i class="fas fa-save"></i> Update Recipe</button>
        <a href="/hotel/kitchen/recipe_management/recipes.php" class="cancel-btn"><i class="fas fa-times"></i> Cancel</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
