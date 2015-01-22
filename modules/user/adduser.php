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
 * @file adduser.php
 * @brief Course admin can add users to the course.
 */
$require_current_course = true;
$require_course_admin = true;
$require_help = true;
$helpTopic = 'User';

require_once '../../include/baseTheme.php';
require_once 'include/sendMail.inc.php';
require_once 'include/log.php';

$toolName = $langUsers;
$pageName = $langAddUser;
$navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langUsers);

if (isset($_GET['add'])) {
    $uid_to_add = intval($_GET['add']);
    $result = Database::get()->query("INSERT IGNORE INTO course_user (user_id, course_id, status, reg_date)
                                    VALUES (?d, ?d, " . USER_STUDENT . ", CURDATE())", $uid_to_add, $course_id);

    Log::record($course_id, MODULE_ID_USERS, LOG_INSERT, array('uid' => $uid_to_add,
                                                               'right' => '+5'));
    if ($result) {
        $tool_content .= "<div class='alert alert-success'>$langTheU $langAdded</div>";
        // notify user via email
        $email = uid_to_email($uid_to_add);
        if (!empty($email) and email_seems_valid($email)) {
            $emailsubject = "$langYourReg " . course_id_to_title($course_id);
            $emailbody = "$langNotifyRegUser1 '" . course_id_to_title($course_id) . "' $langNotifyRegUser2 $langFormula \n$gunet";
            send_mail('', '', '', $email, $emailsubject, $emailbody, $charset);
        }
    } else {
        $tool_content .= "<div class='alert alert-warning'>$langAddError</div>";
    }
    $tool_content .= "<br /><p><a href='$_SERVER[SCRIPT_NAME]?course=$course_code'>$langAddBack</a></p><br />\n";
} else {    
    register_posted_variables(array('search_surname' => true,
                                    'search_givenname' => true,
                                    'search_username' => true,
                                    'search_am' => true), 'any');

    $tool_content .= action_bar(array(
            array('title' => $langBack,
                  'url' => "index.php?course=$course_code",
                  'icon' => 'fa-reply',
                  'level' => 'primary-label'
                 )));

    $tool_content .= "<div class='alert alert-info'>$langAskUser</div>
                <div class='form-wrapper'>
                <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code'>                
                <fieldset>
                <div class='form-group'>
                <label for='surname' class='col-sm-2 control-label'>$langSurname:</label>
                <div class='col-sm-10'>
                    <input class='form-control' id='surname' type='text' name='search_surname' value='" . q($search_surname) . "' placeholder='$langSurname'></div>
                </div>
                <div class='form-group'>
                <label for='name' class='col-sm-2 control-label'>$langName:</label>
                <div class='col-sm-10'>
                    <input class='form-control' id='name' type='text' name='search_givenname' value='" . q($search_givenname) . "' placeholder='$langName'></div>
                </div>
                <div class='form-group'>
                <label for='username' class='col-sm-2 control-label'>$langUsername:</label>
                <div class='col-sm-10'>
                    <input class='form-control' id='username' type='text' name='search_username' value='" . q($search_username) . "' placeholder='$langUsername'></div>
                </div>
                <div class='form-group'>
                <label for='am' class='col-sm-2 control-label'>$langAm:</label>
                <div class='col-sm-10'>
                    <input class='form-control' id='am' type='text' name='search_am' value='" . q($search_am) . "' placeholder='$langAm'></div>
                </div>
                <div class='col-sm-offset-2 col-sm-10'>
                    <input class='btn btn-primary' type='submit' name='search' value='$langSearch'>
                    <a class='btn btn-default' href='index.php?course=$course_code'>$langCancel</a>
                </div>
                </fieldset>
                </form>
                </div>";

    $search = array();
    $values = array();
    foreach (array('surname', 'givenname', 'username', 'am') as $term) {
        $tvar = 'search_' . $term;
        if (!empty($GLOBALS[$tvar])) {
            $search[] = "u.$term LIKE ?s";
            $values[] = $GLOBALS[$tvar] . '%';
        }
    }
    $query = join(' AND ', $search);
    if (!empty($query)) {
        Database::get()->query("CREATE TEMPORARY TABLE lala AS
                    SELECT user_id FROM course_user WHERE course_id = ?d", $course_id);
        $result = Database::get()->queryArray("SELECT u.id, u.surname, u.givenname, u.username, u.am FROM
                                                user u LEFT JOIN lala c ON u.id = c.user_id WHERE
                                                c.user_id IS NULL AND $query", $values);
        if ($result) {
            $tool_content .= "<table class='table-default'>
                                <tr>
                                  <th>$langID</th>
                                  <th>$langName</th>
                                  <th>$langSurname</th>
                                  <th>$langUsername</th>
                                  <th>$langAm</th>
                                  <th>$langActions</th>
                                </tr>";
            $i = 1;
            foreach ($result as $myrow) {                
                $tool_content .= "<td class='text-right'>$i.</td><td>" . q($myrow->givenname) . "</td><td>" .
                        q($myrow->surname) . "</td><td>" . q($myrow->username) . "</td><td>" .
                        q($myrow->am) . "</td><td class='text-center'>" .
                        icon('fa-sign-in', $langRegister, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;add=$myrow->id"). "</td></tr>";
                $i++;
            }
            $tool_content .= "</table>";
        } else {
            $tool_content .= "<div class='alert alert-danger'>$langNoUsersFound</div>";
        }
        Database::get()->query("DROP TABLE lala");
    }
}
draw($tool_content, 2);
