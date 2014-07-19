<?php
PostReadCourses($ann_id){
    $uid = $_SESSION['user_id'];
    $query = "INSERT INTO announcement_user (user_id, ann_id) VALUES ($uid, $ann_id)";
    Database::get() -> querySingle($query);
    alert("Announcment marked as read");
}

?>
