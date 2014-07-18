<?php
PostReadCourses($ann_id){
    $uid = $_SESSION['user_id'];
    Database::get() -> queryFunc("INSERT INTO announcement_user (user_id, ann_id) VALUES ($uid, $ann_id)",
				 function($row){ echo "Added successfully"; });
}

?>
