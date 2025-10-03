<?php
require '../db.php';

$categories = [
   'Hotel Supplies','Cleaning & Sanitation','Utility Products','Office Supplies','Kitchen Equipment','Furniture & Fixtures','Toiletries','Laundry & Linen','Beverage','Meat','Seafood','Vegetable','Fruit','Dairy','Seasoning','Grain','Beverage','Spice','Furniture & Fixtures','Electrical & Lighting','Plumbing Supplies','HVAC & Equipment Parts','Paint & Repair Materials','Tools & Hardware','Others'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name = trim($_POST['supplier_name']);
    $category = trim($_POST['category'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $errors = [];

    if (empty($supplier_name)) $errors[] = "Supplier name is required.";
    if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (!empty($contact_phone)) {
        if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contact_phone)) $errors[] = "Invalid Philippine phone number. Use 09xxxxxxxxx or +639xxxxxxxxx.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO suppliers (supplier_name, category, contact_person, email, phone, address) 
                               VALUES (:supplier_name, :category, :contact_person, :email, :phone, :address)");
        $stmt->execute([
            ':supplier_name' => $supplier_name,
            ':category' => $category,
            ':contact_person' => $contact_name,
            ':email' => $contact_email,
            ':phone' => $contact_phone,
            ':address' => $address
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
<title>Add Supplier</title>
<link rel="stylesheet" href="add_supplier.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>Add Supplier</h1>
      <p>Fill out the details below to add a new supplier.</p>
    </header>

    <?php if (!empty($errors)): ?>
      <div class="error-message">
        <?php foreach ($errors as $err): ?>
          <p><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="add-form">
        <input type="text" name="supplier_name" placeholder="Supplier Name" required value="<?= htmlspecialchars($_POST['supplier_name'] ?? '') ?>">

        <select name="category" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="contact_name" placeholder="Contact Name" value="<?= htmlspecialchars($_POST['contact_name'] ?? '') ?>">
        <input type="email" name="contact_email" placeholder="Email" value="<?= htmlspecialchars($_POST['contact_email'] ?? '') ?>">
        <input type="text" name="contact_phone" placeholder="Phone (09xxxxxxxxx or +639xxxxxxxxx)" value="<?= htmlspecialchars($_POST['contact_phone'] ?? '') ?>">
        <input type="text" name="address" placeholder="Address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">

        <div class="form-buttons">
          <button type="submit"><i class="fas fa-plus"></i> Submit</button>
          <a href="suppliers.php">
            <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
          </a>
        </div>
    </form>
  </div>
</div>
</body>
</html>
