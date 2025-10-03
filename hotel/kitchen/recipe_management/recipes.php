<?php
require '../utils/db.php';

$search_recipe = $_GET['recipe_name'] ?? '';
$search_category = $_GET['category'] ?? '';
$params = [];
$where = [];

if ($search_recipe) {
    $where[] = "r.recipe_name LIKE :recipe_name";
    $params[':recipe_name'] = "%$search_recipe%";
}
if ($search_category) {
    $where[] = "r.category = :category";
    $params[':category'] = $search_category;
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT r.id, r.recipe_name, r.category, r.instructions, r.price, r.created_at,
           GROUP_CONCAT(CONCAT(i.ingredient_name, ' ', i.quantity_needed, ' ', i.unit) SEPARATOR '\n') AS ingredients_list
    FROM recipes r
    LEFT JOIN ingredients i ON r.id = i.recipe_id
    $where_sql
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = ['Appetizer','Main Course','Dessert','Beverage'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Recipes</title>
<link rel="stylesheet" href="/hotel/kitchen/recipe_management/recipes.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.expandable {
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.expandable.expanded {
    white-space: pre-wrap;
}
</style>
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Recipe & Menu Management</h1>
      <?php
        if(!empty($recipes)) {
            $menuItems = array_map(fn($r) => $r['recipe_name'], $recipes);
            shuffle($menuItems);
            $todayMenu = array_slice($menuItems, 0, min(5, count($menuItems)));
            echo '<p>Menu for Today: ' . implode(', ', $todayMenu) . '.</p>';
        } else {
            echo '<p>Create, edit, and manage all your recipes efficiently.</p>';
        }
      ?>
    </header>

    <div class="search-container">
      <form method="GET" class="search-form">
        <a href="../kitchen.php" class="module-btn"><i class="fas fa-arrow-left"></i> Back to Kitchen</a>
        <a href="add_recipe.php" class="module-btn">Add Recipe</a>
        <input type="text" name="recipe_name" placeholder="Search recipe" value="<?= htmlspecialchars($search_recipe) ?>">
        <select name="category">
          <option value="">All Categories</option>
          <?php foreach($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= ($search_category === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">🔍 Search</button>
        <a href="../utils/import_export.php" class="module-btn">Import/Export</a>
        <a href="../activities/roulette.php" class="module-btn roulette-btn">Roulette Inspiration</a>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>Recipe Name</th>
          <th>Category</th>
          <th>Ingredients</th>
          <th>Instructions</th>
          <th>Price</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($recipes)): ?>
        <tr>
          <td colspan="6" style="text-align:center;">No recipes found.</td>
        </tr>
      <?php else: ?>
        <?php foreach($recipes as $r): 
          $id = (int)$r['id'];
          $price = number_format($r['price'] ?? 0, 2);
          $ingredient_text = $r['ingredients_list'] ?? '';
        ?>
        <tr>
          <td><?= htmlspecialchars($r['recipe_name']) ?></td>
          <td><?= htmlspecialchars($r['category']) ?></td>
          <td>
            <div class="expandable" data-truncated="<?= nl2br(htmlspecialchars(mb_strimwidth($ingredient_text, 0, 50, '...'))) ?>" data-full="<?= nl2br(htmlspecialchars($ingredient_text)) ?>">
              <?= nl2br(htmlspecialchars(mb_strimwidth($ingredient_text, 0, 50, '...'))) ?>
            </div>
          </td>
          <td>
            <div class="expandable" data-truncated="<?= nl2br(htmlspecialchars(mb_strimwidth($r['instructions'], 0, 50, '...'))) ?>" data-full="<?= nl2br(htmlspecialchars($r['instructions'])) ?>">
              <?= nl2br(htmlspecialchars(mb_strimwidth($r['instructions'], 0, 50, '...'))) ?>
            </div>
          </td>
          <td>₱<?= $price ?></td>
          <td>
            <a href="edit_recipe.php?id=<?= $id ?>" class="edit-btn">Edit</a>
            <form method="POST" action="delete_recipe.php" onsubmit="return confirm('Are you sure you want to delete this recipe?');" style="display:inline-block; margin:0;">
              <input type="hidden" name="id" value="<?= $id ?>">
              <button type="submit" class="delete-btn">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.querySelectorAll('.expandable').forEach(el => {
    let truncated = el.dataset.truncated;
    let full = el.dataset.full;
    el.addEventListener('click', () => {
        if(el.classList.contains('expanded')) {
            el.innerHTML = truncated;
            el.classList.remove('expanded');
        } else {
            el.innerHTML = full;
            el.classList.add('expanded');
        }
    });
});
</script>
</body>
</html>
