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

$require_login = TRUE;
if(isset($_GET['course'])) {//course messages
    $require_current_course = TRUE;
} else {
    $require_current_course = FALSE;
}
$guest_allowed = FALSE;
include '../../include/baseTheme.php';
require_once 'include/lib/forcedownload.php';
require_once 'include/lib/fileUploadLib.inc.php';
require_once 'include/sendMail.inc.php';

$personal_msgs_allowed = get_config('dropbox_allow_personal_messages');

if (!isset($course_id) || !$course_id) {
    $course_id = 0;
} else {
    $dropbox_dir = $webDir . "/courses/" . $course_code . "/dropbox";
    // get dropbox quotas from database
    $d = Database::get()->querySingle("SELECT dropbox_quota FROM course WHERE code = ?s", $course_code);
    $diskQuotaDropbox = $d->dropbox_quota;
}

if (isset($_POST['course'])) {//for the case of course messages from central ui
    $cid = course_code_to_id($_POST['course']);
    if ($cid === false) {
        $cid = $course_id;
    } else {
        $dropbox_dir = $webDir . "/courses/" . $_POST['course'] . "/dropbox";
        // get dropbox quotas from database
        $d = Database::get()->querySingle("SELECT dropbox_quota FROM course WHERE code = ?s", $_POST['course']);
        $diskQuotaDropbox = $d->dropbox_quota;
    }
} else {
    $cid = $course_id;
}

$pageName = $langDropBox;

require_once("class.msg.php");

$file_attached = FALSE;

/*
  form submission
 */
if (isset($_POST['submit'])) {
    $error = FALSE;
    $errormsg = '';
    if (!isset($_POST['body'])) {
        $error = TRUE;
        $errormsg = $langBadFormData;
    } else if ($_POST['body'] == '') {
        $error = TRUE;
        $errormsg = $langEmptyMsg;
    } elseif(!isset($_REQUEST['course']) && !$personal_msgs_allowed) {
        $error = TRUE;
        $errormsg = $langGeneralError;
    } elseif (!isset($_POST['recipients']) or empty($_POST['recipients'])) {
        $error = TRUE;
        $errormsg = $langNoRecipients;
    } elseif (!empty($_FILES['file']['name'])) {
        $file_attached = TRUE;
    }
    /*
     * --------------------------------------
     *     FORM SUBMIT : UPLOAD NEW FILE
     * --------------------------------------
     */
    if (!$error) {
        if (!$file_attached) {
            $filename = '';
            $real_filename = '';
            $filesize = 0;
            $recipients = array();
            if (!is_array($_POST['recipients'])) { //in personal msg form select2 returns a comma delimited string instead of array
                $_POST['recipients'] = explode(',', $_POST['recipients']);
            }
            foreach ($_POST['recipients'] as $r) { // group ids have been prefixed with '_'
                if (preg_match('/^_/', $r)) {
                    $sql_res = Database::get()->queryArray("SELECT user_id FROM group_members WHERE group_id = SUBSTRING_INDEX(?s, '_', -1)", $r);
                    foreach ($sql_res as $ar) {
                        if ($ar->user_id != $uid) {
                            $recipients[] = $ar->user_id;
                        }
                    }
                } else {
                    $recipients[] = $r;
                }
            }
            $recipients = array_unique($recipients);
            if (isset($_POST['message_title']) and $_POST['message_title'] != '') {
                $subject = $_POST['message_title'];
            } else {
                $subject = $langMessage;
            }
            
            $msg = new Msg($uid, $cid, $subject, $_POST['body'], $recipients, $filename, $real_filename, $filesize);
        } else {
            $cwd = getcwd();
            if (is_dir($dropbox_dir)) {
                $dropbox_space = dir_total_space($dropbox_dir);
            }
            $filename = php2phps($_FILES['file']['name']);
            $filesize = $_FILES['file']['size'];
            $filetype = $_FILES['file']['type'];
            $filetmpname = $_FILES['file']['tmp_name'];

            validateUploadedFile($_FILES['file']['name'], 1);

            if ($filesize + $dropbox_space > $diskQuotaDropbox) {
                $errormsg = $langNoSpace;
                $error = TRUE;
            } elseif (!is_uploaded_file($filetmpname)) { // check user found : no clean error msg 
                die($langBadFormData);
            }
            // set title                       
            if (isset($_POST['message_title']) and $_POST['message_title'] != '') {
                $subject = $_POST['message_title'];
            } else {
                $subject = $langMessage;
            }
            $format = get_file_extension($filename);
            $real_filename = $filename;
            $filename = safe_filename($format);
            $recipients = $_POST["recipients"];
            //After uploading the file, create the db entries
            if (!$error) {
                $filename_final = $dropbox_dir . '/' . $filename;
                move_uploaded_file($filetmpname, $filename_final) or die($langUploadError);
                @chmod($filename_final, 0644);
                
                $msg = new Msg($uid, $cid, $subject, $_POST['body'], $recipients, $filename, $real_filename, $filesize);
            }            
            chdir($cwd);
        }        
        if (isset($_POST['mailing']) and $_POST['mailing']) { // send mail to recipients of dropbox file
            if ($course_id != 0 || isset($_POST['course'])) {//message in course context
                $c = course_id_to_title($cid);
                $subject_dropbox = "$c (".course_id_to_code($cid).") - $langNewDropboxFile";
                foreach ($recipients as $userid) {
                    if (get_user_email_notification($userid, $cid)) {
                        $linkhere = "<a href='${urlServer}main/profile/emailunsubscribe.php?cid=$cid'>$langHere</a>.";
                        $unsubscribe = "<br />" . sprintf($langLinkUnsubscribe, $c);
                        $body_dropbox_message = "$langSender: " . q($_SESSION['givenname']) . " " . q($_SESSION['surname']). " <br /><br /> $subject <br /><br />" . $_POST['body']. "<br />";
                        if ($filesize > 0) {
                                $body_dropbox_message .= "<a href='${urlServer}modules/dropbox/dropbox_download.php?course=".course_id_to_code($cid)."&amp;id=$msg->id'>[$langAttachedFile]</a><br /><br />";
                        }
                        $body_dropbox_message .= "$langNote: $langDoNotReply <a href='${urlServer}modules/dropbox/index.php?course=".course_id_to_code($cid)."'>$langHere</a>.<br />";
                        $body_dropbox_message .= "$unsubscribe $linkhere";
                        $plain_body_dropbox_message = html2text($body_dropbox_message);
                        $emailaddr = uid_to_email($userid);
                        send_mail_multipart('', '', '', $emailaddr, $subject_dropbox, $plain_body_dropbox_message, $body_dropbox_message, $charset);
                    }
                }
            } else {//message in personal context
                $subject_dropbox = $langNewDropboxFile;
                foreach ($recipients as $userid) {
                    if (get_user_email_notification($userid)) {
                        $linkhere = "<a href='${urlServer}main/profile/profile.php'>$langHere</a>.";
                        //$unsubscribe = "<br />" . sprintf($langLinkUnsubscribe, $title);
                        $body_dropbox_message = "$langSender: " . q($_SESSION['givenname']) . " " . q($_SESSION['surname']). " <br /><br /> $subject <br /><br />" . $_POST['body']. "<br />";
                        $body_dropbox_message .= "$langNote: $langDoNotReply <a href='${urlServer}modules/dropbox/index.php'>$langHere</a>.<br />";
                        //$body_dropbox_message .= "$unsubscribe $linkhere";
                        $plain_body_dropbox_message = html2text($body_dropbox_message);
                        $emailaddr = uid_to_email($userid);
                        send_mail_multipart('', '', '', $emailaddr, $subject_dropbox, $plain_body_dropbox_message, $body_dropbox_message, $charset);
                    }
                }
            }
        }
        $tool_content .= "<div class='alert alert-success'>$langdocAdd<br>";
    } else { //end if(!$error)
        $tool_content .= "<div class='alert alert-danger'>$errormsg<br>";
    }
    if ($course_id == 0) {
        $tool_content .= "<a href='index.php'>$langBack</a></div><br>";
    } else {
        $tool_content .= "<a href='index.php?course=$course_code'>$langBack</a></div><br>";
    }
}

if ($course_id == 0) {
    draw($tool_content, 1, null, $head_content);
} else {
    draw($tool_content, 2, null, $head_content);
}
