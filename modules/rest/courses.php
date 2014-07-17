<?php
function GetCourses() {
   
//$movies = array("Dramas"=>5,"Comedies"=>6);

//echo json_encode($movies);

//pairnoume ti vasi mas, vazoume ti vasi diladi se mia metavliti

$database = Database::get();
$courses = array();

$database->queryFunc
(

'SELECT title FROM course', function($row) use (&$courses) 
{

$courses[] = $row;
}

);

//var_dump($row);

echo json_encode($courses);


// echo 'GET';
}
function PostCourses() {
    echo 'POST';
}
function DeleteCourses() {
    echo 'DELETE';
}
?>