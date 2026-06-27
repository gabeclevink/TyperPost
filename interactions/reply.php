<?php

header("Content-Type: application/json");

require_once "../init.php";
require_once "../notify.php";

$token   = trim($_POST["token"] ?? "");
$postID  = intval($_POST["post_id"] ?? 0);
$text    = trim($_POST["text"] ?? "");

if($token=="" || $postID==0 || $text==""){
    die(json_encode([
        "success"=>false,
        "error"=>"missing_fields"
    ]));
}

$stmt=$db->prepare("
SELECT users.*
FROM sessions
INNER JOIN users
ON users.id=sessions.user_id
WHERE token=?
AND expires>?
LIMIT 1
");

$stmt->execute([$token,time()]);
$user=$stmt->fetch();

if(!$user){
    die(json_encode([
        "success"=>false,
        "error"=>"invalid_token"
    ]));
}

$stmt=$db->prepare("
SELECT *
FROM posts
WHERE id=?
");

$stmt->execute([$postID]);

$post=$stmt->fetch();

if(!$post){
    die(json_encode([
        "success"=>false,
        "error"=>"post_not_found"
    ]));
}

$db->prepare("
INSERT INTO posts
(
user_id,
text,
reply_to,
created_at
)
VALUES(?,?,?,?)
")->execute([
    $user["id"],
    $text,
    $postID,
    time()
]);

$replyID=$db->lastInsertId();

$db->prepare("
UPDATE posts
SET replies=replies+1
WHERE id=?
")->execute([$postID]);

$db->prepare("
UPDATE users
SET posts=posts+1
WHERE id=?
")->execute([$user["id"]]);

createNotification(
    $db,
    $post["user_id"],
    $user["id"],
    "reply",
    $replyID
);

echo json_encode([
    "success"=>true,
    "reply_id"=>$replyID
],JSON_PRETTY_PRINT);
