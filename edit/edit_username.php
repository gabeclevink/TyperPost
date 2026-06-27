<?php

header("Content-Type: application/json");
require_once "../init.php";

$token = trim($_POST['token'] ?? '');
$username = trim($_POST['username'] ?? '');

if ($token == "" || $username == "") {
    die(json_encode([
        "success" => false,
        "error" => "missing_fields"
    ]));
}

$username = strtolower($username);

if (!preg_match('/^[a-z0-9_]{3,20}$/', $username)) {
    die(json_encode([
        "success" => false,
        "error" => "invalid_username"
    ]));
}

$reserved = [
    "admin",
    "root",
    "support",
    "api",
    "login",
    "register",
    "edit",
    "settings",
    "user",
    "users",
    "profile",
    "timeline"
];

if (in_array($username, $reserved)) {
    die(json_encode([
        "success" => false,
        "error" => "reserved_username"
    ]));
}

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
SELECT id
FROM users
WHERE username = ?
AND id <> ?
");

$stmt->execute([
    $username,
    $user['id']
]);

if ($stmt->fetch()) {
    die(json_encode([
        "success" => false,
        "error" => "username_taken"
    ]));
}

$stmt = $db->prepare("
UPDATE users
SET username = ?
WHERE id = ?
");

$stmt->execute([
    $username,
    $user['id']
]);

echo json_encode([
    "success" => true,
    "username" => $username
], JSON_PRETTY_PRINT);
