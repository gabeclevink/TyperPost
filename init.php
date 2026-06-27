// Database/Setup File for TyperPost!
// by Gabe Clevin K
<?php

date_default_timezone_set("UTC");

$dbFile = __DIR__ . "/pz0.db";

$firstRun = !file_exists($dbFile);

try {

    $db = new PDO("sqlite:" . $dbFile);

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec("PRAGMA foreign_keys = ON;");
    $db->exec("PRAGMA journal_mode = WAL;");
    $db->exec("PRAGMA synchronous = NORMAL;");

} catch(PDOException $e){

    die(json_encode(array(
        "success"=>false,
        "error"=>"Database connection failed",
        "message"=>$e->getMessage()
    )));
}

$db->exec("
CREATE TABLE IF NOT EXISTS users (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,

    display_name TEXT,
    bio TEXT,

    avatar TEXT DEFAULT '',
    banner TEXT DEFAULT '',

    website TEXT DEFAULT '',
    location TEXT DEFAULT '',

    verified INTEGER DEFAULT 0,

    followers INTEGER DEFAULT 0,
    following INTEGER DEFAULT 0,
    posts INTEGER DEFAULT 0,

    created_at INTEGER

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS posts(

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    user_id INTEGER NOT NULL,

    text TEXT,
    image TEXT,

    reply_to INTEGER DEFAULT NULL,
    repost_of INTEGER DEFAULT NULL,

    likes INTEGER DEFAULT 0,
    replies INTEGER DEFAULT 0,
    reposts INTEGER DEFAULT 0,

    created_at INTEGER,

    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS follows(

    follower INTEGER,
    following INTEGER,

    PRIMARY KEY(follower, following),

    FOREIGN KEY(follower) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(following) REFERENCES users(id) ON DELETE CASCADE

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS likes(

    user_id INTEGER,
    post_id INTEGER,

    PRIMARY KEY(user_id, post_id),

    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS reposts(

    user_id INTEGER,
    post_id INTEGER,

    PRIMARY KEY(user_id, post_id),

    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS sessions(

    token TEXT PRIMARY KEY,

    user_id INTEGER,

    expires INTEGER,

    created_at INTEGER,

    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS notifications(

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    user_id INTEGER,
    from_user INTEGER,

    type TEXT,

    post_id INTEGER,

    is_read INTEGER DEFAULT 0,

    created_at INTEGER,

    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(from_user) REFERENCES users(id)

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS media(

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    user_id INTEGER,

    filename TEXT,
    type TEXT,

    created_at INTEGER,

    FOREIGN KEY(user_id) REFERENCES users(id)

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS hashtags(

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    tag TEXT UNIQUE,

    uses INTEGER DEFAULT 0

);
");

$db->exec("
CREATE TABLE IF NOT EXISTS post_hashtags(

    post_id INTEGER,

    hashtag_id INTEGER,

    PRIMARY KEY(post_id, hashtag_id)

);
");

$db->exec("CREATE INDEX IF NOT EXISTS idx_posts_user ON posts(user_id);");
$db->exec("CREATE INDEX IF NOT EXISTS idx_posts_time ON posts(created_at);");

$db->exec("CREATE INDEX IF NOT EXISTS idx_follow_follower ON follows(follower);");
$db->exec("CREATE INDEX IF NOT EXISTS idx_follow_following ON follows(following);");

$db->exec("CREATE INDEX IF NOT EXISTS idx_like_post ON likes(post_id);");
$db->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);");


@mkdir(__DIR__."/uploads");
@mkdir(__DIR__."/uploads/avatars");
@mkdir(__DIR__."/uploads/banners");
@mkdir(__DIR__."/uploads/media");


if ($firstRun) {

    echo json_encode(array(
        "success"=>true,
        "message"=>"Database created successfully."
    ));

}
