<?php
function GetCourses() {
	$course=array();
	$database = Database::get();
	$database->queryFunc('SELECT code,lang,title,keywords,visible,prof_names,public_code FROM course', function($row)use (&$course) {$course[] = $row;} );
	echo json_encode($course);
}
?>
