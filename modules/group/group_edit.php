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
 *  
 * @file group_edit.php
 * @brief group editing
 *
 */
$require_login = TRUE;
$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Group';

require_once '../../include/baseTheme.php';
$toolName = $langGroups;
$pageName = $langEditGroup;

require_once 'group_functions.php';
initialize_group_id();
initialize_group_info($group_id);

$navigation[] = array('url' => 'index.php?course=' . $course_code, 'name' => $langGroups);
$navigation[] = array('url' => "group_space.php?course=$course_code&amp;group_id=$group_id", 'name' => q($group_name));

load_js('select2');
$head_content .= "<script type='text/javascript'>
    $(document).ready(function () {
        $('#select-tutor').select2();              
    });
    </script>
";
if (!($is_editor or $is_tutor)) {
    header('Location: group_space.php?course=' . $course_code . '&group_id=' . $group_id);
    exit;
}

$head_content .= "<script type='text/javascript' src='{$urlAppend}js/tools.js'></script>\n" .
        "<script type='text/javascript'>var langEmptyGroupName = '" .
        js_escape($langEmptyGroupName) . "';</script>\n";

$message = '';
// Once modifications have been done, the user validates and arrives here
if (isset($_POST['modify'])) {    
    // Update main group settings
    register_posted_variables(array('name' => true, 'description' => true), 'all');
    register_posted_variables(array('maxStudent' => true), 'all');
    $student_members = $member_count - count($tutors);
    if ($maxStudent != 0 and $student_members > $maxStudent) {
        $maxStudent = $student_members;
        $message .= "<div class='alert alert-warning'>$langGroupMembersUnchanged</div>";
    }
    Database::get()->query("UPDATE `group`
                                    SET name = ?s,
                                        description = ?s,
                                        max_members = ?d
                                    WHERE id = ?d", $name, $description, $maxStudent, $group_id);

    Database::get()->query("UPDATE forum SET name = ?s WHERE id =
                        (SELECT forum_id FROM `group` WHERE id = ?d)
                            AND course_id = ?d", $name, $group_id, $course_id);

    if ($is_editor) {
        if (isset($_POST['tutor'])) {
            Database::get()->query("DELETE FROM group_members
                                     WHERE group_id = ?d AND is_tutor = 1", $group_id);
            foreach ($_POST['tutor'] as $tutor_id) {
                $tutor_id = intval($tutor_id);
                Database::get()->query("REPLACE INTO group_members SET group_id = ?d, user_id = ?d, is_tutor = 1", $group_id, $tutor_id);
            }
        } else {
            Database::get()->query("UPDATE group_members SET is_tutor = 0 WHERE group_id = ?d", $group_id);
        }
    }

    // Count number of members
    $numberMembers = @count($_POST['ingroup']);
   
    // Insert new list of members
    if ($maxStudent < $numberMembers and $maxStudent != 0) {
        // More members than max allowed
        $message .= "<div class='alert alert-warning'>$langGroupTooManyMembers</div>";
    } else {
        // Delete all members of this group
        Database::get()->query("DELETE FROM group_members
                                    WHERE group_id = ?d AND is_tutor = 0", $group_id);
        $numberMembers--;

        for ($i = 0; $i <= $numberMembers; $i++) {
            Database::get()->query("INSERT IGNORE INTO group_members (user_id, group_id)
                                      VALUES (?d, ?d)", $_POST['ingroup'][$i], $group_id);
        }
        $message .= "<div class='alert alert-success'>$langGroupSettingsModified</div>";
    }    
    initialize_group_info($group_id);
}

$tool_content_group_name = q($group_name);

if ($is_editor) {
    $tool_content_tutor = "<select name='tutor[]' multiple id='select-tutor' class='form-control'>\n";
    $q = Database::get()->queryArray("SELECT user.id AS user_id, surname, givenname,
                                   user.id IN (SELECT user_id FROM group_members
                                                              WHERE group_id = ?d AND
                                                                    is_tutor = 1) AS is_tutor
                              FROM course_user, user
                              WHERE course_user.user_id = user.id AND
                                    course_user.tutor = 1 AND
                                    course_user.course_id = ?d
                              ORDER BY surname, givenname, user_id", $group_id, $course_id);
    foreach ($q as $row) {
        $selected = $row->is_tutor ? ' selected="selected"' : '';
        $tool_content_tutor .= "<option value='$row->user_id'$selected>" . q($row->surname) .
                ' ' . q($row->givenname) . "</option>\n";
    }
    $tool_content_tutor .= '</select>';
} else {
    $tool_content_tutor = display_user($tutors);
}

$tool_content_max_student = $max_members ? $max_members : '-';
$tool_content_group_description = q($group_description);


if ($multi_reg) {
    // Students registered to the course but not members of this group
    $resultNotMember = Database::get()->queryArray("SELECT u.id, u.surname, u.givenname, u.am
                        FROM user u, course_user cu
                        WHERE cu.course_id = ?d AND
                              cu.user_id = u.id AND
                              u.id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?d) AND
                              cu.status = " . USER_STUDENT . "
                        GROUP BY u.id
                        ORDER BY u.surname, u.givenname", $course_id, $group_id);
} else {
    // Students registered to the course but members of no group
    $resultNotMember = Database::get()->queryArray("SELECT u.id, u.surname, u.givenname, u.am
                        FROM (user u, course_user cu)
                        WHERE cu.course_id = $course_id AND
                              cu.user_id = u.id AND
                              cu.status = " . USER_STUDENT . " AND
                              u.id NOT IN (SELECT user_id FROM group_members, `group`
                                                               WHERE `group`.id = group_members.group_id AND
                                                               `group`.course_id = ?d)
                        GROUP BY u.id
                        ORDER BY u.surname, u.givenname", $course_id);
}

$tool_content_not_Member = '';
foreach ($resultNotMember as $myNotMember) {
    $tool_content_not_Member .= "<option value='$myNotMember->id'>" .
            q("$myNotMember->surname $myNotMember->givenname") . (!empty($myNotMember->am) ? q(" ($myNotMember->am)") : "") . "</option>";
}

$q = Database::get()->queryArray("SELECT user.id, user.surname, user.givenname
               FROM user, group_members
               WHERE group_members.user_id = user.id AND
                     group_members.group_id = ?d AND
                     group_members.is_tutor = 0
               ORDER BY user.surname, user.givenname", $group_id);

$tool_content_group_members = '';
foreach ($q as $member) {
    $tool_content_group_members .= "<option value='$member->id'>" . q("$member->surname $member->givenname") .
            "</option>";
}

if (!empty($message)) {
    $tool_content .= $message;
}

$tool_content .= "<div id='operations_container'>" .
        action_bar(array(
            array('title' => $langGroupThisSpace,
                'url' => "group_space.php?course=$course_code&amp;group_id=$group_id",
                'icon' => 'fa-users',
                'level' => 'primary-label'),
            array('title' => $langAddTutors,
                'url' => "../user/?course=$course_code",
                'icon' => 'fa-folder',
                'level' => 'primary'),
        )) .
        "</div>";

$tool_content .= "<div class='form-wrapper'>
        <form class='form-horizontal' role='form' name='groupedit' method='post' action='" . $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;group_id=$group_id' onsubmit=\"return checkrequired(this,'name');\">
        <fieldset>    
        <div class='form-group'>
            <label class='col-sm-2 control-label'>$langGroupName:</label>
              <div class='col-sm-10'><input type=text name='name' size=40 value='$tool_content_group_name' /></div>
        </div>
        <div class='form-group'>
          <label class='col-sm-2 control-label'>$langDescription $langOptional:</label>
          <div class='col-sm-10'><textarea name='description' rows='2' cols='60'>$tool_content_group_description</textarea></div>
        </div>
        <div class='form-group'>
          <label class='col-sm-2 control-label'>$langMax $langGroupPlacesThis:</label>
          <div class='col-sm-10'><input type=text name='maxStudent' size=2 value='$tool_content_max_student'></div>
        </div>
        <div class='form-group'>
          <label class='col-sm-2 control-label'>$langGroupTutor:</label>
          <div class='col-sm-10'>
             $tool_content_tutor
          </div>
        </div>
        <div class='form-group'>
            <label class='col-sm-2 control-label'>$langGroupMembers:</label>
        <div class='col-sm-10'>
          <table class='table-default'>
          <tr class='title1'>
            <td>$langNoGroupStudents</td>
            <td width='100' class='center'>$langMove</td>
            <td class='right'>$langGroupMembers</td>
          </tr>
          <tr>
            <td>
              <select id='users_box' name='nogroup[]' size='15' multiple>
                $tool_content_not_Member
              </select>
            </td>
            <td class='center'>
              <input type='button' onClick=\"move('users_box','members_box')\" value='   &gt;&gt;   ' /><br /><input type='button' onClick=\"move('members_box','users_box')\" value='   &lt;&lt;   ' />
            </td>
            <td class='right'>
              <select id='members_box' name='ingroup[]' size='15' multiple>
                $tool_content_group_members
              </select>
            </td>
          </tr>
          </table>
      </div>
    </div>
    <div class='col-sm-10 col-sm-offset-3'>      
      <input class='btn btn-primary' type='submit' name='modify' value='$langModify' onClick=\"selectAll('members_box',true)\">
    </div>    
    </fieldset>
    </form>
</div>";

draw($tool_content, 2, null, $head_content);