<?php

header("Content-Type: application/json");
require_once "../init.php";

$token = trim($_POST['token'] ?? '');

if ($token == "") {
    die(json_encode([
        "success"=>false,
        "error"=>"missing_token"
    ]));
}

$stmt = $db->prepare("
SELECT users.*
FROM sessions
INNER JOIN users ON users.id=sessions.user_id
WHERE sessions.token=?
AND sessions.expires>?
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

if (!isset($_FILES["banner"])) {
    die(json_encode([
        "success"=>false,
        "error"=>"missing_file"
    ]));
}

$file = $_FILES["banner"];

if ($file["error"] != UPLOAD_ERR_OK) {
    die(json_encode([
        "success"=>false,
        "error"=>"upload_failed"
    ]));
}

if ($file["size"] > (8 * 1024 * 1024)) {
    die(json_encode([
        "success"=>false,
        "error"=>"file_too_large"
    ]));
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

$allowed = [
    "image/jpeg"=>"jpg",
    "image/png"=>"png",
    "image/gif"=>"gif",
    "image/webp"=>"webp"
];

if (!isset($allowed[$mime])) {
    die(json_encode([
        "success"=>false,
        "error"=>"invalid_image"
    ]));
}

$ext = $allowed[$mime];

$dir = "../uploads/banners/";

foreach (glob($dir.$user["id"].".*") as $old)
    @unlink($old);

$filename = $user["id"].".".$ext;
$path = $dir.$filename;

if (!move_uploaded_file($file["tmp_name"], $path)) {
    die(json_encode([
        "success"=>false,
        "error"=>"save_failed"
    ]));
}

$db->prepare("
UPDATE users
SET banner=?
WHERE id=?
")->execute([
    "uploads/banners/".$filename,
    $user["id"]
]);

echo json_encode([
    "success"=>true,
    "banner"=>"uploads/banners/".$filename
], JSON_PRETTY_PRINT);
