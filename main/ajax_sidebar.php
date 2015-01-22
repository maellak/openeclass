<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2015  Greek Universities Network - GUnet
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
 * @file ajax_sidebar.php
 * @brief Sidebar AJAX handler
 */
$require_login = true;

require_once '../include/baseTheme.php';
require_once 'perso_functions.php';
require_once 'template/template.inc.php';
require_once 'main/notifications/notifications.inc.php';

header('Content-Type: application/json; charset=UTF-8');

function getSidebarNotifications() {
    global $modules, $admin_modules, $theme_settings;

    $notifications_html = array();
    if (isset($_POST['courseIDs']) and count($_POST['courseIDs'])) {
        $t = new Template();
        $t->set_var('sideBarCourseNotifyBlock', $_SESSION['template']['sideBarCourseNotifyBlock']);
        foreach ($_POST['courseIDs'] as $id) {
            $t->set_var('sideBarCourseNotify', '');
            $notifications = get_course_notifications($id);
            foreach ($notifications as $n) {
                $modules_array = (isset($modules[$n->module_id]))? $modules: $admin_modules;
                if (isset($modules_array[$n->module_id]) &&
                    isset($modules_array[$n->module_id]['image']) &&
                    isset($theme_settings['icon_map'][$modules_array[$n->module_id]['image']])) {
                    $t->set_var('sideBarCourseNotifyIcon', $theme_settings['icon_map'][$modules_array[$n->module_id]['image']]);
                    $t->set_var('sideBarCourseNotifyCount', $n->notcount);
                    $t->parse('sideBarCourseNotify', 'sideBarCourseNotifyBlock', true);
                }
            }
            $notifications_html[$id] = $t->get_var('sideBarCourseNotify');
        }
    }
    return $notifications_html;
}

function getSidebarMessages() {
    global $uid, $urlServer, $langFrom, $dateFormatLong, $langDropboxNoMessage;

    $message_content = '';

    $mbox = new Mailbox($uid, 0);
    $msgs = $mbox->getInboxMsgs('', 5);

    $msgs = array_filter($msgs, function ($msg) { return !$msg->is_read; });
    if (!count($msgs)) {
        $message_content .= "<li class='list-item'>" .
                            "<span class='item-wholeline'>" .
                                $langDropboxNoMessage .
                            "</span>" .
                         "</li>";
    } else {
        foreach ($msgs as $message) {
            if ($message->course_id > 0) {
                $course_title = q(ellipsize(course_id_to_title($message->course_id), 30));
            } else {
                $course_title = '';
            }

            $message_date = claro_format_locale_date($dateFormatLong, $message->timestamp);
            $message_content .= "<li class='list-item'>" .
                            "<span class='item-wholeline'>" .
                                "<div class='text-title'>$langFrom " .
                                    display_user($message->author_id, false, false) . ":<br>" .
                                    "<a href='{$urlServer}modules/dropbox/index.php?mid=$message->id'>" .
                                        q($message->subject) . "</a>" .
                                "</div>" .
                                "<div class='text-grey'>$course_title</div>" .
                                "<div>$message_date</div>" .
                                "</span>" .
                            "</li>";
        }
    }
    return $message_content;
}

$json_obj = array(
    'messages' => getSidebarMessages(),
    'notifications' => getSidebarNotifications()
);
echo json_encode($json_obj, JSON_UNESCAPED_UNICODE);
