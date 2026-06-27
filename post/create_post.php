<?php

header("Content-Type: application/json");

require_once "../init.php";
require_once "../notify.php";

$token = trim($_POST['token'] ?? '');
$text = trim($_POST['text'] ?? '');
$replyTo = intval($_POST['reply_to'] ?? 0);
$repostOf = intval($_POST['repost_of'] ?? 0);

if ($token == "") {
    die(json_encode([
        "success" => false,
        "error" => "missing_token"
    ]));
}

$stmt = $db->prepare("
SELECT users.*
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

$image = "";

if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {

    if ($_FILES["image"]["size"] <= (8 * 1024 * 1024)) {

        $mime = mime_content_type($_FILES["image"]["tmp_name"]);

        $types = [
            "image/jpeg"=>"jpg",
            "image/png"=>"png",
            "image/gif"=>"gif",
            "image/webp"=>"webp"
        ];

        if (isset($types[$mime])) {

            $ext = $types[$mime];

            if (!is_dir("uploads/media"))
                mkdir("uploads/media",0777,true);

            $filename = uniqid().".".$ext;

            move_uploaded_file(
                $_FILES["image"]["tmp_name"],
                "uploads/media/".$filename
            );

            $image = "uploads/media/".$filename;
        }
    }
}

if ($text == "" && $image == "" && $repostOf == 0) {
    die(json_encode([
        "success"=>false,
        "error"=>"empty_post"
    ]));
}

$stmt = $db->prepare("
INSERT INTO posts
(
user_id,
text,
image,
reply_to,
repost_of,
created_at
)
VALUES(?,?,?,?,?,?)
");

$stmt->execute([
    $user["id"],
    $text,
    $image,
    $replyTo ?: NULL,
    $repostOf ?: NULL,
    time()
]);

$postID = $db->lastInsertId();

$db->prepare("
UPDATE users
SET posts=posts+1
WHERE id=?
")->execute([
    $user["id"]
]);

if ($replyTo > 0) {

    $db->prepare("
    UPDATE posts
    SET replies=replies+1
    WHERE id=?
    ")->execute([$replyTo]);

    $stmt=$db->prepare("
    SELECT user_id
    FROM posts
    WHERE id=?
    ");

    $stmt->execute([$replyTo]);

    if($owner=$stmt->fetch()){
        createNotification(
            $db,
            $owner["user_id"],
            $user["id"],
            "reply",
            $postID
        );
    }
}

if($repostOf>0){

    $db->prepare("
    UPDATE posts
    SET reposts=reposts+1
    WHERE id=?
    ")->execute([$repostOf]);

    $stmt=$db->prepare("
    SELECT user_id
    FROM posts
    WHERE id=?
    ");

    $stmt->execute([$repostOf]);

    if($owner=$stmt->fetch()){
        createNotification(
            $db,
            $owner["user_id"],
            $user["id"],
            "repost",
            $repostOf
        );
    }
}

echo json_encode([
    "success"=>true,
    "post_id"=>$postID,
    "image"=>$image
],JSON_PRETTY_PRINT);
