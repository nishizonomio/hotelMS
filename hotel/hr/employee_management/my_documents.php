<?php
session_start();
include '../db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: employee_login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sign_contract'])) {
        $stmt = $conn->prepare("UPDATE staff SET contract_signed=1, contract_signed_at=NOW() WHERE staff_id=?");
        $stmt->bind_param("s", $staff_id);
        $stmt->execute();
        $message = "You have successfully signed the employment contract.";
    }
    if (isset($_POST['withdraw_contract'])) {
        $stmt = $conn->prepare("UPDATE staff SET contract_signed=0, contract_signed_at=NULL WHERE staff_id=?");
        $stmt->bind_param("s", $staff_id);
        $stmt->execute();
        $message = "You have withdrawn your signed contract.";
    }

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    function handleUpload($field) {
        global $uploadDir;
        if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === 0) {
            $path = $uploadDir . uniqid() . "_" . basename($_FILES[$field]['name']);
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $path)) return $path;
        }
        return null;
    }

    $sss = $_POST['sss_no'] ?? null;
    $philhealth = $_POST['philhealth_no'] ?? null;
    $pagibig = $_POST['pagibig_no'] ?? null;
    $tin = $_POST['tin_no'] ?? null;

    $nbi = handleUpload('nbi_clearance') ?? ($_POST['existing_nbi'] ?? null);
    $birth = handleUpload('birth_certificate') ?? ($_POST['existing_birth'] ?? null);
    $diploma = handleUpload('diploma') ?? ($_POST['existing_diploma'] ?? null);
    $tor = handleUpload('tor') ?? ($_POST['existing_tor'] ?? null);
    $barangay = handleUpload('barangay_clearance') ?? ($_POST['existing_barangay'] ?? null);
    $police = handleUpload('police_clearance') ?? ($_POST['existing_police'] ?? null);

    $check = $conn->prepare("SELECT id FROM employee_documents WHERE staff_id=?");
    $check->bind_param("s", $staff_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE employee_documents SET 
            sss_no=?, philhealth_no=?, pagibig_no=?, tin_no=?, 
            nbi_clearance=?, birth_certificate=?, diploma=?, tor=?, barangay_clearance=?, police_clearance=?
            WHERE staff_id=?");
        $stmt->bind_param(
            "sssssssssss",
            $sss, $philhealth, $pagibig, $tin,
            $nbi, $birth, $diploma, $tor, $barangay, $police,
            $staff_id
        );
    } else {
        $stmt = $conn->prepare("INSERT INTO employee_documents 
            (staff_id, sss_no, philhealth_no, pagibig_no, tin_no,
            nbi_clearance, birth_certificate, diploma, tor, barangay_clearance, police_clearance)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param(
            "sssssssssss",
            $staff_id, $sss, $philhealth, $pagibig, $tin,
            $nbi, $birth, $diploma, $tor, $barangay, $police
        );
    }

    $stmt->execute();
    $message = "Profile and documents updated successfully.";
}

$res = $conn->prepare("SELECT * FROM staff WHERE staff_id=?");
$res->bind_param("s", $staff_id);
$res->execute();
$staffResult = $res->get_result();
$staff = $staffResult->fetch_assoc();

$docsRes = $conn->prepare("SELECT * FROM employee_documents WHERE staff_id=?");
$docsRes->bind_param("s", $staff_id);
$docsRes->execute();
$docsResult = $docsRes->get_result();
$docs = $docsResult->fetch_assoc() ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submit Required Documents</title>
<link rel="stylesheet" href="../css/my_documents.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<body>
<a href="homepage.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
<div class="header-container">
  <h2>Submit Required Employment Documents</h2>
</div>
<div class="container">
  <form action="" method="POST" enctype="multipart/form-data">
    <div class="section">
      <h3>Employment Contract</h3>
      <?php 
      $contractFile = $staff['contract_file'] ?? '';
      $contractPath = '';
      if ($contractFile) {
          if (file_exists($contractFile)) $contractPath = $contractFile;
          elseif (file_exists('../contract/' . basename($contractFile))) $contractPath = '../contract/' . basename($contractFile);
          elseif (file_exists('../contracts/' . basename($contractFile))) $contractPath = '../contracts/' . basename($contractFile);
      }
      ?>
      <?php if ($contractPath): ?>
          <p><a href="<?= htmlspecialchars($contractPath) ?>" target="_blank" class="view-contract">View Contract</a></p>
      <?php else: ?>
          <p>No contract uploaded by HR yet.</p>
      <?php endif; ?>

      <?php if (!empty($staff['contract_file'])): ?>
          <?php if (empty($staff['contract_signed'])): ?>
              <button type="submit" name="sign_contract" class="btn">Sign Contract</button>
          <?php else: ?>
              <button type="submit" name="withdraw_contract" class="btn">Withdraw Contract</button>
              <p><strong>Signed on: <?= $staff['contract_signed_at'] ?></strong></p>
          <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="section">
      <h3>Government Numbers</h3>
      <div class="form-grid">
        <div><label>SSS Number</label><input type="text" name="sss_no" value="<?= htmlspecialchars($docs['sss_no'] ?? '') ?>"></div>
        <div><label>PhilHealth Number</label><input type="text" name="philhealth_no" value="<?= htmlspecialchars($docs['philhealth_no'] ?? '') ?>"></div>
        <div><label>PAG-IBIG Number</label><input type="text" name="pagibig_no" value="<?= htmlspecialchars($docs['pagibig_no'] ?? '') ?>"></div>
        <div><label>TIN</label><input type="text" name="tin_no" value="<?= htmlspecialchars($docs['tin_no'] ?? '') ?>"></div>
      </div>
    </div>

    <div class="section">
      <h3>Supporting Documents</h3>
      <div class="form-grid">
        <?php 
        $fields = ['nbi_clearance','birth_certificate','diploma','tor','barangay_clearance','police_clearance'];
        foreach ($fields as $field): 
            $existing = $docs[$field] ?? '';
        ?>
            <div>
                <label><?= ucwords(str_replace('_',' ',$field)) ?></label>
                <input type="file" name="<?= $field ?>">
                <input type="hidden" name="existing_<?= $field ?>" value="<?= htmlspecialchars($existing) ?>">
            </div>
        <?php endforeach; ?>
      </div>
    </div>
    <button type="submit" class="btn">Update Profile</button>
  </form>
</div>

<div class="modal" id="successModal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('successModal').style.display='none'">&times;</span>
    <p><?= htmlspecialchars($message) ?></p>
    <button class="btn" onclick="document.getElementById('successModal').style.display='none'">Exit</button>
  </div>
</div>
</body>
</html>