  <?php
  require '../db.php';

  if (!isset($pdo)) {
      die("Database connection failed.");
  }

  if (isset($_GET['delete_id'])) {
      $delete_id = (int) $_GET['delete_id'];
      $stmt = $pdo->prepare("UPDATE suppliers SET is_active = 0 WHERE supplier_id = :id");
      $stmt->execute([':id' => $delete_id]);
      header("Location: suppliers.php");
      exit;
  }

  $show_table = isset($_POST['search']);
  $conditions = ["is_active = 1"];

  if (!empty($_POST['supplier_name'])) {
      $supplier_name = $_POST['supplier_name'];
      $conditions[] = "supplier_name LIKE :supplier_name";
  }

  $where = "WHERE " . implode(" AND ", $conditions);

  $query = "SELECT supplier_id, supplier_name, contact_person, email, phone, address
            FROM suppliers
            $where
            ORDER BY supplier_name ASC";

  $stmt = $pdo->prepare($query);

  if (!empty($_POST['supplier_name'])) {
      $stmt->bindValue(':supplier_name', "%$supplier_name%");
  }

  $stmt->execute();
  $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <title>Suppliers</title>
  <link rel="stylesheet" href="suppliers.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  </head>
  <body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Suppliers</h1>
        <p>View and manage all suppliers in real-time.</p>
      </header>

      <div class="search-container">
        <form method="POST" class="search-form">
          <a href="../inventory.php">
              <button type="button"><i class="fas fa-arrow-left"></i> Back to Inventory</button>
          </a>
          <input type="text" name="supplier_name" placeholder="Search by supplier" value="<?= htmlspecialchars($_POST['supplier_name'] ?? '') ?>">
          <button type="submit" name="search">🔍 Search</button>
          <a href="add_supplier.php">
              <button type="button"><i class="fas fa-plus"></i> Add Supplier</button>
          </a>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Supplier Name</th>
            <th>Contact Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($suppliers as $sup): ?>
          <tr>
            <td><?= htmlspecialchars($sup['supplier_id']) ?></td>
            <td><?= htmlspecialchars($sup['supplier_name']) ?></td>
            <td><?= htmlspecialchars($sup['contact_person'] ?? '-') ?></td>
            <td><?= htmlspecialchars($sup['email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($sup['phone'] ?? '-') ?></td>
            <td><?= htmlspecialchars($sup['address'] ?? '-') ?></td>
            
            <td>
              <a href="edit_supplier.php?id=<?= $sup['supplier_id'] ?>">
                <button type="button" class="edit-btn"><i class="fas fa-edit"></i> Edit</button>
              </a>
              <a href="suppliers.php?delete_id=<?= $sup['supplier_id'] ?>" onclick="return confirm('Are you sure you want to remove this supplier?');">
                <button type="button" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  </body>
  </html>
