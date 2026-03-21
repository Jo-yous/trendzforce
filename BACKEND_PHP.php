<?php
/*
╔══════════════════════════════════════════════════════════════════════════════╗
║            TRENDSFORCE — COMPLETE PHP BACKEND                              ║
║  Drop all files in an /api/ folder on your PHP server (PHP 7.4+, MySQL)    ║
╚══════════════════════════════════════════════════════════════════════════════╝

FILE LIST:
  api/config.php          ← Database config + helpers  (edit DB credentials here)
  api/login.php           ← POST  Sign in
  api/logout.php          ← POST  Sign out
  api/register.php        ← POST  Create member account
  api/submit_report.php   ← POST  Submit daily report
  api/get_reports.php     ← GET   Fetch reports (role-scoped)
  api/verify.php          ← POST  Leader verify a report
  api/admin_verify.php    ← POST  Admin approve/reject a report
  api/admin_reports.php   ← GET   All reports (admin only)
  api/members.php         ← GET   Member list (admin/leader)
  api/stats.php           ← GET   Public stats for login page

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DATABASE SETUP — run this SQL once in your MySQL/MariaDB console:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CREATE DATABASE IF NOT EXISTS trendsforce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trendsforce;

CREATE TABLE IF NOT EXISTS members (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(255)  NOT NULL,
  main_handle     VARCHAR(100)  NOT NULL UNIQUE,
  password_hash   VARCHAR(255)  NOT NULL,
  group_name      VARCHAR(100)  DEFAULT NULL,
  role            ENUM('member','leader','admin') NOT NULL DEFAULT 'member',
  other_handles   JSON          DEFAULT NULL,
  hashtags        JSON          DEFAULT NULL,
  platforms       JSON          DEFAULT NULL,
  created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reports (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  member_id           INT           NOT NULL,
  member_handle       VARCHAR(100)  NOT NULL,
  member_name         VARCHAR(255)  DEFAULT NULL,
  group_name          VARCHAR(100)  DEFAULT NULL,
  report_date         DATE          NOT NULL,
  submitted_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  status              ENUM('pending','leader_verified','verified','rejected') NOT NULL DEFAULT 'pending',

  -- KingsChat
  kc_accounts         TINYINT       DEFAULT 0,
  kc_posts            INT           DEFAULT 0,
  kc_likes            INT           DEFAULT 0,
  kc_shares           INT           DEFAULT 0,
  kc_comments         INT           DEFAULT 0,
  kc_views            INT           DEFAULT 0,
  kc_people_engaged   INT           DEFAULT 0,
  kc_hashtags         JSON          DEFAULT NULL,
  kc_engaged_out      JSON          DEFAULT NULL,
  kc_engaged_in       JSON          DEFAULT NULL,

  -- External platforms
  ext_whatsapp        INT           DEFAULT 0,
  ext_telegram        INT           DEFAULT 0,
  ext_twitter         INT           DEFAULT 0,
  ext_instagram       INT           DEFAULT 0,
  ext_facebook        INT           DEFAULT 0,
  ext_tiktok          INT           DEFAULT 0,
  ext_channels        JSON          DEFAULT NULL,

  notes               TEXT          DEFAULT NULL,

  -- Verification trail
  leader_verified_by  VARCHAR(100)  DEFAULT NULL,
  leader_verified_at  TIMESTAMP     NULL DEFAULT NULL,
  admin_verified_by   VARCHAR(100)  DEFAULT NULL,
  admin_verified_at   TIMESTAMP     NULL DEFAULT NULL,

  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  UNIQUE KEY uq_member_date (member_id, report_date)
);

-- Default admin account (password: admin123 — change immediately!)
INSERT IGNORE INTO members (full_name, main_handle, password_hash, role)
VALUES ('Administrator', '@admin', '$2y$12$placeholder_change_me', 'admin');

-- Update admin password (run this separately, replacing 'your_secure_password'):
-- UPDATE members SET password_hash = '$2y$12$' WHERE main_handle = '@admin';
-- Use: php -r "echo password_hash('your_secure_password', PASSWORD_BCRYPT);"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
IN THE HTML FILES — search for "PHP BACKEND" comments and uncomment those
blocks, then delete the demo/localStorage code below them.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
*/


// ══════════════════════════════════════════════════════════════════════════════
// api/config.php
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// ── Edit these values ──────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'trendsforce');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_PORT', 3306);

// ── Helpers ────────────────────────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function require_auth(string ...$roles): array {
    session_start();
    if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Not authenticated'], 401);
    if ($roles && !in_array($_SESSION['user_role'], $roles))
        respond(['success'=>false,'message'=>'Insufficient permissions'], 403);
    return ['id'=>$_SESSION['user_id'],'role'=>$_SESSION['user_role'],'handle'=>$_SESSION['user_handle'],'group'=>$_SESSION['user_group']];
}

function decode_report(array $row): array {
    foreach (['kc_hashtags','kc_engaged_out','kc_engaged_in','ext_channels'] as $f)
        $row[$f] = json_decode($row[$f] ?? '[]', true);
    return $row;
}


// ══════════════════════════════════════════════════════════════════════════════
// api/login.php
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

$b = body();
$handle   = trim(strtolower($b['handle'] ?? ''));
$password = $b['password'] ?? '';
$role     = $b['role'] ?? 'member';

if (!$handle || !$password) respond(['success'=>false,'message'=>'Handle and password are required.']);

if (!str_starts_with($handle, '@')) $handle = '@'.$handle;

$stmt = db()->prepare('SELECT * FROM members WHERE LOWER(main_handle)=? LIMIT 1');
$stmt->execute([$handle]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash']))
    respond(['success'=>false,'message'=>'Invalid handle or password.']);

if ($user['role'] !== $role)
    respond(['success'=>false,'message'=>'Wrong role selected for this account.']);

session_start();
session_regenerate_id(true);
$_SESSION['user_id']     = $user['id'];
$_SESSION['user_role']   = $user['role'];
$_SESSION['user_handle'] = $user['main_handle'];
$_SESSION['user_group']  = $user['group_name'];

respond(['success'=>true,'user'=>[
    'id'           => $user['id'],
    'name'         => $user['full_name'],
    'handle'       => $user['main_handle'],
    'group'        => $user['group_name'],
    'role'         => $user['role'],
    'otherHandles' => json_decode($user['other_handles']??'[]',true),
    'hashtags'     => json_decode($user['hashtags']??'[]',true),
    'platforms'    => json_decode($user['platforms']??'{}',true),
]]);


// ══════════════════════════════════════════════════════════════════════════════
// api/logout.php
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';
session_start();
session_destroy();
respond(['success'=>true]);


// ══════════════════════════════════════════════════════════════════════════════
// api/register.php
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

$b          = body();
$name       = trim($b['name'] ?? '');
$handle     = trim(strtolower($b['mainHandle'] ?? ''));
$password   = $b['password'] ?? '';
$group      = trim($b['group'] ?? '');
$others     = json_encode($b['otherHandles'] ?? []);
$hashtags   = json_encode($b['hashtags'] ?? []);
$platforms  = json_encode($b['platforms'] ?? []);

if (!$name)     respond(['success'=>false,'message'=>'Full name is required.']);
if (!$handle)   respond(['success'=>false,'message'=>'KingsChat handle is required.']);
if (!$password) respond(['success'=>false,'message'=>'Password is required.']);
if (!$group)    respond(['success'=>false,'message'=>'Please select your group.']);
if (strlen($password) < 6) respond(['success'=>false,'message'=>'Password must be at least 6 characters.']);

if (!str_starts_with($handle,'@')) $handle='@'.$handle;

// Check duplicate
$chk = db()->prepare('SELECT id FROM members WHERE LOWER(main_handle)=?');
$chk->execute([$handle]);
if ($chk->fetch()) respond(['success'=>false,'message'=>'This KingsChat handle is already registered.']);

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
$ins  = db()->prepare(
    'INSERT INTO members (full_name,main_handle,password_hash,group_name,role,other_handles,hashtags,platforms)
     VALUES (?,?,?,?,?,?,?,?)'
);
$ins->execute([$name,$handle,$hash,$group,'member',$others,$hashtags,$platforms]);

respond(['success'=>true,'message'=>'Account created. You can now sign in.']);


// ══════════════════════════════════════════════════════════════════════════════
// api/submit_report.php
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

$auth = require_auth('member');
$b    = body();
$date = $b['date'] ?? date('Y-m-d');
$kc   = $b['kingsChat'] ?? [];
$ext  = $b['external'] ?? [];

// Get member info
$stmt = db()->prepare('SELECT * FROM members WHERE id=?');
$stmt->execute([$auth['id']]);
$member = $stmt->fetch();

try {
    // Check if report exists
    $chk = db()->prepare('SELECT id, status FROM reports WHERE member_id=? AND report_date=?');
    $chk->execute([$member['id'], $date]);
    $existing = $chk->fetch();

    if ($existing) {
        if ($existing['status'] !== 'pending') {
            respond(['success'=>false,'message'=>'Report already verified. Cannot update.']);
        }
        $upd = db()->prepare(
           'UPDATE reports SET
            kc_accounts=?, kc_posts=?, kc_likes=?, kc_shares=?, kc_comments=?, kc_views=?, kc_people_engaged=?,
            kc_hashtags=?, kc_engaged_out=?, kc_engaged_in=?,
            ext_whatsapp=?, ext_telegram=?, ext_twitter=?, ext_instagram=?, ext_facebook=?, ext_tiktok=?, ext_channels=?,
            notes=?
            WHERE id=?'
        );
        $upd->execute([
            intval($kc['accounts']??0), intval($kc['posts']??0), intval($kc['likes']??0),
            intval($kc['shares']??0), intval($kc['comments']??0), intval($kc['views']??0),
            intval($kc['peopleEngaged']??0),
            json_encode($kc['hashtags']??[]), json_encode($kc['engagedOut']??[]), json_encode($kc['engagedIn']??[]),
            intval($ext['whatsapp']??0), intval($ext['telegram']??0), intval($ext['twitter']??0),
            intval($ext['instagram']??0), intval($ext['facebook']??0), intval($ext['tiktok']??0),
            json_encode($ext['channels']??[]),
            $b['notes'] ?? '',
            $existing['id']
        ]);
        respond(['success'=>true,'id'=>$existing['id'], 'message'=>'Report updated successfully.']);
    } else {
        $ins = db()->prepare(
           'INSERT INTO reports
            (member_id,member_handle,member_name,group_name,report_date,
             kc_accounts,kc_posts,kc_likes,kc_shares,kc_comments,kc_views,kc_people_engaged,
             kc_hashtags,kc_engaged_out,kc_engaged_in,
             ext_whatsapp,ext_telegram,ext_twitter,ext_instagram,ext_facebook,ext_tiktok,ext_channels,
             notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $ins->execute([
            $member['id'], $member['main_handle'], $member['full_name'], $member['group_name'], $date,
            intval($kc['accounts']??0), intval($kc['posts']??0), intval($kc['likes']??0),
            intval($kc['shares']??0), intval($kc['comments']??0), intval($kc['views']??0),
            intval($kc['peopleEngaged']??0),
            json_encode($kc['hashtags']??[]), json_encode($kc['engagedOut']??[]), json_encode($kc['engagedIn']??[]),
            intval($ext['whatsapp']??0), intval($ext['telegram']??0), intval($ext['twitter']??0),
            intval($ext['instagram']??0), intval($ext['facebook']??0), intval($ext['tiktok']??0),
            json_encode($ext['channels']??[]),
            $b['notes'] ?? ''
        ]);
        respond(['success'=>true,'id'=>db()->lastInsertId()]);
    }
} catch (PDOException $e) {
    respond(['success'=>false,'message'=>'Database error. Please try again.'], 500);
}


// ══════════════════════════════════════════════════════════════════════════════
// api/get_reports.php  (member sees own; leader sees their group; admin sees all)
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

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


// ══════════════════════════════════════════════════════════════════════════════
// api/verify.php  (group leader marks as leader_verified)
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

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


// ══════════════════════════════════════════════════════════════════════════════
// api/admin_verify.php  (admin final approve/reject)
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

$auth   = require_auth('admin');
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


// ══════════════════════════════════════════════════════════════════════════════
// api/admin_reports.php  (admin: all reports with optional filters)
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

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


// ══════════════════════════════════════════════════════════════════════════════
// api/members.php
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

$auth = require_auth('leader','admin');

if ($auth['role']==='leader') {
    $stmt = db()->prepare('SELECT id,full_name,main_handle,group_name,other_handles,hashtags,platforms,created_at FROM members WHERE group_name=? AND role="member" ORDER BY full_name');
    $stmt->execute([$auth['group']]);
} else {
    $stmt = db()->prepare('SELECT id,full_name,main_handle,group_name,role,other_handles,hashtags,platforms,created_at FROM members ORDER BY group_name,full_name');
    $stmt->execute();
}

$members = array_map(function($m){
    foreach(['other_handles','hashtags','platforms'] as $f) $m[$f]=json_decode($m[$f]??'[]',true);
    return $m;
}, $stmt->fetchAll());

respond(['success'=>true,'members'=>$members]);


// ══════════════════════════════════════════════════════════════════════════════
// api/stats.php  (public – login page counters)
// ══════════════════════════════════════════════════════════════════════════════
// <?php
// require 'config.php';

$members = db()->query('SELECT COUNT(*) FROM members WHERE role="member"')->fetchColumn();
$today   = date('Y-m-d');
$todayR  = db()->prepare('SELECT COUNT(*) FROM reports WHERE report_date=?');
$todayR->execute([$today]);
$verified = db()->prepare('SELECT COUNT(*) FROM reports WHERE status="verified" AND report_date=?');
$verified->execute([$today]);

respond(['members'=>(int)$members,'todayReports'=>(int)$todayR->fetchColumn(),'todayVerified'=>(int)$verified->fetchColumn()]);
