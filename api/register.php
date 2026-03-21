<?php
require 'config.php';

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
