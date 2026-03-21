<?php
require 'config.php';

$b = body();
$id = $b['id'] ?? null;
$name = trim($b['name'] ?? '');
$group = trim($b['group'] ?? '');
$otherHandles = is_array($b['otherHandles'] ?? null) ? json_encode($b['otherHandles']) : '[]';
$hashtags = is_array($b['hashtags'] ?? null) ? json_encode($b['hashtags']) : '[]';
$platforms = is_array($b['platforms'] ?? null) ? json_encode($b['platforms']) : '{}';

if (!$id || !$name) respond(['success'=>false, 'message'=>'Invalid request']);

// If we allow updating group, we should include it.
// Admin ignores group update.
$upd = db()->prepare('UPDATE members SET full_name=?, group_name=?, other_handles=?, hashtags=?, platforms_json=? WHERE id=?');
$upd->execute([$name, $group, $otherHandles, $hashtags, $platforms, $id]);

$stmt = db()->prepare('SELECT * FROM members WHERE id=?');
$stmt->execute([$id]);
$u = $stmt->fetch();

$user = [
    'id' => $u['id'],
    'name' => $u['full_name'],
    'handle' => $u['main_handle'],
    'mainHandle' => $u['main_handle'],
    'group' => $u['group_name'],
    'role' => $u['role'],
    'otherHandles' => json_decode($u['other_handles'] ?: '[]'),
    'hashtags' => json_decode($u['hashtags'] ?: '[]'),
    'platforms' => json_decode($u['platforms_json'] ?: '{}')
];

respond(['success'=>true, 'user'=>$user]);
