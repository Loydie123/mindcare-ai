<?php
// /dashboard/get_notifications.php
session_start();
require_once __DIR__ . '/../config/dbcon.php';

// Admin-only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  http_response_code(403);
  echo '<p class="text-muted text-center mb-0">Forbidden</p>';
  exit;
}

// Mark all admin notifications as read when opening modal
if ($conn && !$conn->connect_errno) {
  $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
}

// Helper to format relative time
function _rel_time($dtstr) {
  if (!$dtstr) return '';
  try {
    $dt = new DateTime($dtstr);
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');
    $d = $dt->format('Y-m-d');
    if ($d === $today) return 'Today ' . $dt->format('H:i');
    if ($d === $yesterday) return 'Yesterday ' . $dt->format('H:i');
    $diff = $now->diff($dt);
    if ($diff->days <= 7) return $diff->days . ' day' . ($diff->days==1?'':'s') . ' ago';
    return $dt->format('M j, Y H:i');
  } catch (Exception $e) { return htmlspecialchars($dtstr); }
}

// Fetch latest admin notifications
$items = [];
if ($conn && !$conn->connect_errno) {
  $sql = "SELECT id, assessment_id, message, created_at, is_read FROM admin_notifications ORDER BY created_at DESC LIMIT 100";
  if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) {
      $items[] = $r;
    }
    $res->free();
  }
}

ob_start();
if (empty($items)) {
  echo '<p class="text-muted text-center mb-0">No notifications yet</p>';
} else {
  echo '<div class="list-group">';
  foreach ($items as $it) {
    $assessment_id = htmlspecialchars($it['assessment_id'] ?? '');
    $message = htmlspecialchars($it['message'] ?? '');
    $time = _rel_time($it['created_at'] ?? '');

    echo '<div class="list-group-item d-flex justify-content-between align-items-start">';
    echo '<div>';
    if ($assessment_id) {
      echo '<div class="fw-semibold">Assessment #' . $assessment_id . '</div>';
    }
    if ($message !== '') {
      echo '<div class="small text-muted mb-1" style="white-space: pre-line;">' . $message . '</div>';
    }
    echo '</div>';
    echo '<small class="text-muted ms-3">' . $time . '</small>';
    echo '</div>';
  }
  echo '</div>';
}
$html = ob_get_clean();

header('Content-Type: text/html; charset=UTF-8');
echo $html;