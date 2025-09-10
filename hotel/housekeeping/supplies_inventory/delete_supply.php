<?php
// Ensure the script returns only JSON even if PHP emits warnings
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connector/db_connect.php';
include __DIR__ . '/../repo/SupplyRepository.php';
include 'SupplyService.php';

$db = new Database();
$conn = $db->getConnection();
$repo = new SupplyRepository($conn);
$service = new SupplyService($repo);

$response = [
    'success' => false,
    'message' => 'Unknown error'
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Handle single item delete
    if (isset($_POST['item_id'])) {
        $service->delete((int)$_POST['item_id']);
    }
    // Handle bulk delete
    else if (isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        foreach ($_POST['selected_items'] as $item_id) {
            $service->delete((int)$item_id);
        }
    } else {
        throw new Exception('No items specified for deletion');
    }

    // Successful response
    $response = [
        'success' => true,
        'counts' => $service->counts(),
        'supplies' => $service->list()
    ];
} catch (Throwable $e) {
    http_response_code(400);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Discard any accidental output (warnings, HTML) and emit JSON only
ob_end_clean();
echo json_encode($response);
exit;
?>
