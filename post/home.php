<?php

header("Content-Type: application/json");

require_once "../init.php";

$token=$_GET["token"] ?? "";

$stmt=$db->prepare("
SELECT user_id
FROM sessions
WHERE token=?
AND expires>?
");

$stmt->execute([
$token,
time()
]);

$me=$stmt->fetch();

if(!$me){

die(json_encode([
"success"=>false,
"error"=>"invalid_token"
]));

}

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

WHERE

posts.user_id=?

OR

posts.user_id IN
(
SELECT following
FROM follows
WHERE follower=?
)

ORDER BY posts.created_at DESC

LIMIT 50

");

$stmt->execute([
$me["user_id"],
$me["user_id"]
]);

$feed=[];

while($row=$stmt->fetch()){

$feed[]=array(

"id"=>(int)$row["id"],

"text"=>$row["text"],

"image"=>$row["image"],

"likes"=>(int)$row["likes"],

"replies"=>(int)$row["replies"],

"reposts"=>(int)$row["reposts"],

"created_at"=>(int)$row["created_at"],

"user"=>array(

"id"=>(int)$row["user_id"],

"username"=>$row["username"],

"display_name"=>$row["display_name"],

"avatar"=>$row["avatar"],

"verified"=>(bool)$row["verified"]

)

);

}

echo json_encode([

"success"=>true,

"posts"=>$feed

],JSON_PRETTY_PRINT);
