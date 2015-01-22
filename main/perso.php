<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

/**
 * @file perso.php
 * @brief displays user courses and courses activity
 */

require_once 'perso_functions.php';

if (!isset($_SESSION['uid'])) {
    die("Unauthorized Access!");
    exit;
}

if ($_SESSION['status'] == USER_TEACHER) {
    $extra = "AND course.visible != " . COURSE_INACTIVE;
} else {
    $extra = '';
}

$result2 = Database::get()->queryArray("SELECT course.id cid, course.code code, course.public_code,
                        course.title title, course.prof_names profs, course_user.status status
                FROM course JOIN course_user ON course.id = course_user.course_id
                WHERE course_user.user_id = ?d $extra ORDER BY status, course.title, course.prof_names", $uid);

$courses = array();
if (count($result2) > 0) {
    foreach ($result2 as $mycours) {
        $courses[$mycours->code] = $mycours->status;
    }
}
$_SESSION['courses'] = $courses;

$_user['persoLastLogin'] = last_login($uid);
$_user['lastLogin'] = str_replace('-', ' ', $_user['persoLastLogin']);

$user_assignments = $user_announcements = $user_documents = $user_agenda = $user_forumPosts = '';

//  Get user's course info
$user_lesson_info = getUserLessonInfo($uid);
//if user is registered to at least one lesson
if (count($lesson_ids) > 0) {
    // get user assignments    
    $user_assignments = getUserAssignments($lesson_ids);
    // get user announcements    
    $user_announcements = getUserAnnouncements($lesson_ids);
    // get user documents
    $user_documents = getUserDocuments($lesson_ids);
    // get user agenda    
    $user_agenda = getUserAgenda($lesson_ids);
    // get user forum posts    
    $user_forumPosts = getUserForumPosts($lesson_ids);
}

// get user latest personal messages
$user_messages = getUserMessages();

// create array with content
//BEGIN - Get user personal calendar
$today = getdate();
$day = $today['mday'];
$month = $today['mon'];
$year = $today['year'];
Calendar_Events::get_calendar_settings();
$user_personal_calendar = Calendar_Events::small_month_calendar($day, $month, $year);

//END - Get personal calendar
// ==  BEGIN create array with personalised content

$perso_tool_content = array(
    'lessons_content' => $user_lesson_info,
    'assigns_content' => $user_assignments,
    'docs_content' => $user_documents,
    'agenda_content' => $user_agenda,
    'forum_content' => $user_forumPosts,
    'personal_calendar_content' => $user_personal_calendar
);

