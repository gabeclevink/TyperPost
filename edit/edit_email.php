// Editing Used Email to Create your Account on the App!
// by Gabe Clevin K
<?php

header("Content-Type: application/json");
require_once "../init.php";

$token = trim($_POST['token'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($token == "" || $email == "") {
    die(json_encode([
        "success" => false,
        "error" => "missing_fields"
    ]));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode([
        "success" => false,
        "error" => "invalid_email"
    ]));
}

$stmt = $db->prepare("
SELECT users.*
FROM sessions
INNER JOIN users ON users.id=sessions.user_id
WHERE sessions.token=?
AND sessions.expires>?
LIMIT 1
");

$stmt->execute([$token,time()]);
$user = $stmt->fetch();

if(!$user){
    die(json_encode([
        "success"=>false,
        "error"=>"invalid_token"
    ]));
}

$stmt = $db->prepare("
SELECT id
FROM users
WHERE email=?
AND id<>?
");

$stmt->execute([
    $email,
    $user['id']
]);

if($stmt->fetch()){
    die(json_encode([
        "success"=>false,
        "error"=>"email_exists"
    ]));
}

$stmt = $db->prepare("
UPDATE users
SET email=?
WHERE id=?
");

$stmt->execute([
    $email,
    $user['id']
]);

echo json_encode([
    "success"=>true,
    "email"=>$email
],JSON_PRETTY_PRINT);
