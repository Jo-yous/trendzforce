<?php
require 'config.php';

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
