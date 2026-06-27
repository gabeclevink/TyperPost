<?php

header("Content-Type: application/json");

require_once "init.php";

$token=$_POST['token'] ?? '';

$stmt=$db->prepare("
SELECT user_id
FROM sessions
WHERE token=?
AND expires>?
");

$stmt->execute([
$token,
time()
]);

$user=$stmt->fetch();

if(!$user)
die(json_encode([
"success"=>false
]));

$db->prepare("
UPDATE notifications
SET is_read=1
WHERE user_id=?
")->execute([
$user['user_id']
]);

echo json_encode([
"success"=>true
]);
