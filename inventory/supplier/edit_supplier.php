<?php
require '../db.php';

$categories = [
    'Hotel Supplies',
    'Foods & Beverages',
    'Cleaning & Sanitation',
    'Utility Products',
    'Office Supplies',
    'Kitchen Equipment',
    'Furniture & Fixtures',
    'Laundry & Linen',
    'Others'
];

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: suppliers.php");
    exit;
}

$stmt = $pdo->prepare("SELECT supplier_id, supplier_name, category, contact_person, phone, email, address FROM suppliers WHERE supplier_id = :id");
$stmt->execute([':id' => $id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    header("Location: suppliers.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name = trim($_POST['supplier_name']);
    $category = trim($_POST['category'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($supplier_name)) $errors[] = "Supplier name is required.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (!empty($phone) && !preg_match('/^(09\d{9}|\+639\d{9})$/', $phone)) $errors[] = "Phone number must be in Philippine format (09XXXXXXXXX or +639XXXXXXXXX).";

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE suppliers 
                               SET supplier_name = :supplier_name, 
                                   category = :category,
                                   contact_person = :contact_person, 
                                   phone = :phone, 
                                   email = :email, 
                                   address = :address 
                               WHERE supplier_id = :id");
        $stmt->execute([
            ':supplier_name' => $supplier_name,
            ':category' => $category,
            ':contact_person' => $contact_person,
            ':phone' => $phone,
            ':email' => $email,
            ':address' => $address,
            ':id' => $id
        ]);
        header("Location: suppliers.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Supplier</title>
<link rel="stylesheet" href="add_supplier.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Edit Supplier</h1>
      <p>Update the details below for this supplier.</p>
    </header>

    <?php if (!empty($errors)): ?>
      <div class="error-messages">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="add-form">
        <input type="text" name="supplier_name" placeholder="Supplier Name" value="<?= htmlspecialchars($supplier['supplier_name']) ?>" required>
        
        <select name="category" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($supplier['category'] === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="contact_person" placeholder="Contact Person" value="<?= htmlspecialchars($supplier['contact_person']) ?>">
        <input type="text" name="phone" placeholder="Phone" value="<?= htmlspecialchars($supplier['phone']) ?>">
        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($supplier['email']) ?>">
        <input type="text" name="address" placeholder="Address" value="<?= htmlspecialchars($supplier['address']) ?>">

        <div class="form-buttons">
          <button type="submit"><i class="fas fa-edit"></i> Update</button>
          <a href="suppliers.php">
            <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
          </a>
        </div>
    </form>
  </div>
</div>
</body>
</html>
