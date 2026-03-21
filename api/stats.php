<?php
require 'config.php';

$members = db()->query('SELECT COUNT(*) FROM members WHERE role="member"')->fetchColumn();
$today   = date('Y-m-d');
$todayR  = db()->prepare('SELECT COUNT(*) FROM reports WHERE report_date=?');
$todayR->execute([$today]);
$verified = db()->prepare('SELECT COUNT(*) FROM reports WHERE status="verified" AND report_date=?');
$verified->execute([$today]);

respond(['members'=>(int)$members,'todayReports'=>(int)$todayR->fetchColumn(),'todayVerified'=>(int)$verified->fetchColumn()]);
