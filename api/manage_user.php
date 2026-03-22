<?php
require 'config.php';

$auth   = require_auth('admin');
if ($auth['adminLevel'] !== 1) respond(['success'=>false, 'message'=>'Restricted. Only Level 1 Admins can manage users.']);
$b      = body();
$action = $b['action'] ?? '';
$userId = intval($b['id'] ?? 0);

if (!$userId) respond(['success'=>false, 'message'=>'User ID required.']);
if (!in_array($action, ['delete', 'block'])) respond(['success'=>false, 'message'=>'Invalid action.']);

try {
    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM members WHERE id=?');
        $stmt->execute([$userId]);
        respond(['success'=>true, 'message'=>'User account deleted successfully.']);
    } elseif ($action === 'block') {
        // Change user password hash to a blocked status so they can't login or operate
        $stmt = db()->prepare("UPDATE members SET password_hash='BLOCKED_USER', role='member' WHERE id=?");
        $stmt->execute([$userId]);
        respond(['success'=>true, 'message'=>'User account blocked successfully.']);
    }
} catch (PDOException $e) {
    respond(['success'=>false, 'message'=>'Database error. Please try again.']);
}
