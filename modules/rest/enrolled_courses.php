<?php
function GetEnrolledCourses() {	
	$uid = $_SESSION['uid'];
	$user_courses = "SELECT course.id course_id,
                                course.code code,
                                course.public_code,
                                course.title title,
                                course.prof_names professor,
                                course_user.status status
                           FROM course, course_user, user
                          WHERE course.id = course_user.course_id AND
                                course_user.user_id = ?d AND
                                user.id = ?d AND
                                course.visible != ?d
                       ORDER BY course.title, course.prof_names";
	$database = Database::get();
	$courses = array();

	$database->queryFunc($user_courses, function($row) use (&$courses) 
										{
											$courses[] = $row;
										},  
						intval($uid), intval($uid), COURSE_INACTIVE);
	echo json_encode($courses);
// 	echo var_dump($courses);
}
?>
