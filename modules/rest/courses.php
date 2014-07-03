<?php
function GetCourses() {
    $courses = array();

    $sql = "SELECT course.code,
                   course.lang,
                   course.title,
                   course.keywords,
                   course.visible,
                   course.prof_names,
                   course.public_code,
                   course_user.status as status
             FROM course JOIN course_user ON course.id = course_user.course_id
             WHERE course_user.user_id = ?d
             ORDER BY status, course.title, course.prof_names";

    $callback = function($course) use (&$courses) {
        $courses[] = $course;
    };

    Database::get()->queryFunc($sql, $callback, intval($_SESSION['uid']));
    echo json_encode($courses);
}
function PostCourses() {
    echo 'POST';
}
function DeleteCourses() {
    echo 'DELETE';
}
?>