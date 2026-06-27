<?php

header("Content-Type: application/json");

require_once "init.php";

$token=$_GET['token'] ?? '';

$stmt=$db->prepare("
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

$user=$stmt->fetch();

if(!$user){

die(json_encode([
"success"=>false
]));

}

$stmt=$db->prepare("

SELECT

notifications.*,

users.username,

users.display_name,

users.avatar

FROM notifications

LEFT JOIN users

ON users.id=notifications.from_user

WHERE notifications.user_id=?

ORDER BY notifications.created_at DESC

LIMIT 100

");

$stmt->execute([
$user['id']
]);

$list=[];

while($row=$stmt->fetch()){

$list[]=array(

"id"=>(int)$row['id'],

"type"=>$row['type'],

"post_id"=>$row['post_id']?intval($row['post_id']):null,

"read"=>(bool)$row['is_read'],

"created_at"=>(int)$row['created_at'],

"from"=>array(

"id"=>(int)$row['from_user'],

"username"=>$row['username'],

"display_name"=>$row['display_name'],

"avatar"=>$row['avatar']

)

);

}

echo json_encode([

"success"=>true,

"count"=>count($list),

"notifications"=>$list

],JSON_PRETTY_PRINT);
