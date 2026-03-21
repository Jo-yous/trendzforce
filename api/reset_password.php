<?php
require 'config.php';

$b = body();
$name = trim($b['name'] ?? '');
$handle = trim(strtolower($b['handle'] ?? ''));
$group = trim($b['group'] ?? '');
$newPass = $b['newPassword'] ?? '';

if (!$name || !$handle || !$newPass) respond(['success'=>false, 'message'=>'Required fields missing.']);
if (strlen($newPass) < 6) respond(['success'=>false, 'message'=>'Password must be at least 6 characters.']);

if (!str_starts_with($handle, '@')) $handle = '@'.$handle;

// Check user identity
$stmt = db()->prepare('SELECT id, full_name, group_name FROM members WHERE LOWER(main_handle)=? LIMIT 1');
$stmt->execute([$handle]);
$user = $stmt->fetch();

if (!$user) {
    respond(['success'=>false, 'message'=>'Account not found. Check your handle.']);
}

if (strcasecmp($user['full_name'], $name) !== 0) {
    respond(['success'=>false, 'message'=>'Identity verification failed. Name does not match.']);
}

if ((string)$user['group_name'] !== (string)$group) {
    respond(['success'=>false, 'message'=>'Identity verification failed. Group does not match.']);
}

// All matched, reset password
$hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost'=>12]);
$upd = db()->prepare('UPDATE members SET password_hash=? WHERE id=?');
$upd->execute([$hash, $user['id']]);

respond(['success'=>true, 'message'=>'Password reset successfully.']);
