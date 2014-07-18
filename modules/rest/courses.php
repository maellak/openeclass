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
	$sql = 'SELECT id, name, course_id, num_topics FROM forum WHERE course_id='.$cid;  // Πρόσθεσα και τον αριθμό των topic που έχει το κάθε forum
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

function PostTopic($cid, $fid) {
	define('RESPONSE_FAILED', json_encode(array('status' => 'FAILED')));
	$_POST = json_decode(file_get_contents('php://input'), true);
	
	// Ανάκτηση του τίτλου του topic
	$topic = $_POST['title'];
	
	// Αποθήκευση του topic
	Database::get()->query("INSERT INTO forum_topic (forum_topic.title, forum_topic.topic_time, forum_topic.forum_id)
                                              VALUES (?s, NOW(), $fid)", $topic);
	session_regenerate_id();
	
	// Ανακτούμε τον αριθμό των topics που περιέχει το συγκεκριμένο forum για να τον ενημερώσουμε
	$sqlNumTopics = 'SELECT num_topics FROM forum WHERE forum.id = '.$fid;
    $myrow = Database::get()->querySingle($sqlNumTopics);
    $myrow = (array) $myrow;
	$num_topics = $myrow['num_topics'];
	$num_topics++;
	
	// Ενημέρωση του αριθμού των topics στον πίνακα forum
	Database::get()->query("UPDATE forum SET num_topics = $num_topics WHERE forum.id = $fid");
	session_regenerate_id();
	
	echo json_encode(array('success' => 'True'));
	
}
?>
