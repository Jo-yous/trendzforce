<?php
require 'config.php';

$auth   = require_auth('admin');
if ($auth['adminLevel'] >= 3) respond(['success'=>false, 'message'=>'Restricted. Your admin tier cannot approve reports.']);
$b      = body();
$id     = intval($b['id'] ?? 0);
$status = $b['status'] ?? 'verified';

if (!in_array($status,['verified','rejected','leader_verified']))
    respond(['success'=>false,'message'=>'Invalid status.']);

$upd = db()->prepare(
    'UPDATE reports SET status=?, admin_verified_by=?, admin_verified_at=NOW() WHERE id=?'
);
$upd->execute([$status, $auth['handle'], $id]);
respond(['success'=>true]);
