<?php
function GetCourses() {
	$course=array();
	$database = Database::get();
	$database->queryFunc('SELECT code,lang,title,keywords,visible,prof_names,public_code FROM course', function($row)use (&$course) {$course[] = $row;} );
	echo json_encode($course);
}

function GetForums($cid) {
	$forums = array();
	$database = Database::get();
	$sql = 'SELECT id, name, course_id FROM forum WHERE course_id='.$cid;
	$database->queryFunc($sql, function($row)use (&$forums) {$forums[] = $row;});
	echo json_encode($forums);
	
}

function GetTopics($cid, $fid) {
	$topics = array();
	$database = Database::get();
	$sql = 'SELECT id, title, forum_id FROM forum_topic WHERE forum_id='.$fid;  // Εδώ έχω μια αμφιβολία μήπως χρειάζεται κάποιο join με τον πίνακα forum
	$database->queryFunc($sql, function($row)use (&$topics) {$topics[] = $row;});
	echo json_encode($topics);
	
}
?>
