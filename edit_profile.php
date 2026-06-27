// Editing Users Profiles!
// by Gabe Clevin K
<?php

header("Content-Type: application/json");

require_once "init.php";

$token = trim($_POST['token'] ?? '');

$display_name = trim($_POST['display_name'] ?? '');
$bio          = trim($_POST['bio'] ?? '');
$website      = trim($_POST['website'] ?? '');
$location     = trim($_POST['location'] ?? '');

if ($token == "")
{
    die(json_encode([
        "success" => false,
        "error" => "missing_token"
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

if (!$user)
{
    die(json_encode([
        "success" => false,
        "error" => "invalid_token"
    ]));
}


$display_name = substr($display_name, 0, 50);
$bio          = substr($bio, 0, 160);
$website      = substr($website, 0, 255);
$location     = substr($location, 0, 100);

$stmt = $db->prepare("
UPDATE users
SET
    display_name = ?,
    bio = ?,
    website = ?,
    location = ?
WHERE id = ?
");

$stmt->execute([
    $display_name,
    $bio,
    $website,
    $location,
    $user['id']
]);

$stmt = $db->prepare("
SELECT *
FROM users
WHERE id = ?
LIMIT 1
");

$stmt->execute([
    $user['id']
]);

$user = $stmt->fetch();

echo json_encode([
    "success" => true,

    "user" => [

        "id" => (int)$user["id"],

        "username" => $user["username"],

        "display_name" => $user["display_name"],

        "bio" => $user["bio"],

        "avatar" => $user["avatar"],

        "banner" => $user["banner"],

        "website" => $user["website"],

        "location" => $user["location"],

        "verified" => (bool)$user["verified"]

    ]

], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
