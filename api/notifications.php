<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get':
        $limit = intval($_GET['limit'] ?? 10);
        $notifications = getNotifications($conn, $user_id, $limit);
        $unread = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
        $unread->bind_param("i", $user_id);
        $unread->execute();
        $unread_count = $unread->get_result()->fetch_assoc()['c'];
        
        echo json_encode(['success' => true, 'notifications' => $notifications, 'unread_count' => $unread_count]);
        break;
        
    case 'mark_read':
        $notif_id = intval($_POST['notification_id'] ?? 0);
        if ($notif_id > 0) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notif_id, $user_id);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'mark_all_read':
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
