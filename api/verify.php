<?php
require 'config.php';

$auth = require_auth('leader','admin');
$b    = body();
$id   = intval($b['id'] ?? 0);
$status = $b['status'] ?? 'leader_verified';

if (!in_array($status,['leader_verified','rejected']))
    respond(['success'=>false,'message'=>'Invalid status.']);

// Leaders can only verify their own group's reports
if ($auth['role']==='leader') {
    $check = db()->prepare('SELECT group_name FROM reports WHERE id=?');
    $check->execute([$id]);
    $row = $check->fetch();
    if (!$row || $row['group_name'] !== $auth['group'])
        respond(['success'=>false,'message'=>'Report not in your group.']);
}

$upd = db()->prepare(
    'UPDATE reports SET status=?, leader_verified_by=?, leader_verified_at=NOW() WHERE id=?'
);
$upd->execute([$status, $auth['handle'], $id]);
respond(['success'=>true]);
