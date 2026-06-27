// i'm tired of putting comments so no more comments on the php files aaaaaaa
// by Gabe Clevin K
<?php

header("Content-Type: application/json");
require_once "../init.php";

$token = trim($_POST['token'] ?? '');

$oldPassword = $_POST['old_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if($token=="" || $oldPassword=="" || $newPassword==""){
    die(json_encode([
        "success"=>false,
        "error"=>"missing_fields"
    ]));
}

if(strlen($newPassword) < 6){
    die(json_encode([
        "success"=>false,
        "error"=>"password_too_short"
    ]));
}

$stmt=$db->prepare("
SELECT users.*
FROM sessions
INNER JOIN users ON users.id=sessions.user_id
WHERE sessions.token=?
AND sessions.expires>?
LIMIT 1
");

$stmt->execute([
    $token,
    time()
]);

$user=$stmt->fetch();

if(!$user){
    die(json_encode([
        "success"=>false,
        "error"=>"invalid_token"
    ]));
}

if(!password_verify($oldPassword,$user['password'])){
    die(json_encode([
        "success"=>false,
        "error"=>"wrong_password"
    ]));
}

$newHash=password_hash($newPassword,PASSWORD_DEFAULT);

$stmt=$db->prepare("
UPDATE users
SET password=?
WHERE id=?
");

$stmt->execute([
    $newHash,
    $user['id']
]);

echo json_encode([
    "success"=>true
],JSON_PRETTY_PRINT);
