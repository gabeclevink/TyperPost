<?php

header("Content-Type: application/json");

require_once "../init.php";
require_once "../notify.php";

$token = trim($_POST["token"] ?? "");
$postID = intval($_POST["post_id"] ?? 0);

$stmt = $db->prepare("
SELECT users.id
FROM sessions
INNER JOIN users
ON users.id=sessions.user_id
WHERE token=?
AND expires>?
LIMIT 1
");

$stmt->execute([
    $token,
    time()
]);

$user = $stmt->fetch();

if (!$user) {
    die(json_encode([
        "success"=>false
    ]));
}

$stmt = $db->prepare("
SELECT *
FROM posts
WHERE id=?
LIMIT 1
");

$stmt->execute([
    $postID
]);

$post = $stmt->fetch();

if (!$post) {
    die(json_encode([
        "success"=>false,
        "error"=>"post_not_found"
    ]));
}

$stmt = $db->prepare("
SELECT 1
FROM reposts
WHERE user_id=?
AND post_id=?
");

$stmt->execute([
    $user["id"],
    $postID
]);

if ($stmt->fetch()) {
    die(json_encode([
        "success"=>false,
        "error"=>"already_reposted"
    ]));
}

$db->prepare("
INSERT INTO reposts(user_id,post_id)
VALUES(?,?)
")->execute([
    $user["id"],
    $postID
]);

$db->prepare("
UPDATE posts
SET reposts=reposts+1
WHERE id=?
")->execute([
    $postID
]);

$db->prepare("
INSERT INTO posts
(
user_id,
text,
image,
repost_of,
created_at
)
VALUES(?,?,?,?,?)
")->execute([
    $user["id"],
    "",
    "",
    $postID,
    time()
]);

createNotification(
    $db,
    $post["user_id"],
    $user["id"],
    "repost",
    $postID
);

$count = $db->query("
SELECT reposts
FROM posts
WHERE id=".$postID
)->fetchColumn();

echo json_encode([
    "success"=>true,
    "reposts"=>(int)$count
],JSON_PRETTY_PRINT);
