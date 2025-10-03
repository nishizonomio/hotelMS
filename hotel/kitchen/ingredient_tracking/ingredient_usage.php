<?php
require_once(__DIR__ . '/../utils/db.php');

$search_item = trim($_GET['item'] ?? '');

$params = [];
$where = "WHERE 1=1";
if ($search_item !== '') {
    $where .= " AND i.item_name LIKE :item";
    $params[':item'] = '%' . $search_item . '%';
}

$stmt = $pdo->prepare("
    SELECT 
        iu.usage_id,
        i.item_name,
        i.category,
        iu.used_qty,
        iu.used_by,
        iu.date_used
    FROM ingredient_usage iu
    INNER JOIN inventory i ON i.item_id = iu.item_id
    $where
    ORDER BY iu.date_used DESC
");
$stmt->execute($params);
$usageList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ingredient Usage Log</title>
<link rel="stylesheet" href="ingredients.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Ingredient Usage Log</h1>
    </header>

    <div class="search-container">
      <form method="GET" class="search-form">
        <input type="text" name="item" placeholder="Search ingredient" value="<?= htmlspecialchars($search_item) ?>">
        <button type="submit" class="btn">🔍 Search</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>Usage ID</th>
          <th>Ingredient</th>
          <th>Category</th>
          <th>Quantity Used</th>
          <th>Used By</th>
          <th>Date Used</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($usageList): ?>
          <?php foreach($usageList as $usage): ?>
          <tr>
            <td><?= (int)$usage['usage_id'] ?></td>
            <td><?= htmlspecialchars($usage['item_name']) ?></td>
            <td><?= htmlspecialchars($usage['category']) ?></td>
            <td><?= (int)$usage['used_qty'] ?></td>
            <td><?= htmlspecialchars($usage['used_by']) ?></td>
            <td><?= htmlspecialchars($usage['date_used']) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="text-align:center;">No ingredient usage found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
