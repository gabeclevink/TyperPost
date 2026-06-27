<?php

header("Content-Type: application/json");
require_once "../init.php";

$token = trim($_POST['token'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if ($token == "") {
    die(json_encode([
        "success" => false,
        "error" => "missing_token"
    ]));
}

$bio = substr($bio, 0, 160);

$stmt = $db->prepare("
SELECT users.*
FROM sessions
INNER JOIN users ON users.id = sessions.user_id
WHERE sessions.token = ?
AND sessions.expires > ?
LIMIT 1
");

$stmt->execute([
    $token,
    time()
]);

$user = $stmt->fetch();

if (!$user) {
    die(json_encode([
        "success" => false,
        "error" => "invalid_token"
    ]));
}

$stmt = $db->prepare("
UPDATE users
SET bio = ?
WHERE id = ?
");

$stmt->execute([
    $bio,
    $user['id']
]);

echo json_encode([
    "success" => true,
    "bio" => $bio
], JSON_PRETTY_PRINT);
