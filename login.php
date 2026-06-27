// Login 
// By Gabe Clevin K
<?php

header("Content-Type: application/json");

require_once "init.php";

$user = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if($user=="" || $password=="")
{
    die(json_encode([
        "success"=>false
    ]));
}

$stmt = $db->prepare("
SELECT *
FROM users
WHERE username=?
OR email=?
LIMIT 1
");

$stmt->execute([
    $user,
    $user
]);

$account = $stmt->fetch();

if(!$account)
{
    die(json_encode([
        "success"=>false,
        "error"=>"invalid_login"
    ]));
}

if(!password_verify($password,$account['password']))
{
    die(json_encode([
        "success"=>false,
        "error"=>"invalid_login"
    ]));
}

$token = bin2hex(random_bytes(32));

$db->prepare("
DELETE FROM sessions
WHERE user_id=?
")->execute([
    $account['id']
]);

$db->prepare("
INSERT INTO sessions
(token,user_id,expires,created_at)
VALUES(?,?,?,?)
")->execute([
    $token,
    $account['id'],
    time()+60*60*24*365,
    time()
]);

echo json_encode([
    "success"=>true,
    "token"=>$token,
    "user"=>[
        "id"=>$account['id'],
        "username"=>$account['username'],
        "display_name"=>$account['display_name'],
        "avatar"=>$account['avatar'],
        "verified"=>$account['verified']
    ]
]);
