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
 * @file group_email.php
 * @brief email to users group
 */

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Group';

require_once '../../include/baseTheme.php';
require_once 'include/sendMail.inc.php';

$group_id = intval($_REQUEST['group_id']);

$toolName = $langGroups;
$pageName = $langEmailGroup;
$navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langGroupSpace,
                      'url' => "group_space.php?group_id=$group_id", 'name' => $langGroups);

$tool_content .= action_bar(array(
    array(
        'title' => $langBack,
        'level' => 'primary-label',
        'icon' => 'fa-reply',
        'url' => "index.php?course=$course_code"
    )
));

$tutor_id = Database::get()->querySingle("SELECT is_tutor FROM group_members WHERE group_id = ?d", $group_id)->is_tutor;
$is_tutor = ($tutor_id == 1);

if (!$is_editor and ! $is_tutor) {
    header('Location: group_space.php?course=' . $course_code . '&group_id=' . $group_id);
    exit;
}

if ($is_editor or $is_tutor) {
    if (isset($_POST['submit'])) {
        $sender = Database::get()->querySingle("SELECT email, surname, givenname FROM user
                                                             WHERE id = ?d", $uid);
        $sender_name = $sender->givenname . ' ' . $sender->surname;
        $sender_email = $sender->email;
        $emailsubject = $title . " - " . $_POST['subject'];
        $emailbody = "$_POST[body_mail]\n\n$langSender: $sender->surname $sender->givenname <$sender->email>\n$langProfLesson\n";
        $req = Database::get()->queryArray("SELECT user_id FROM group_members WHERE group_id = ?d", $group_id);
        foreach ($req as $userid) {
            $email = Database::get()->querySingle("SELECT email FROM user WHERE id = $userid->user_id")->email;
            if (get_user_email_notification($userid->user_id, $course_id)) {
                $linkhere = "&nbsp;<a href='${urlServer}main/profile/emailunsubscribe.php?cid=$course_id'>$langHere</a>.";
                $unsubscribe = "<br /><br />$langNote: " . sprintf($langLinkUnsubscribe, $title);
                $emailbody .= $unsubscribe . $linkhere;
                if (email_seems_valid($email) and ! send_mail($sender_name, $sender_email, '', $email, $emailsubject, $emailbody, $charset)) {
                    $tool_content .= "<h4>$langMailError</h4>";
                }
            }
        }
        // aldo send email to professor
        send_mail($sender_name, $sender_email, '', $sender_email, $emailsubject, $emailbody, $charset);
        $tool_content .= "<div class='alert alert-success'>$langEmailSuccess<br>";
        $tool_content .= "<a href='index.php?course=$course_code'>$langBack</a></div>";
    } else {
        $tool_content .= "<div class='form-wrapper'>
                <form class='form-horizontal' role='form' action='$_SERVER[SCRIPT_NAME]?course=$course_code' method='post'>
                <fieldset>
                <input type='hidden' name='group_id' value='$group_id'>
                <div class='form-group'>                
                  <label>$langMailSubject</label>
                </div>
                <div class='form-group'>
                    <input type='text' name='subject' size='58' class='FormData_InputText'></input>
                </div>
                <div class='form-group'>
                  <label>$langMailBody</label>
                </div>
                <div class='form-group'>
                  <label><textarea name='body_mail' rows='10' cols='73' class='FormData_InputText'></textarea></label>
                </div>
                 <input class='btn btn-primary' type='submit' name='submit' value='$langSend'></input>                                
                </fieldset>
                 </form>
                 </div>";
    }
}
draw($tool_content, 2);
