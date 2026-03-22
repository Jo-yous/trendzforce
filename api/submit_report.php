<?php
require 'config.php';

$auth = require_auth('member', 'leader');
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
