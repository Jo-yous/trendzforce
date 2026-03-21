<?php
require 'config.php';

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
