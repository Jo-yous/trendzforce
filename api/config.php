<?php
// ── Edit these values ──────────────────────────────────────────────────────
define('DB_HOST', 'mysql-trendzforce.alwaysdata.net');
define('DB_NAME', 'trendzforce_main');
define('DB_USER', 'trendzforce');
define('DB_PASS', 'hd!7Xuex!8UeDMH');
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
    return ['id'=>$_SESSION['user_id'],'role'=>$_SESSION['user_role'],'handle'=>$_SESSION['user_handle'],'group'=>$_SESSION['user_group'],'adminLevel'=>$_SESSION['admin_level']??0];
}

function decode_report(array $row): array {
    return [
        'id'               => $row['id'],
        'memberId'         => $row['member_id'],
        'memberName'       => $row['member_name'],
        'memberHandle'     => $row['member_handle'],
        'group'            => $row['group_name'],
        'date'             => $row['report_date'],
        'submittedAt'      => $row['submitted_at'],
        'status'           => $row['status'],
        'notes'            => $row['notes'],
        'kingsChat' => [
            'accounts'      => (int)($row['kc_accounts'] ?? 0),
            'posts'         => (int)($row['kc_posts'] ?? 0),
            'likes'         => (int)($row['kc_likes'] ?? 0),
            'shares'        => (int)($row['kc_shares'] ?? 0),
            'comments'      => (int)($row['kc_comments'] ?? 0),
            'views'         => (int)($row['kc_views'] ?? 0),
            'peopleEngaged' => (int)($row['kc_people_engaged'] ?? 0),
            'hashtags'      => json_decode($row['kc_hashtags'] ?? '[]', true),
            'engagedOut'    => json_decode($row['kc_engaged_out'] ?? '[]', true),
            'engagedIn'     => json_decode($row['kc_engaged_in'] ?? '[]', true),
        ],
        'external' => [
            'whatsapp'   => (int)($row['ext_whatsapp'] ?? 0),
            'telegram'   => (int)($row['ext_telegram'] ?? 0),
            'twitter'    => (int)($row['ext_twitter'] ?? 0),
            'instagram'  => (int)($row['ext_instagram'] ?? 0),
            'facebook'   => (int)($row['ext_facebook'] ?? 0),
            'tiktok'     => (int)($row['ext_tiktok'] ?? 0),
            'channels'   => json_decode($row['ext_channels'] ?? '[]', true),
        ],
        'leaderVerifiedBy' => $row['leader_verified_by'] ?? null,
        'leaderVerifiedAt' => $row['leader_verified_at'] ?? null,
        'adminVerifiedBy'  => $row['admin_verified_by'] ?? null,
        'adminVerifiedAt'  => $row['admin_verified_at'] ?? null,
    ];
}
