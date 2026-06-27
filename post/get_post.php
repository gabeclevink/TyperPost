<?php

header("Content-Type: application/json");

require_once "../init.php";

$id=intval($_GET["id"] ?? 0);

$stmt=$db->prepare("
SELECT

posts.*,

users.username,
users.display_name,
users.avatar,
users.verified

FROM posts

INNER JOIN users

ON users.id=posts.user_id

WHERE posts.id=?

LIMIT 1
");

$stmt->execute([$id]);

$post=$stmt->fetch();

if(!$post){

die(json_encode([
"success"=>false,
"error"=>"post_not_found"
]));

}

echo json_encode([

"success"=>true,

"post"=>[

"id"=>(int)$post["id"],

"text"=>$post["text"],

"image"=>$post["image"],

"likes"=>(int)$post["likes"],

"replies"=>(int)$post["replies"],

"reposts"=>(int)$post["reposts"],

"reply_to"=>$post["reply_to"],

"repost_of"=>$post["repost_of"],

"created_at"=>(int)$post["created_at"],

"user"=>[

"id"=>(int)$post["user_id"],

"username"=>$post["username"],

"display_name"=>$post["display_name"],

"avatar"=>$post["avatar"],

"verified"=>(bool)$post["verified"]

]

]

],JSON_PRETTY_PRINT);
