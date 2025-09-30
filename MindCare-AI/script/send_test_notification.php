<?php
session_start();
require_once __DIR__ . '/../config/dbcon.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'A user';

// Get admin user ID
$adminQuery = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
$adminResult = $conn->query($adminQuery);
if (!$adminResult || $adminResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No admin user found']);
    exit;
}
$adminUser = $adminResult->fetch_assoc();
$adminId = $adminUser['id'];

// Create test notification
$message = "[TEST] Test notification from " . htmlspecialchars($userName);
$sql = "INSERT INTO admin_notifications (user_id, assessment_id, message, is_read) VALUES (?, 1, ?, 0)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $userId, $message);

header('Content-Type: application/json');
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create notification']);
}
?>
