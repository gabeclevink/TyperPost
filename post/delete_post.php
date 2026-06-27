<?php

header("Content-Type: application/json");

require_once "init.php";

$token = trim($_POST['token'] ?? '');
$postID = intval($_POST['post_id'] ?? 0);

if ($token == "" || $postID == 0) {

    die(json_encode([
        "success"=>false,
        "error"=>"missing_fields"
    ]));

}

$stmt=$db->prepare("
SELECT users.id
FROM sessions
INNER JOIN users
ON users.id=sessions.user_id
WHERE sessions.token=?
AND sessions.expires>?
LIMIT 1
");

$stmt->execute([
    $token,
    time()
]);

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
LIMIT 1
");

$stmt->execute([
    $postID
]);

$post=$stmt->fetch();

if(!$post){

    die(json_encode([
        "success"=>false,
        "error"=>"post_not_found"
    ]));

}

if($post["user_id"]!=$user["id"]){

    die(json_encode([
        "success"=>false,
        "error"=>"permission_denied"
    ]));

}

if($post["image"]!="" && file_exists($post["image"]))
    @unlink($post["image"]);

$db->prepare("
DELETE FROM posts
WHERE id=?
")->execute([
    $postID
]);

$db->prepare("
UPDATE users
SET posts=CASE
WHEN posts>0 THEN posts-1
ELSE 0
END
WHERE id=?
")->execute([
    $user["id"]
]);

echo json_encode([
    "success"=>true
],JSON_PRETTY_PRINT);
