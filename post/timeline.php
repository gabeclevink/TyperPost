<?php

header("Content-Type: application/json");
require_once "init.php";

$id = intval($_GET["id"] ?? 0);
$username = trim($_GET["username"] ?? "");

if ($id == 0 && $username == "") {

    die(json_encode([
        "success" => false,
        "error" => "missing_user"
    ]));

}

if ($id > 0) {

    $stmt = $db->prepare("
    SELECT *
    FROM users
    WHERE id=?
    LIMIT 1
    ");

    $stmt->execute([$id]);

} else {

    $stmt = $db->prepare("
    SELECT *
    FROM users
    WHERE username=?
    LIMIT 1
    ");

    $stmt->execute([$username]);

}

$user = $stmt->fetch();

if (!$user) {

    die(json_encode([
        "success" => false,
        "error" => "user_not_found"
    ]));

}

$stmt = $db->prepare("
SELECT *

FROM posts

WHERE user_id=?

ORDER BY created_at DESC
");

$stmt->execute([
    $user["id"]
]);

$posts = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
{

    $posts[] = [

        "id" => (int)$row["id"],

        "text" => $row["text"],

        "image" => $row["image"],

        "likes" => (int)$row["likes"],

        "replies" => (int)$row["replies"],

        "reposts" => (int)$row["reposts"],

        "reply_to" => $row["reply_to"],

        "repost_of" => $row["repost_of"],

        "created_at" => (int)$row["created_at"]

    ];

}

echo json_encode([

    "success" => true,

    "user" => [

        "id" => (int)$user["id"],

        "username" => $user["username"],

        "display_name" => $user["display_name"],

        "avatar" => $user["avatar"],

        "verified" => (bool)$user["verified"]

    ],

    "posts" => $posts

], JSON_PRETTY_PRINT);
