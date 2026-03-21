<?php
require 'config.php';

$auth = require_auth();

if ($auth['role'] === 'member') {
    $stmt = db()->prepare('SELECT * FROM reports WHERE member_id=? ORDER BY report_date DESC');
    $stmt->execute([$auth['id']]);
} elseif ($auth['role'] === 'leader') {
    $grp  = $_GET['group'] ?? $auth['group'];
    $stmt = db()->prepare('SELECT * FROM reports WHERE group_name=? ORDER BY report_date DESC, submitted_at DESC');
    $stmt->execute([$grp]);
} else {
    $stmt = db()->prepare('SELECT * FROM reports ORDER BY report_date DESC, submitted_at DESC');
    $stmt->execute();
}

$reports = array_map('decode_report', $stmt->fetchAll());
respond(['success'=>true,'reports'=>$reports]);
