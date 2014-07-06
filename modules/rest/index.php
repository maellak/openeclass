<?php
// Initialize openClass libraries
define('RESPONSE_FAILED', json_encode(array('status' => 'FAILED')));

require __DIR__ . '/../../include/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$app->config('debug', true);
error_reporting(E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);

// Setup the REST routes
require_once (__DIR__.'/auth.php');
$app->map('/login', RequestAccessToken)->via('POST');
require_once (__DIR__.'/courses.php');
$app->map('/courses', GetCourses)->via('GET');
$app->map('/courses', PostCourses)->via('POST');
$app->map('/courses', DeleteCourses)->via('DELETE');
// 404 not found
$app->notFound(function () { echo json_encode(array('status' => 'NOT_FOUND')); });

$app->run();
?>