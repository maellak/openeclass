<?php
ini_set("session.use_cookies",0);
ini_set("session.use_only_cookies",0);
ini_set("session.use_trans_sid",1); # Forgot this one!
ini_set("session.name",'access_token');

define('RESPONSE_FAILED', json_encode(array('status' => 'FAILED')));

$_POST = json_decode(file_get_contents('php://input'), true);


    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // cache for 1 day
    }

    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day

    }
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    }

require __DIR__ . '/../../include/Slim/Slim.php';
require_once (__DIR__.'/../../include/init.php');
require_once (__DIR__.'/auth.php');
require_once (__DIR__.'/courses.php');
require_once (__DIR__.'/ann.php');
//require_once(__DIR__.'/ann.php');
require_once (__DIR__.'/enrolled_courses.php');
require_once (__DIR__.'/docu.php'); 
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$app->config('debug', true);
error_reporting(E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);

// Setup the REST routes

$app->map('/login', RequestAccessToken)->via('POST', 'OPTIONS');
$app->map('/courses', GetCourses)->via('GET', 'OPTIONS');
$app->map('/courses/:cid/announcements',CheckAuth,GetAnn)->via('GET', 'OPTIONS');
$app->map('/courses/announcements' ,CheckAuth, GetAllAnn)->via('GET', 'OPTIONS');

//$app->map('/courses', CheckAuth, PostCourses)->via('POST', 'OPTIONS');
//$app->map('/courses', CheckAuth, DeleteCourses)->via('DELETE', 'OPTIONS');

$app->map('/courses', GetCourses)->via('GET', 'OPTIONS');
$app->map('/courses/:cid', CheckAuth, DeleteCourses)->via('DELETE', 'OPTIONS');
$app->map('/courses/:cid/forums', CheckAuth, GetForums)->via('GET', 'OPTIONS');
$app->map('/courses/:cid/forums/:fid/topics/:tid/posts', CheckAuth, GetPosts)->via('GET', 'OPTIONS');
$app->map('/courses/:cid/forums/:fid/topics/:tid/posts', CheckAuth, PostPosts)->via('POST', 'OPTIONS');
$app->map('/courses/:cid/forums/:fid/topics', CheckAuth, GetTopics)->via('GET', 'OPTIONS');
$app->map('/courses/:cid/forums/:fid/topics', CheckAuth, PostTopic)->via('POST', 'OPTIONS');
$app->map('/enrolledcourses', GetEnrolledCourses)->via('GET', 'OPTIONS');
$app->map('/enrollcourse', CheckAuth, PostEnrollCourse)->via('POST', 'OPTIONS');
$app->map('/login/status', GetCheckNet)->via('GET', 'OPTIONS');
$app->map('/courses/announcements/:aid/read', CheckAuth, PostReadAnnouncements)->via('POST', 'OPTIONS');  
$app->map('/courses/:cid/documents' ,CheckAuth, GetDoc)->via('GET','OPTIONS');
$app->map('/courses/:cid', CheckAuth, DeleteCourses)->via('DELETE', 'OPTIONS');

// 404 not found
$app->notFound(function () { echo json_encode(array('status' => 'NOT_FOUND')); });

$app->run();
?>
