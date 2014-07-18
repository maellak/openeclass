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

/**
 * @brief check if a course is restricted
 * @param type $course_id
 * @return boolean
 */
function is_restricted($course_id) {
	$res = Database::get()->querySingle("SELECT visible FROM course WHERE id = ?d", intval($course_id));
	if ($res && $res->visible == 0) {
		return true;
	} else {
		return false;
	}
}

function PostEnrollCourse() {
	$SUCCESS = 0;
	$RESTRICTED = 1;
	$PASS_PROTECTED = 2;
	$COURSE_NOT_EXISTS = 3;
	$ALREADY_ENROLLED = 4;
	$NOT_LOGGED_IN = 5;
	if (!isset($_SESSION)) {
		$uid = $_SESSION['uid'];
		$cid = $_POST['cid'];
		$course_info = Database::get()->querySingle("SELECT public_code, password, visible FROM course WHERE id = ?d", $cid);
		if ($course_info) {
			$is_enrolled = Database::get()->querySingle("SELECT * FROM `course_user` WHERE `course_id` = ?d AND `user_id` = ?d", $cid, intval($uid));
			if (!$is_enrolled) {
				if (($course_info->visible == COURSE_REGISTRATION or
						$course_info->visible == COURSE_OPEN) and !empty($course_info->password) and
						$course_info->password !== autounquote($_POST['pass' . $cid])) {
					echo $PASS_PROTECTED;
				}
				if (is_restricted($cid) and !in_array($cid, $selectCourse)) { // do not allow registration to restricted course
					echo $RESTRICTED;
				} else {
					Database::get()->query("INSERT IGNORE INTO `course_user` (`course_id`, `user_id`, `status`, `reg_date`)
		                                        VALUES (?d, ?d, ?d, CURDATE())", $cid, intval($uid), USER_STUDENT);
					echo $SUCCESS;
				}
			} else {
				echo $ALREADY_ENROLLED;
			}
		} else {
			echo $COURSE_NOT_EXISTS;
		}
	} else {
		echo $NOT_LOGGED_IN;
	}
}
?>
