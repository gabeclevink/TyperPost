// Register a Account! 
// By Gabe Clevin K
<?php

header("Content-Type: application/json");

require_once "init.php";

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($username == "" || $email == "" || $password == "")
{
    die(json_encode([
        "success"=>false,
        "error"=>"missing_fields"
    ]));
}

if(strlen($username) < 3)
{
    die(json_encode([
        "success"=>false,
        "error"=>"username_too_short"
    ]));
}

if(strlen($password) < 6)
{
    die(json_encode([
        "success"=>false,
        "error"=>"password_too_short"
    ]));
}

$stmt = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
$stmt->execute([$username,$email]);

if($stmt->fetch())
{
    die(json_encode([
        "success"=>false,
        "error"=>"account_exists"
    ]));
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("
INSERT INTO users
(username,email,password,display_name,created_at)
VALUES(?,?,?,?,?)
");

$stmt->execute([
    $username,
    $email,
    $hash,
    $username,
    time()
]);

$userID = $db->lastInsertId();

$token = bin2hex(random_bytes(32));

$stmt = $db->prepare("
INSERT INTO sessions
(token,user_id,expires,created_at)
VALUES(?,?,?,?)
");

$stmt->execute([
    $token,
    $userID,
    time()+60*60*24*365,
    time()
]);

echo json_encode([
    "success"=>true,
    "user_id"=>$userID,
    "token"=>$token
]);
