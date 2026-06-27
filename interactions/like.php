<?php

header("Content-Type: application/json");

require_once "init.php";
require_once "notify.php";

$token = trim($_POST["token"] ?? "");
$postID = intval($_POST["post_id"] ?? 0);

if ($token == "" || $postID == 0) {
    die(json_encode([
        "success" => false,
        "error" => "missing_fields"
    ]));
}

$stmt = $db->prepare("
SELECT users.id
FROM sessions
INNER JOIN users
ON users.id = sessions.user_id
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
        "success"=>false,
        "error"=>"invalid_token"
    ]));
}

$stmt = $db->prepare("
SELECT *
FROM posts
WHERE id = ?
LIMIT 1
");

$stmt->execute([$postID]);

$post = $stmt->fetch();

if (!$post) {
    die(json_encode([
        "success"=>false,
        "error"=>"post_not_found"
    ]));
}

$stmt = $db->prepare("
SELECT 1
FROM likes
WHERE user_id = ?
AND post_id = ?
");

$stmt->execute([
    $user["id"],
    $postID
]);

if ($stmt->fetch()) {
    die(json_encode([
        "success"=>false,
        "error"=>"already_liked"
    ]));
}

$db->prepare("
INSERT INTO likes(user_id,post_id)
VALUES(?,?)
")->execute([
    $user["id"],
    $postID
]);

$db->prepare("
UPDATE posts
SET likes = likes + 1
WHERE id = ?
")->execute([
    $postID
]);

createNotification(
    $db,
    $post["user_id"],
    $user["id"],
    "like",
    $postID
);

$count = $db->query("
SELECT likes
FROM posts
WHERE id=".$postID
)->fetchColumn();

echo json_encode([
    "success"=>true,
    "likes"=>(int)$count
],JSON_PRETTY_PRINT);
