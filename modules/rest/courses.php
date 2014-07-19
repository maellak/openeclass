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
	$sql = 'SELECT id, name, course_id, num_topics FROM forum WHERE course_id = '.$cid;
	$database->queryFunc($sql, function($row) use (&$forums) {$forums[] = $row;});
	echo json_encode($forums);
	
}

function GetTopics($cid, $fid) {
	$topics = array();
	$database = Database::get();
	$sql = 'SELECT id, title, forum_id FROM forum_topic WHERE forum_id = '.$fid;  // Εδώ έχω μια αμφιβολία μήπως χρειάζεται κάποιο join με τον πίνακα forum
	$database->queryFunc($sql, function($row) use (&$topics) {$topics[] = $row;});
	echo json_encode($topics);

}

function PostTopic($cid, $fid) {
		
	define('RESPONSE_FAILED', json_encode(array('status' => 'FAILED')));
	$_POST = json_decode(file_get_contents('php://input'), true);
	
	// Ανάκτηση του τίτλου του topic
	$topic = $_POST['title'];
	// Ανακτηση του poster_id
	$poster_id = $_SESSION['uid'];
	
	// Αποθήκευση του topic
	if(!empty($topic)) {
		Database::get()->query("INSERT INTO forum_topic (forum_topic.title, forum_topic.poster_id, forum_topic.topic_time, forum_topic.forum_id) VALUES (?s, ?d, NOW(), ?d)", $topic, intval($poster_id), intval($fid));
		
		// Ανακτούμε τον αριθμό των topics που περιέχει το συγκεκριμένο forum για να τον ενημερώσουμε
		$sqlNumTopics = 'SELECT num_topics FROM forum WHERE forum.id = '.$fid;
	    $myrow = Database::get()->querySingle($sqlNumTopics);
	    $myrow = (array) $myrow;
		$num_topics = $myrow['num_topics'];
		$num_topics++;
		
		// Ενημέρωση του αριθμού των topics στον πίνακα forum
		Database::get()->query("UPDATE forum SET num_topics = $num_topics WHERE forum.id = $fid");
		
		echo json_encode(array('success' => 'True'));
	} else {
		echo json_encode(array('success' => 'False'));	
	}	
	
}

function GetPosts($cid, $fid, $tid) {
	$posts = array();
	$database = Database::get();
	$sql = 'SELECT * FROM forum_post WHERE topic_id = '.$tid;
	$database->queryFunc($sql, function($row)use (&$posts) {$posts[] = $row;});
	echo json_encode($posts);
}

function PostPosts($cid, $fid, $tid) {
	define('RESPONSE_FAILED', json_encode(array('status' => 'FAILED')));
	$_POST = json_decode(file_get_contents('php://input'), true);
	
	// Ανάκτηση των στοιχείων του post
	$post_text = $_POST['post_text'];
	$poster_id = $_SESSION['uid'];
	$parent_post_id = 0; // Εδώ τι βάζουμε άραγε;;;
		
	// Αποθήκευση του post
	if(!empty($post_text)) {
		$post = Database::get()->query("INSERT INTO forum_post (forum_post.topic_id, forum_post.post_text, forum_post.poster_id, forum_post.post_time, forum_post.poster_ip, forum_post.parent_post_id) VALUES (?d, ?s, ?d, NOW(), ?s, ?d)", intval($tid), $post_text, intval($poster_id), $_SERVER['REMOTE_ADDR'], intval($parent_post_id));
		// Ενημέρωση του last_post_id στον πίνακα forum_topic
		$last_post_id = $post->lastInsertID;
		Database::get()->query("UPDATE forum_topic SET last_post_id = $last_post_id WHERE forum_topic.id = $tid");
		echo json_encode(array('success' => 'True'));
	} else {
		echo json_encode(array('success' => 'False'));	
	}
	
}

?>