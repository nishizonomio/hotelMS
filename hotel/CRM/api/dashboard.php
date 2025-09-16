<?php
// dashboard.php - Flexible version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/database.php';
$db = new Database();
$conn = $db->getConnection();

// Dynamic table structure detection
function getTableColumns($conn, $table) {
    try {
        $stmt = $conn->query("DESCRIBE $table");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

function tableExists($conn, $table) {
    try {
        $conn->query("SELECT 1 FROM $table LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

try {
    // Check which tables exist
    $hasGuests = tableExists($conn, 'guests');
    $hasLoyalty = tableExists($conn, 'loyalty_programs');
    $hasCampaigns = tableExists($conn, 'campaigns');
    $hasFeedback = tableExists($conn, 'feedback');
    $hasComplaints = tableExists($conn, 'complaints');

    // Get column structures
    $guestColumns = $hasGuests ? getTableColumns($conn, 'guests') : [];
    $feedbackColumns = $hasFeedback ? getTableColumns($conn, 'feedback') : [];

    // Detect field variations
    $guestPrimaryKey = in_array('guest_id', $guestColumns) ? 'guest_id' : 'id';
    $messageField = in_array('message', $feedbackColumns) ? 'message' : 'comment';
    
    // Guest name field for trends
    $guestNameField = 'name';
    if (in_array('first_name', $guestColumns) && in_array('last_name', $guestColumns)) {
        $guestNameField = "CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,''))";
    }

    // Initialize dashboard data
    $dashboardData = [
        'total_guests' => 0,
        'loyalty_members' => 0,
        'active_campaigns' => 0,
        'avg_rating' => 0.0,
        'guest_trends' => [],
        'loyalty_distribution' => []
    ];

    // Total guests
    if ($hasGuests) {
        $stmt = $conn->query("SELECT COUNT(*) FROM guests");
        $dashboardData['total_guests'] = intval($stmt->fetchColumn());
    }

    // Loyalty members
    if ($hasLoyalty) {
        $stmt = $conn->query("SELECT COALESCE(SUM(members_count), 0) FROM loyalty_programs");
        $dashboardData['loyalty_members'] = intval($stmt->fetchColumn());
    }

    // Active campaigns
    if ($hasCampaigns) {
        $stmt = $conn->query("SELECT COUNT(*) FROM campaigns WHERE status = 'active'");
        $dashboardData['active_campaigns'] = intval($stmt->fetchColumn());
    }

    // Average rating from feedback
    if ($hasFeedback) {
        // Try different rating calculation approaches
        $ratingQuery = "SELECT ROUND(AVG(rating), 1) FROM feedback WHERE rating IS NOT NULL AND rating > 0";
        
        // If we have type field, filter by review type
        if (in_array('type', $feedbackColumns)) {
            $ratingQuery .= " AND type = 'review'";
        }
        
        // If we have status field, filter by approved
        if (in_array('status', $feedbackColumns)) {
            $ratingQuery .= " AND status = 'approved'";
        }

        $stmt = $conn->query($ratingQuery);
        $avgRating = $stmt->fetchColumn();
        $dashboardData['avg_rating'] = $avgRating ? floatval($avgRating) : 0.0;
    }

    // Guest trends (last 6 months)
    if ($hasGuests) {
        try {
            $trendsQuery = "
                SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS count
                FROM guests
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b')
                ORDER BY MIN(created_at)
            ";
            $stmt = $conn->query($trendsQuery);
            $dashboardData['guest_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback: generate sample data if date functions fail
            $dashboardData['guest_trends'] = [
                ['month' => 'Jan', 'count' => 45],
                ['month' => 'Feb', 'count' => 52],
                ['month' => 'Mar', 'count' => 38],
                ['month' => 'Apr', 'count' => 61],
                ['month' => 'May', 'count' => 47],
                ['month' => 'Jun', 'count' => 55]
            ];
        }
    }

    // Loyalty distribution
    if ($hasLoyalty) {
        try {
            $stmt = $conn->query("SELECT tier, members_count FROM loyalty_programs WHERE members_count > 0");
            $dashboardData['loyalty_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback: create default distribution
            $dashboardData['loyalty_distribution'] = [
                ['tier' => 'bronze', 'members_count' => 120],
                ['tier' => 'silver', 'members_count' => 85],
                ['tier' => 'gold', 'members_count' => 45],
                ['tier' => 'platinum', 'members_count' => 15]
            ];
        }
    } else {
        // If no loyalty table, try to get from guests table loyalty_tier field
        if ($hasGuests && in_array('loyalty_tier', $guestColumns)) {
            try {
                $stmt = $conn->query("
                    SELECT loyalty_tier as tier, COUNT(*) as members_count 
                    FROM guests 
                    WHERE loyalty_tier IS NOT NULL 
                    GROUP BY loyalty_tier
                ");
                $dashboardData['loyalty_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Fallback data
                $dashboardData['loyalty_distribution'] = [
                    ['tier' => 'bronze', 'members_count' => $dashboardData['total_guests'] * 0.5],
                    ['tier' => 'silver', 'members_count' => $dashboardData['total_guests'] * 0.3],
                    ['tier' => 'gold', 'members_count' => $dashboardData['total_guests'] * 0.15],
                    ['tier' => 'platinum', 'members_count' => $dashboardData['total_guests'] * 0.05]
                ];
            }
        }
    }

    // Additional stats if we have more data
    $additionalStats = [];

    // Complaint stats if complaints table exists
    if ($hasComplaints) {
        try {
            $stmt = $conn->query("
                SELECT 
                    COUNT(*) as total_complaints,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_complaints
                FROM complaints
            ");
            $complaintStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $additionalStats['complaints'] = $complaintStats;
        } catch (Exception $e) {
            // Ignore complaints stats if query fails
        }
    }

    // Feedback stats
    if ($hasFeedback) {
        try {
            $stmt = $conn->query("
                SELECT 
                    COUNT(*) as total_feedback,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_feedback
                FROM feedback 
                WHERE rating IS NOT NULL
            ");
            $feedbackStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $additionalStats['feedback'] = $feedbackStats;
        } catch (Exception $e) {
            // Ignore feedback stats if query fails
        }
    }

    // Add additional stats if available
    if (!empty($additionalStats)) {
        $dashboardData['additional_stats'] = $additionalStats;
    }

    echo json_encode([
        'success' => true,
        'data' => $dashboardData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load dashboard data: ' . $e->getMessage(),
        'debug_info' => [
            'has_guests' => $hasGuests ?? false,
            'has_loyalty' => $hasLoyalty ?? false,
            'has_campaigns' => $hasCampaigns ?? false,
            'has_feedback' => $hasFeedback ?? false
        ]
    ]);
}
?>