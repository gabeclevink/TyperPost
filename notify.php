<?php

function createNotification($db,$user_id,$from_user,$type,$post_id=NULL)
{
    if($user_id==$from_user)
        return;

    $stmt=$db->prepare("
    INSERT INTO notifications
    (
        user_id,
        from_user,
        type,
        post_id,
        created_at
    )
    VALUES(?,?,?,?,?)
    ");

    $stmt->execute([
        $user_id,
        $from_user,
        $type,
        $post_id,
        time()
    ]);
}
