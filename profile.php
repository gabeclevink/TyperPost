// Users Profiles
// by Gabe Clevin K
<?php

header("Content-Type: application/json");

require_once "init.php";

$username = trim($_GET['username'] ?? '');
$id = intval($_GET['id'] ?? 0);

if ($username == "" && $id == 0)
{
    die(json_encode([
        "success" => false,
        "error" => "missing_user"
    ]));
}

if ($id > 0)
{
    $stmt = $db->prepare("
        SELECT *
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);
}
else
{
    $stmt = $db->prepare("
        SELECT *
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $stmt->execute([$username]);
}

$user = $stmt->fetch();

if (!$user)
{
    die(json_encode([
        "success" => false,
        "error" => "user_not_found"
    ]));
}

$followers = $db->prepare("
SELECT COUNT(*)
FROM follows
WHERE following=?
");
$followers->execute([$user['id']]);

$following = $db->prepare("
SELECT COUNT(*)
FROM follows
WHERE follower=?
");
$following->execute([$user['id']]);

$postCount = $db->prepare("
SELECT COUNT(*)
FROM posts
WHERE user_id=?
");
$postCount->execute([$user['id']]);

$stmt = $db->prepare("
SELECT
    id,
    text,
    image,
    likes,
    replies,
    reposts,
    reply_to,
    repost_of,
    created_at
FROM posts
WHERE user_id=?
ORDER BY created_at DESC
LIMIT 50
");

$stmt->execute([$user['id']]);

$posts = [];

while ($row = $stmt->fetch())
{
    $posts[] = [
        "id" => (int)$row["id"],
        "text" => $row["text"],
        "image" => $row["image"],
        "likes" => (int)$row["likes"],
        "replies" => (int)$row["replies"],
        "reposts" => (int)$row["reposts"],
        "reply_to" => $row["reply_to"] ? (int)$row["reply_to"] : null,
        "repost_of" => $row["repost_of"] ? (int)$row["repost_of"] : null,
        "created_at" => (int)$row["created_at"]
    ];
}

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

        "verified" => (bool)$user["verified"],

        "followers" => (int)$followers->fetchColumn(),

        "following" => (int)$following->fetchColumn(),

        "posts_count" => (int)$postCount->fetchColumn(),

        "created_at" => (int)$user["created_at"]

    ],

    "posts" => $posts

], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
