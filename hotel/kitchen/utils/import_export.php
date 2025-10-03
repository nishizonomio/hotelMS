<?php
require_once(__DIR__ . '/../utils/db.php');
$message = '';

if (isset($_POST['import']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($file, 'r')) !== false) {
        fgetcsv($handle);
        $imported = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if (count($data) < 5) continue;
            $recipe_name = trim($data[0]);
            if ($recipe_name === '') continue;
            $category = trim($data[1] ?? '');
            $ingredients = trim($data[2] ?? '');
            $instructions = trim($data[3] ?? '');
            $price = is_numeric($data[4] ?? null) ? $data[4] : 0;
            $stmt = $pdo->prepare("INSERT INTO recipes (recipe_name, category, ingredients, instructions, price, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$recipe_name, $category, $ingredients, $instructions, $price]);
            $imported++;
        }
        fclose($handle);
        $message = "$imported recipes imported successfully!";
    }
}

if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="recipes_export.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Recipe Name', 'Category', 'Ingredients', 'Instructions', 'Price']);
    $stmt = $pdo->query("SELECT recipe_name, category, ingredients, instructions, price FROM recipes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Import / Export Recipes</title>
<link rel="stylesheet" href="/hotel/kitchen/utils/import_export.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Import / Export Recipes</h1>
    </header>
    <?php if($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="add-form">
      <input type="file" name="csv_file" required>
      <button type="submit" name="import" class="action-btn import-btn"><i class="fas fa-file-import"></i> Import CSV</button>
  <a href="/hotelMS/hotel/kitchen/utils/import_export.php?export=1" class="action-btn export-btn"><i class="fas fa-file-export"></i> Export CSV</a>
    </form>
  <a href="/hotel/kitchen/recipe_management/recipes.php" class="module back cancel-btn">
      <i class="fas fa-times"></i> Cancel
    </a>
  </div>
</div>
</body>
</html>
