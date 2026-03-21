<?php
require 'config.php';

require_auth('admin');
$where = '1=1'; $params = [];

if (!empty($_GET['group']))  { $where.=' AND group_name=?'; $params[]=$_GET['group']; }
if (!empty($_GET['status'])) { $where.=' AND status=?';     $params[]=$_GET['status']; }
if (!empty($_GET['date']))   { $where.=' AND report_date=?'; $params[]=$_GET['date']; }
if (!empty($_GET['search'])) {
    $q='%'.trim($_GET['search']).'%';
    $where.=' AND (member_name LIKE ? OR member_handle LIKE ?)';
    $params[]=$q; $params[]=$q;
}

$limit  = min(intval($_GET['limit']??500),1000);
$offset = intval($_GET['offset']??0);

$stmt = db()->prepare("SELECT * FROM reports WHERE $where ORDER BY report_date DESC, submitted_at DESC LIMIT ? OFFSET ?");
$stmt->execute([...$params,$limit,$offset]);
$reports = array_map('decode_report', $stmt->fetchAll());

$cnt = db()->prepare("SELECT COUNT(*) FROM reports WHERE $where");
$cnt->execute($params);

respond(['success'=>true,'reports'=>$reports,'total'=>(int)$cnt->fetchColumn()]);
