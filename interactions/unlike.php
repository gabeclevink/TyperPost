<?php

header("Content-Type: application/json");

require_once "../init.php";

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

$db->prepare("
DELETE FROM likes
WHERE user_id=?
AND post_id=?
")->execute([
    $user["id"],
    $postID
]);

$db->prepare("
UPDATE posts
SET likes =
CASE
WHEN likes>0 THEN likes-1
ELSE 0
END
WHERE id=?
")->execute([
    $postID
]);

$count = $db->query("
SELECT likes
FROM posts
WHERE id=".$postID
)->fetchColumn();

echo json_encode([
    "success"=>true,
    "likes"=>(int)$count
],JSON_PRETTY_PRINT);
