<?php
define('RESPONSE_FAILED', json_encode(array('status' => 'FAILED')));

$_POST = json_decode(file_get_contents('php://input'), true);

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
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$app->config('debug', true);
error_reporting(E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);

// Setup the REST routes
require_once (__DIR__.'/../../include/init.php');
require_once (__DIR__.'/auth.php');
$app->map('/login', RequestAccessToken)->via('POST', 'OPTIONS');
require_once (__DIR__.'/courses.php');
$app->map('/courses', GetCourses)->via('GET', 'OPTIONS');
$app->map('/courses/:cid/forums', GetForums)->via('GET', 'OPTIONS');
$app->map('/courses/:cid/forums/:fid/topics', GetTopics)->via('GET', 'OPTIONS');
//$app->map('/courses', CheckAuth, PostCourses)->via('POST', 'OPTIONS');
//$app->map('/courses', CheckAuth, DeleteCourses)->via('DELETE', 'OPTIONS');
// 404 not found
$app->notFound(function () { echo json_encode(array('status' => 'NOT_FOUND')); });

$app->run();
?>
