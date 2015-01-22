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
 * ========================================================================

  ============================================================================
  @Description: Main script for the work tool
  ============================================================================
 */

$require_current_course = true;
$require_login = true;
$require_help = true;
$helpTopic = 'Work';

require_once '../../include/baseTheme.php';
require_once 'include/lib/forcedownload.php';
require_once 'work_functions.php';
require_once 'modules/group/group_functions.php';
require_once 'include/lib/fileUploadLib.inc.php';
require_once 'include/lib/fileManageLib.inc.php';
require_once 'include/sendMail.inc.php';
require_once 'modules/graphics/plotter.php';
require_once 'include/log.php';

// Include autojudge connectors
$connectorFiles = array_diff(scandir('modules/work/connectors'), array('..', '.'));
foreach($connectorFiles as $curFile) {
    require_once('modules/work/connectors/'.$curFile);
}
// End including connectors

// For colorbox, fancybox, shadowbox use
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
ModalBoxHelper::loadModalBox();
/* * ** The following is added for statistics purposes ** */
require_once 'include/action.php';
$action = new action();
$action->record(MODULE_ID_ASSIGN);
/* * *********************************** */


$workPath = $webDir . "/courses/" . $course_code . "/work";
$works_url = array('url' => "$_SERVER[SCRIPT_NAME]?course=$course_code", 'name' => $langWorks);
$toolName = $langWorks;

//-------------------------------------------
// main program
//-------------------------------------------

//Gets the student's assignment file ($file_type=NULL)
//or the teacher's assignment ($file_type=1)
if (isset($_GET['get'])) {
    if (isset($_GET['file_type']) && $_GET['file_type']==1) {
        $file_type = intval($_GET['file_type']);
    } else {
        $file_type = NULL;
    }
    if (!send_file(intval($_GET['get']), $file_type)) {
        Session::Messages($langFileNotFound, 'alert-danger');
    }
}

// Only course admins can download all assignments in a zip file
if ($is_editor) {
    if (isset($_GET['download'])) {
        include 'include/pclzip/pclzip.lib.php';
        $as_id = intval($_GET['download']);
        // Allow unlimited time for creating the archive
        set_time_limit(0);
        if (!download_assignments($as_id)) {
            Session::Messages($langNoAssignmentsExist, 'alert-danger');
            redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$as_id);
        }
    }
}

if ($is_editor) {
    load_js('tools.js');
    global $themeimg, $m;
    $head_content .= "
    <script type='text/javascript'>
    function check_weights() {
        /* function to check weight validity */
        if($('#hidden-opt').is(':visible') && $('#auto_judge').is(':checked')) {
            var weights = document.getElementsByClassName('auto_judge_weight');
            var weight_sum = 0;
            var max_grade = parseFloat(document.getElementById('max_grade').value);
            max_grade = Math.round(max_grade * 1000) / 1000;

            for (i = 0; i < weights.length; i++) {
                // match ints or floats
                w = weights[i].value.match(/^\d+\.\d+$|^\d+$/);
                if(w != null) {
                    w = parseFloat(w);
                    if(w >= 0  && w <= max_grade)  // 0->max_grade allowed
                    {
                        /* allow 3 decimal digits */
                        weight_sum += w;
                        continue;
                    }
                    else{
                        alert('Weights must be between 1 and max_grade!');
                        return false;
                    }
                }
                else {
                    alert('Only numbers as weights!');
                    return false;
                }
            }
            diff = Math.round((max_grade - weight_sum) * 1000) / 1000;
            if (diff >= 0 && diff <= 0.001) {
                return true;
            }
            else {
                alert('Weights do not sum up to ' + max_grade +
                    '!\\n(Remember, 3 decimal digits precision)');
                return false;
            }
        }
        else
            return true;
    }
    function updateWeightsSum() {
        var weights = document.getElementsByClassName('auto_judge_weight');
        var weight_sum = 0;
        var max_grade = parseFloat(document.getElementById('max_grade').value);
        max_grade = Math.round(max_grade * 1000) / 1000;

        for (i = 0; i < weights.length; i++) {
            // match ints or floats
            w = weights[i].value.match(/^\d+\.\d+$|^\d+$/);
            if(w != null) {
                w = parseFloat(w);
                if(w >= 0  && w <= max_grade)  // 0->max_grade allowed
                {
                    /* allow 3 decimal digits */
                    weight_sum += w;
                    continue;
                }
                else{
                    $('#weights-sum').html('-');
                    $('#weights-sum').css('color', 'red');
                    return;
                }
            }
            else {
                $('#weights-sum').html('-');
                $('#weights-sum').css('color', 'red');
                return;
            }
        }
        $('#weights-sum').html(weight_sum);
        diff = Math.round((max_grade - weight_sum) * 1000) / 1000;
        if (diff >= 0 && diff <= 0.001) {
            $('#weights-sum').css('color', 'green');
        } else {
            $('#weights-sum').css('color', 'red');
        }
    }
    $(document).ready(function() {
        updateWeightsSum();
        $('.auto_judge_weight').change(updateWeightsSum);
        $('#max_grade').change(updateWeightsSum);
    });

    $(function() {
        $('input[name=group_submissions]').click(changeAssignLabel);
        $('input[id=assign_button_some]').click(ajaxAssignees);
        $('input[id=assign_button_all]').click(hideAssignees);
        $('input[name=auto_judge]').click(changeAutojudgeScenariosVisibility);
        $(document).ready(function() { changeAutojudgeScenariosVisibility.apply($('input[name=auto_judge]')); });

        function hideAssignees()
        {
            $('#assignees_tbl').addClass('hide');
            $('#assignee_box').find('option').remove();
        }
        function changeAssignLabel()
        {
            var assign_to_specific = $('input:radio[name=assign_to_specific]:checked').val();
            if(assign_to_specific==1){
               ajaxAssignees();
            }
            if (this.id=='group_button') {
               $('#assign_button_all_text').text('$m[WorkToAllGroups]');
               $('#assign_button_some_text').text('$m[WorkToGroup]');
               $('#assignees').text('$langGroups');
            } else {
               $('#assign_button_all_text').text('$m[WorkToAllUsers]');
               $('#assign_button_some_text').text('$m[WorkToUser]');
               $('#assignees').text('$langStudents');
            }
        }
        function ajaxAssignees()
        {
            $('#assignees_tbl').removeClass('hide');
            var type = $('input:radio[name=group_submissions]:checked').val();
            $.post('$works_url[url]',
            {
              assign_type: type
            },
            function(data,status){
                var index;
                var parsed_data = JSON.parse(data);
                var select_content = '';
                if(type==0){
                    for (index = 0; index < parsed_data.length; ++index) {
                        select_content += '<option value=\"' + parsed_data[index]['id'] + '\">' + parsed_data[index]['surname'] + ' ' + parsed_data[index]['givenname'] + '<\/option>';
                    }
                } else {
                    for (index = 0; index < parsed_data.length; ++index) {
                        select_content += '<option value=\"' + parsed_data[index]['id'] + '\">' + parsed_data[index]['name'] + '<\/option>';
                    }
                }
                $('#assignee_box').find('option').remove();
                $('#assign_box').find('option').remove().end().append(select_content);
            });
        }

        function changeAutojudgeScenariosVisibility() {
            if($(this).is(':checked')) {
                $(this).parent().parent().find('table').show();
                $('#lang').parent().parent().show();
            } else {
                $(this).parent().parent().find('table').hide();
                $('#lang').parent().parent().hide();
            }
        }
        $('#autojudge_new_scenario').click(function(e) {
            var rows = $(this).parent().parent().parent().find('tr').size()-1;
            // Clone the first line
            var newLine = $(this).parent().parent().parent().find('tr:first').clone();
            // Replace 0 wth the line number
            newLine.html(newLine.html().replace(/auto_judge_scenarios\[0\]/g, 'auto_judge_scenarios['+rows+']'));
            // Initialize the remove event and show the button
            newLine.find('.autojudge_remove_scenario').show();
            newLine.find('.autojudge_remove_scenario').click(removeRow);
            // Clear out any potential content
            newLine.find('input').val('');
            // Insert it just before the final line
            newLine.insertBefore($(this).parent().parent().parent().find('tr:last'));
            // Add the event handler
            newLine.find('.auto_judge_weight').change(updateWeightsSum);
            e.preventDefault();
            return false;
        });
        // Remove row
        function removeRow(e) {
            $(this).parent().parent().remove();
            e.preventDefault();
            return false;
        }
        $('.autojudge_remove_scenario').click(removeRow);
        $(document).on('change', 'select.auto_judge_assertion', function(e) {
            e.preventDefault();
            var value = $(this).val();

            // Change selected attr.
            $(this).find('option').each(function() {
                if ($(this).attr('selected') == 'selected') {
                    $(this).removeAttr('selected');
                } else if ($(this).attr('value') == value) {
                    $(this).attr('selected', true);
                }
            });
            var row       = $(this).parent().parent();
            var tableBody = $(this).parent().parent().parent();
            var indexNum  = row.index() + 1;

            if (value === 'eq' ||
                value === 'same' ||
                value === 'notEq' ||
                value === 'notSame' ||
                value === 'startsWith' ||
                value === 'endsWith' ||
                value === 'contains'
            ) {
                tableBody.find('tr:nth-child('+indexNum+')').find('input.auto_judge_output').removeAttr('disabled');
            } else {
                tableBody.find('tr:nth-child('+indexNum+')').find('input.auto_judge_output').val('');
                tableBody.find('tr:nth-child('+indexNum+')').find('input.auto_judge_output').attr('disabled', 'disabled');
            }
            return false;
        });
    });

    </script>";

    $email_notify = (isset($_POST['email']) && $_POST['email']);
    if (isset($_POST['grade_comments'])) {
        $work_title = Database::get()->querySingle("SELECT title FROM assignment WHERE id = ?d", intval($_POST['assignment']))->title;
        $pageName = $work_title;
        $navigation[] = $works_url;
        submit_grade_comments($_POST['assignment'], $_POST['submission'], $_POST['grade'], $_POST['comments'], $email_notify, null);
    } elseif (isset($_GET['add'])) {
        $pageName = $langNewAssign;
        $navigation[] = $works_url;
        new_assignment();
    } elseif (isset($_POST['assign_type'])) {
        if ($_POST['assign_type']) {
            $data = Database::get()->queryArray("SELECT name,id FROM `group` WHERE course_id = ?d", $course_id);
        } else {
            $data = Database::get()->queryArray("SELECT user.id AS id, surname, givenname
                                    FROM user, course_user
                                    WHERE user.id = course_user.user_id
                                    AND course_user.course_id = ?d AND course_user.status = 5
                                    AND user.id", $course_id);

        }
        echo json_encode($data);
        exit;
    } elseif (isset($_POST['new_assign'])) {
        add_assignment();
    } elseif (isset($_GET['as_id'])) {
        $as_id = intval($_GET['as_id']);
        $id = intval($_GET['id']);
        if(delete_user_assignment($as_id)){
            Session::Messages($langDeleted, 'alert-success');
        } else {
            Session::Messages($langDelError, 'alert-danger');
        }
        redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$id);
    } elseif (isset($_POST['grades'])) {
        $navigation[] = $works_url;
        submit_grades(intval($_POST['grades_id']), $_POST['grades'], $email_notify);
    } elseif (isset($_REQUEST['id'])) {
        $id = intval($_REQUEST['id']);
        $work_title = q(Database::get()->querySingle("SELECT title FROM assignment WHERE id = ?d", $id)->title);
        $work_id_url = array('url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&id=$id",
            'name' => $work_title);
        if (isset($_POST['on_behalf_of'])) {
            if (isset($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
            } else {
                $user_id = $uid;
            }
            $pageName = $langAddGrade;
            $navigation[] = $works_url;
            $navigation[] = $work_id_url;
            submit_work($id, $user_id);
        } elseif (isset($_REQUEST['choice'])) {
            $choice = $_REQUEST['choice'];
            if ($choice == 'disable') {
                if (Database::get()->query("UPDATE assignment SET active = 0 WHERE id = ?d", $id)->affectedRows > 0) {
                    Session::Messages($langAssignmentDeactivated, 'alert-success');
                }
                redirect_to_home_page('modules/work/index.php?course='.$course_code);
            } elseif ($choice == 'enable') {
                if (Database::get()->query("UPDATE assignment SET active = 1 WHERE id = ?d", $id)->affectedRows > 0) {
                    Session::Messages($langAssignmentActivated, 'alert-success');
                }
                redirect_to_home_page('modules/work/index.php?course='.$course_code);
            } elseif ($choice == 'do_delete') {
                if(delete_assignment($id)) {
                    Session::Messages($langDeleted, 'alert-success');
                } else {
                    Session::Messages($langDelError, 'alert-danger');
                }
                redirect_to_home_page('modules/work/index.php?course='.$course_code);
            } elseif ($choice == 'do_delete_file') {
                if(delete_teacher_assignment_file($id)){
                    Session::Messages($langDelF, 'alert-success');
                } else {
                    Session::Messages($langDelF, 'alert-danger');
                }
                redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$id.'&choice=edit');
            } elseif ($choice == 'do_purge') {
                if (purge_assignment_subs($id)) {
                    Session::Messages($langAssignmentSubsDeleted, 'alert-success');
                }
                redirect_to_home_page('modules/work/index.php?course='.$course_code);
            } elseif ($choice == 'edit') {
                $pageName = $m['WorkEdit'];
                $navigation[] = $works_url;
                $navigation[] = $work_id_url;
                show_edit_assignment($id);
            } elseif ($choice == 'do_edit') {
                $pageName = $langWorks;
                $navigation[] = $works_url;
                $navigation[] = $work_id_url;
                edit_assignment($id);
            } elseif ($choice == 'add') {
                $pageName = $langAddGrade;
                $navigation[] = $works_url;
                $navigation[] = $work_id_url;
                show_submission_form($id, groups_with_no_submissions($id), true);
            } elseif ($choice == 'plain') {
                show_plain_view($id);
            }
        } else {
            $pageName = $work_title;
            $navigation[] = $works_url;
            if (isset($_GET['disp_results'])) {
                show_assignment($id, true);
            } elseif (isset($_GET['disp_non_submitted'])) {
                show_non_submitted($id);
            } else {
                show_assignment($id);
            }
        }
    } else {
        $pageName = $langWorks;
        show_assignments();
    }
} else {
    if (isset($_REQUEST['id'])) {
        $id = intval($_REQUEST['id']);
        if (isset($_POST['work_submit'])) {
            $pageName = $m['SubmissionStatusWorkInfo'];
            $navigation[] = $works_url;
            $navigation[] = array('url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id", 'name' => $langWorks);
            submit_work($id);
        } else {
            $work_title = Database::get()->querySingle("SELECT title FROM assignment WHERE id = ?d", $id)->title;
            $pageName = $work_title;
            $navigation[] = $works_url;
            show_student_assignment($id);
        }
    } else {
        show_student_assignments();
    }
}

add_units_navigation(TRUE);
draw($tool_content, 2, null, $head_content);

//-------------------------------------
// end of main program
//-------------------------------------

// insert the assignment into the database
function add_assignment() {
    global $tool_content, $workPath, $course_id, $uid, $langTheField, $m,
           $course_code, $langFormErrors, $langNewAssignSuccess;

    $v = new Valitron\Validator($_POST);
    $v->rule('required', array('title', 'max_grade'));
    $v->rule('numeric', array('max_grade'));
    $v->labels(array(
        'title' => "$langTheField $m[title]",
        'max_grade' => "$langTheField $m[max_grade]"
    ));
    if($v->validate()) {
        $title = $_POST['title'];
        $desc = $_POST['desc'];
        $deadline = (trim($_POST['WorkEnd'])!=FALSE) ? date('Y-m-d H:i', strtotime($_POST['WorkEnd'])) : '0000-00-00 00:00:00';
        $late_submission = ((isset($_POST['late_submission']) &&  trim($_POST['WorkEnd']!=FALSE)) ? 1 : 0);
        $group_submissions = filter_input(INPUT_POST, 'group_submissions', FILTER_VALIDATE_INT);
        $max_grade = filter_input(INPUT_POST, 'max_grade', FILTER_VALIDATE_FLOAT);
        $assign_to_specific = filter_input(INPUT_POST, 'assign_to_specific', FILTER_VALIDATE_INT);
        $assigned_to = filter_input(INPUT_POST, 'ingroup', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
        $auto_judge = filter_input(INPUT_POST, 'auto_judge', FILTER_VALIDATE_INT);
        $auto_judge_scenarios = serialize($_POST['auto_judge_scenarios']);
        $lang = filter_input(INPUT_POST, 'lang');
        $secret = uniqid('');

        if ($assign_to_specific == 1 && empty($assigned_to)) {
            $assign_to_specific = 0;
        }
        if (@mkdir("$workPath/$secret", 0777) && @mkdir("$workPath/admin_files/$secret", 0777, true)) {
            $id = Database::get()->query("INSERT INTO assignment (course_id, title, description, deadline, late_submission, comments, submission_date, secret_directory, group_submissions, max_grade, assign_to_specific, auto_judge, auto_judge_scenarios, lang) ". "VALUES (?d, ?s, ?s, ?t, ?d, ?s, ?t, ?s, ?d, ?d, ?d, ?d, ?s, ?s)", $course_id, $title, $desc, $deadline, $late_submission, '', date("Y-m-d H:i:s"), $secret, $group_submissions, $max_grade, $assign_to_specific, $auto_judge, $auto_judge_scenarios, $lang)->lastInsertID;
            $secret = work_secret($id);
            if ($id) {
                $local_name = uid_to_name($uid);
                $am = Database::get()->querySingle("SELECT am FROM user WHERE id = ?d", $uid)->am;
                if (!empty($am)) {
                    $local_name .= $am;
                }
                $local_name = greek_to_latin($local_name);
                $local_name = replace_dangerous_char($local_name);
                if (!isset($_FILES) || !$_FILES['userfile']['size']) {
                    $_FILES['userfile']['name'] = '';
                    $_FILES['userfile']['tmp_name'] = '';
                } else {
                    validateUploadedFile($_FILES['userfile']['name'], 2);
                    if (preg_match('/\.(ade|adp|bas|bat|chm|cmd|com|cpl|crt|exe|hlp|hta|' . 'inf|ins|isp|jse|lnk|mdb|mde|msc|msi|msp|mst|pcd|pif|reg|scr|sct|shs|' . 'shb|url|vbe|vbs|wsc|wsf|wsh)$/', $_FILES['userfile']['name'])) {
                        $tool_content .= "<p class=\"caution\">$langUnwantedFiletype: {$_FILES['userfile']['name']}<br />";
                        $tool_content .= "<a href=\"$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id\">$langBack</a></p><br />";
                        return;
                    }
                    $ext = get_file_extension($_FILES['userfile']['name']);
                    $filename = "$secret/$local_name" . (empty($ext) ? '' : '.' . $ext);
                    if (move_uploaded_file($_FILES['userfile']['tmp_name'], "$workPath/admin_files/$filename")) {
                        @chmod("$workPath/admin_files/$filename", 0644);
                        $file_name = $_FILES['userfile']['name'];
                        Database::get()->query("UPDATE assignment SET file_path = ?s, file_name = ?s WHERE id = ?d", $filename, $file_name, $id);
                    }
                }
                if ($assign_to_specific && !empty($assigned_to)) {
                    if ($group_submissions == 1) {
                        $column = 'group_id';
                        $other_column = 'user_id';
                    } else {
                        $column = 'user_id';
                        $other_column = 'group_id';
                    }
                    foreach ($assigned_to as $assignee_id) {
                        Database::get()->query("INSERT INTO assignment_to_specific ({$column}, {$other_column}, assignment_id) VALUES (?d, ?d, ?d)", $assignee_id, 0, $id);
                    }
                }
                Log::record($course_id, MODULE_ID_ASSIGN, LOG_INSERT, array('id' => $id,
                    'title' => $title,
                    'description' => $desc,
                    'deadline' => $deadline,
                    'secret' => $secret,
                    'group' => $group_submissions));
                Session::Messages($langNewAssignSuccess,'alert-success');
                redirect_to_home_page("modules/work/index.php?course=$course_code");
            } else {
                @rmdir("$workPath/$secret");
                die('Error creating directories');
            }
        }
    } else {
        Session::flashPost()->Messages($langFormErrors)->Errors($v->errors());
        redirect_to_home_page("modules/work/index.php?course=$course_code&add=1");
    }
}

function submit_work($id, $on_behalf_of = null) {
    global $tool_content, $workPath, $uid, $course_id, $works_url,
    $langUploadSuccess, $langBack, $langUploadError,
    $langExerciseNotPermit, $langUnwantedFiletype, $langAutoJudgeEmptyFile,
    $langAutoJudgeInvalidFileType, $langAutoJudgeScenariosPassed, $course_code,
    $langOnBehalfOfUserComment, $langOnBehalfOfGroupComment, $course_id;
    $connector = q(get_config('autojudge_connector'));
    $connector = new $connector();
    $langExt = $connector->getSupportedLanguages();

    //checks for submission validity end here
    if (isset($on_behalf_of)) {
        $user_id = $on_behalf_of;
    } else {
        $user_id = $uid;
    }
    $submit_ok = FALSE; // Default do not allow submission
    if (isset($uid) && $uid) { // check if logged-in
        if ($GLOBALS['status'] == 10) { // user is guest
            $submit_ok = FALSE;
        } else { // user NOT guest
            if (isset($_SESSION['courses']) && isset($_SESSION['courses'][$_SESSION['dbname']])) {
                // user is registered to this lesson
                $row = Database::get()->querySingle("SELECT deadline, late_submission, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                              FROM assignment WHERE id = ?d", $id);
                if (($row->time < 0 && (int) $row->deadline && !$row->late_submission) and !$on_behalf_of) {
                    $submit_ok = FALSE; // after assignment deadline
                } else {
                    $submit_ok = TRUE; // before deadline
                }
            } else {
                //user NOT registered to this lesson
                $submit_ok = FALSE;
            }
        }
    } //checks for submission validity end here
    $row = Database::get()->querySingle("SELECT title, group_submissions, auto_judge, auto_judge_scenarios, lang, max_grade FROM assignment WHERE course_id = ?d AND id = ?d", $course_id, $id);
    $title = q($row->title);
    $group_sub = $row->group_submissions;
    $auto_judge = $row->auto_judge;
    $auto_judge_scenarios = ($auto_judge == true) ? unserialize($row->auto_judge_scenarios) : null;
    $lang = $row->lang;
    $max_grade = $row->max_grade;
    $nav[] = $works_url;
    $nav[] = array('url' => "$_SERVER[SCRIPT_NAME]?id=$id", 'name' => $title);

    if ($submit_ok) {
        if ($group_sub) {
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : -1;
            $gids = user_group_info($on_behalf_of ? null : $user_id, $course_id);
            $local_name = isset($gids[$group_id]) ? greek_to_latin($gids[$group_id]) : '';
        } else {
            $group_id = 0;
            $local_name = uid_to_name($user_id);
            $am = Database::get()->querySingle("SELECT am FROM user WHERE id = ?d", $user_id)->am;
            if (!empty($am)) {
                $local_name .= $am;
            }
            $local_name = greek_to_latin($local_name);
        }
        $local_name = replace_dangerous_char($local_name);
        if (isset($on_behalf_of) and
                (!isset($_FILES) or !$_FILES['userfile']['size'])) {
            $_FILES['userfile']['name'] = '';
            $_FILES['userfile']['tmp_name'] = '';
            $no_files = true;
        } else {
            $no_files = false;
        }

        validateUploadedFile($_FILES['userfile']['name'], 2);

        if (preg_match('/\.(ade|adp|bas|bat|chm|cmd|com|cpl|crt|exe|hlp|hta|' . 'inf|ins|isp|jse|lnk|mdb|mde|msc|msi|msp|mst|pcd|pif|reg|scr|sct|shs|' . 'shb|url|vbe|vbs|wsc|wsf|wsh)$/', $_FILES['userfile']['name'])) {
            $tool_content .= "<p class=\"caution\">$langUnwantedFiletype: {$_FILES['userfile']['name']}<br />";
            $tool_content .= "<a href=\"$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id\">$langBack</a></p><br />";
            return;
        }
        $secret = work_secret($id);
        $ext = get_file_extension($_FILES['userfile']['name']);
        $filename = "$secret/$local_name" . (empty($ext) ? '' : '.' . $ext);

        if (!isset($on_behalf_of)) {
            $msg1 = delete_submissions_by_uid($user_id, -1, $id);
            if ($group_sub) {
                if (array_key_exists($group_id, $gids)) {
                    $msg1 = delete_submissions_by_uid(-1, $group_id, $id);
                }
            }
        } else {
            $msg1 = '';
        }
        if ($no_files or move_uploaded_file($_FILES['userfile']['tmp_name'], "$workPath/$filename")) {
            if(file_get_contents("$workPath/$filename") != false){
                if ($no_files) {
                    $filename = '';
                } else {
                    @chmod("$workPath/$filename", 0644);
                }

                $msg2 = $langUploadSuccess;
                $submit_ip = $_SERVER['REMOTE_ADDR'];
                if (isset($on_behalf_of)) {
                    if ($group_sub) {
                        $auto_comments = sprintf($langOnBehalfOfGroupComment, uid_to_name($uid), $gids[$group_id]);
                    } else {
                        $auto_comments = sprintf($langOnBehalfOfUserComment, uid_to_name($uid), uid_to_name($user_id));
                    } //group_sub

                    $stud_comments = $auto_comments;
                    $grade_comments = $_POST['stud_comments'];

                    $grade_valid = filter_input(INPUT_POST, 'grade', FILTER_VALIDATE_FLOAT);
                    (isset($_POST['grade']) && $grade_valid!== false) ? $grade = $grade_valid : $grade = NULL;

                    $grade_ip = $submit_ip;
                } else {
                    $stud_comments = $_POST['stud_comments'];
                    $grade = NULL;
                    $grade_comments = $grade_ip = "";
                }
                if (!$group_sub or array_key_exists($group_id, $gids)) {
                    $file_name = $_FILES['userfile']['name'];
                    $sid = Database::get()->query("INSERT INTO assignment_submit
                                            (uid, assignment_id, submission_date, submission_ip, file_path,
                                             file_name, comments, grade, grade_comments, grade_submission_ip,
                                             grade_submission_date, group_id)
                                             VALUES (?d, ?d, NOW(), ?s, ?s, ?s, ?s, ?f, ?s, ?s, NOW(), ?d)", $user_id, $id, $submit_ip, $filename, $file_name, $stud_comments, $grade, $grade_comments, $grade_ip, $group_id)->lastInsertID;
                    Log::record($course_id, MODULE_ID_ASSIGN, LOG_INSERT, array('id' => $sid,
                        'title' => $title,
                        'assignment_id' => $id,
                        'filepath' => $filename,
                        'filename' => $file_name,
                        'comments' => $stud_comments,
                        'group_id' => $group_id));

                    // update attendance book as well
                    update_attendance_book($id, 'assignment');

                    if ($on_behalf_of and isset($_POST['email'])) {
                        $email_grade = $_POST['grade'];
                        $email_comments = "\n$auto_comments\n\n" . $_POST['stud_comments'];
                        grade_email_notify($id, $sid, $email_grade, $email_comments);
                    }
                }
                $tool_content .= "<div class='alert alert-success'>$msg2<br>$msg1<br><a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id'>$langBack</a></div><br>";
            } else {
                $tool_content .= "<div class='alert alert-danger'>$langAutoJudgeEmptyFile<br><a href='$_SERVER[SCRIPT_NAME]?course=$course_code'>$langBack</a></div><br>";
            }
        } else {
            $tool_content .= "<div class='alert alert-danger'>$langUploadError<br><a href='$_SERVER[SCRIPT_NAME]?course=$course_code'>$langBack</a></div><br>";
        }
        
                // Auto-judge: Send file to hackearth
        if ($auto_judge && $ext === $langExt[$lang]) {
                $content = file_get_contents("$workPath/$filename");
                // Run each scenario and count how many passed
                 $auto_judge_scenarios_output = array(
                    array(
                        'student_output'=> '',
                        'passed'=> 0,
                    )
                );

                $passed = 0;
                $i = 0;
                $partial = 0;
                $errorsComment = '';
                $weight_sum = 0;
                foreach($auto_judge_scenarios as $curScenario) {
                    $input = new AutoJudgeConnectorInput();
                    $input->input = $curScenario['input'];
                    $input->code = $content;
                    $input->lang = $lang;
                    $result = $connector->compile($input);
                    // Check if we have compilation errors.
                    if ($result->compileStatus !== $result::COMPILE_STATUS_OK) {
                        // Write down the error message.
                        $num = $i+1;
                        $errorsComment = $result->compileStatus." ".$result->output."<br />";
                        $auto_judge_scenarios_output[$i]['passed'] = 0;
                    } else {
                        // Get all needed values to run the assertion.
                        $auto_judge_scenarios_output[$i]['student_output'] = $result->output;
                        $scenarioOutputExpectation = trim($curScenario['output']);
                        $scenarionAssertion        = $curScenario['assertion'];
                        // Do it now.
                        $assertionResult = doScenarioAssertion(
                            $scenarionAssertion,
                            $auto_judge_scenarios_output[$i]['student_output'],
                            $scenarioOutputExpectation
                        );
                        // Check if assertion passed.
                        if ($assertionResult) {
                            $passed++;
                            $auto_judge_scenarios_output[$i]['passed'] = 1;
                            $partial += $curScenario['weight'];
                        } else {
                            $num = $i+1;
                            $auto_judge_scenarios_output[$i]['passed'] = 0;
                        }
                    }

                    $weight_sum += $curScenario['weight'];
                    $i++;
                }

                // 3 decimal digits precision
                $grade = round($partial / $weight_sum * $max_grade, 3);
                // allow an error of 0.001
                if($max_grade - $grade <= 0.001)
                    $grade = $max_grade;
                // Add the output as a comment
                $comment = $langAutoJudgeScenariosPassed.': '.$passed.'/'.count($auto_judge_scenarios);
                rtrim($errorsComment, '<br />');
                if ($errorsComment !== '') {
                    $comment .= '<br /><br />'.$errorsComment;
                }
                submit_grade_comments($id, $sid, $grade, $comment, false, $auto_judge_scenarios_output, true);

        } else if ($auto_judge && $ext !== $langExt[$lang]) {
            if($lang == null) { die('Auto Judge is enabled but no language is selected'); }
            if($langExt[$lang] == null) { die('An unsupported language was selected. Perhaps platform-wide auto judge settings have been changed?'); }
            submit_grade_comments($id, $sid, 0, sprintf($langAutoJudgeInvalidFileType, $langExt[$lang], $ext), false, null, true);
        }
        // End Auto-judge
        
    } else { // not submit_ok
        $tool_content .="<div class='alert alert-danger'>$langExerciseNotPermit<br><a href='$_SERVER[SCRIPT_NAME]?course=$course_code'>$langBack</a></div><br>";
    }
}

//  assignment - prof view only
function new_assignment() {
    global $tool_content, $m, $langAdd, $course_code, $course_id;
    global $desc, $language, $head_content, $langCancel, $langMoreOptions, $langLessOptions;
    global $langBack, $langStudents, $langMove, $langWorkFile,
    $langAutoJudgeInputNotSupported, $langAutoJudgeSum, $langAutoJudgeNewScenario,
    $langAutoJudgeEnable, $langAutoJudgeInput, $langAutoJudgeExpectedOutput,
    $langAutoJudgeOperator, $langAutoJudgeWeight, $langAutoJudgeProgrammingLanguage,
    $langAutoJudgeAssertions;

    $connector = q(get_config('autojudge_connector'));
    $connector = new $connector();


    load_js('bootstrap-datetimepicker');
    $head_content .= "<script type='text/javascript'>
        $(function() {
            $('#enddatepicker').datetimepicker({
                format: 'dd-mm-yyyy hh:ii', pickerPosition: 'bottom-left',
                language: '".$language."',
                autoclose: true
                });
            $('#hidden-opt-btn').on('click', function(e) {
                e.preventDefault();
                $('#hidden-opt').collapse('toggle');
            });
            $('#hidden-opt').on('shown.bs.collapse', function () {
                $('#hidden-opt-btn i').removeClass('fa-caret-down').addClass('fa-caret-up');
                var caret = '<i class=\"fa fa-caret-up\"></i>';
                $('#hidden-opt-btn').html('$langLessOptions '+caret);
            })
            $('#hidden-opt').on('hidden.bs.collapse', function () {
                var caret = '<i class=\"fa fa-caret-down\"></i>';
                $('#hidden-opt-btn').html('$langMoreOptions '+caret);
            })
        });
    </script>";
    $workEnd = isset($_POST['WorkEnd']) ? $_POST['WorkEnd'] : "";

    $tool_content .= action_bar(array(
        array('title' => $langBack,
              'level' => 'primary-label',
              'url' => "$_SERVER[PHP_SELF]?course=$course_code",
              'icon' => 'fa-reply')));
    $title_error = Session::getError('title');
    $max_grade_error = Session::getError('max_grade');
    $tool_content .= "
        <div class='row'><div class='col-sm-12'>
        <div class='form-wrapper'>
        <form class='form-horizontal' role='form' enctype='multipart/form-data' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code' onsubmit='return check_weights();'>
        <fieldset>
            <div class='form-group ".($title_error ? "has-error" : "")."'>
                <label for='title' class='col-sm-2 control-label'>$m[title]:</label>
                <div class='col-sm-10'>
                  <input name='title' type='text' class='form-control' required='required' id='title' placeholder='$m[title]'>
                  <span class='help-block'>$title_error</span>
                </div>
            </div>
            <div class='form-group'>
                <label for='desc' class='col-sm-2 control-label'>$m[description]:</label>
                <div class='col-sm-10'>
                " . rich_text_editor('desc', 4, 20, $desc) . "
                </div>
            </div>
            <div class='col-sm-10 col-sm-offset-2 margin-top-fat margin-bottom-fat'>
                <a id='hidden-opt-btn' class='btn btn-success btn-xs' href='#' style='text-decoration:none;'>$langMoreOptions <i class='fa fa-caret-down'></i></a>
            </div>
            <div class='collapse ".(Session::hasErrors() ? "in" : "")."' id='hidden-opt'>
                <div class='form-group'>
                    <label for='userfile' class='col-sm-2 control-label'>$langWorkFile:</label>
                    <div class='col-sm-10'>
                      <input type='file' id='userfile' name='userfile'>
                    </div>
                </div>
                <div class='form-group ".($max_grade_error ? "has-error" : "")."'>
                    <label for='title' class='col-sm-2 control-label'>$m[max_grade]:</label>
                    <div class='col-sm-10'>
                      <input name='max_grade' type='text' class='form-control' id='max_grade' placeholder='$m[max_grade]' value='". ((isset($_POST['max_grade'])) ? $_POST['max_grade'] : "10") ."'>
                      <span class='help-block'>$max_grade_error</span>
                    </div>
                </div>
                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$m[deadline]:</label>
                    <div class='col-sm-10'>
                        <div class='radio'>
                          <label>
                            <input type='radio' name='is_deadline' value='0' ". ((isset($_POST['WorkEnd'])) ? "" : "checked") ." onclick='$(\"#enddatepicker, #late_sub_row\").addClass(\"hide\");$(\"#deadline\").val(\"\");'>
                            $m[no_deadline]
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' name='is_deadline' value='1' ". ((isset($_POST['WorkEnd'])) ? "checked" : "") ." onclick='$(\"#enddatepicker, #late_sub_row\").removeClass(\"hide\")'>
                            $m[with_deadline]
                          </label>
                        </div>
                    </div>
                </div>
                <div class='input-append date form-group ". ((isset($_POST['WorkEnd'])) ? "" : "hide") ."' id='enddatepicker' data-date='$workEnd' data-date-format='dd-mm-yyyy'>
                    <div class='col-xs-8 col-xs-offset-2'>
                        <input class='form-control' name='WorkEnd' id='deadline' type='text' value='$workEnd'>
                    </div>
                    <div class='col-xs-2'>
                        <span class='add-on'><i class='fa fa-times'></i></span>
                        <span class='add-on'><i class='fa fa-calendar'></i></span>
                    </div>
                    <div class='col-xs-10 col-xs-offset-2'>$m[deadline_notif]</div>
                </div>
                <div class='form-group ". ((isset($_POST['WorkEnd'])) ? "" : "hide") ."' id='late_sub_row'>
                    <div class='col-xs-10 col-xs-offset-2'>
                        <div class='checkbox'>
                          <label>
                            <input type='checkbox' name='late_submission' value='1'>
                            $m[late_submission_enable]
                          </label>
                        </div>
                    </div>
                </div>
                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$m[group_or_user]:</label>
                    <div class='col-sm-10'>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='user_button' name='group_submissions' value='0' checked>
                            $m[user_work]
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='group_button' name='group_submissions' value='1'>
                            $m[group_work]
                          </label>
                        </div>
                    </div>
                </div>
                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$m[WorkAssignTo]:</label>
                    <div class='col-sm-10'>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='assign_button_all' name='assign_to_specific' value='0' checked>
                            <span id='assign_button_all_text'>$m[WorkToAllUsers]</span>
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='assign_button_some' name='assign_to_specific' value='1'>
                            <span id='assign_button_some_text'>$m[WorkToUser]</span>
                          </label>
                        </div>
                    </div>
                </div>
                <table id='assignees_tbl' class='table hide'>
                    <tr class='title1'>
                      <td id='assignees'>$langStudents</td>
                      <td class='text-center'>$langMove</td>
                      <td>$m[WorkAssignTo]</td>
                    </tr>
                    <tr>
                      <td>
                        <select id='assign_box' size='15' multiple>
                        </select>
                      </td>
                      <td class='text-center'>
                        <input type='button' onClick=\"move('assign_box','assignee_box')\" value='   &gt;&gt;   ' /><br /><input type='button' onClick=\"move('assignee_box','assign_box')\" value='   &lt;&lt;   ' />
                      </td>
                      <td width='40%'>
                        <select id='assignee_box' name='ingroup[]' size='15' multiple>

                        </select>
                      </td>
                    </tr>
                </table>
                                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$langAutoJudgeEnable:</label>
                    <div class='col-sm-10'>
                        <div class='radio'><input type='checkbox' id='auto_judge' name='auto_judge' value='1' /></div>
                        <table style='display: none;'>
                            <thead>
                                <tr>
                                  <th>$langAutoJudgeInput</th>
                                  <th>$langAutoJudgeOperator</th>
                                  <th>$langAutoJudgeExpectedOutput</th>
                                  <th>$langAutoJudgeWeight</th>
                                  <th>".$m['delete']."</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                  <td><input type='text' name='auto_judge_scenarios[0][input]' ".($connector->supportsInput() ? '' : 'readonly="readonly" placeholder="'.$langAutoJudgeInputNotSupported.'"')." /></td>
                                  <td>
                                    <select name='auto_judge_scenarios[0][assertion]' class='auto_judge_assertion'>
                                        <option value='eq' selected='selected'>".$langAutoJudgeAssertions['eq']."</option>
                                        <option value='same'>".$langAutoJudgeAssertions['same']."</option>
                                        <option value='notEq'>".$langAutoJudgeAssertions['notEq']."</option>
                                        <option value='notSame'>".$langAutoJudgeAssertions['notSame']."</option>
                                        <option value='integer'>".$langAutoJudgeAssertions['integer']."</option>
                                        <option value='float'>".$langAutoJudgeAssertions['float']."</option>
                                        <option value='digit'>".$langAutoJudgeAssertions['digit']."</option>
                                        <option value='boolean'>".$langAutoJudgeAssertions['boolean']."</option>
                                        <option value='notEmpty'>".$langAutoJudgeAssertions['notEmpty']."</option>
                                        <option value='notNull'>".$langAutoJudgeAssertions['notNull']."</option>
                                        <option value='string'>".$langAutoJudgeAssertions['string']."</option>
                                        <option value='startsWith'>".$langAutoJudgeAssertions['startsWith']."</option>
                                        <option value='endsWith'>".$langAutoJudgeAssertions['endsWith']."</option>
                                        <option value='contains'>".$langAutoJudgeAssertions['contains']."</option>
                                        <option value='numeric'>".$langAutoJudgeAssertions['numeric']."</option>
                                        <option value='isArray'>".$langAutoJudgeAssertions['isArray']."</option>
                                        <option value='true'>".$langAutoJudgeAssertions['true']."</option>
                                        <option value='false'>".$langAutoJudgeAssertions['false']."</option>
                                        <option value='isJsonString'>".$langAutoJudgeAssertions['isJsonString']."</option>
                                        <option value='isObject'>".$langAutoJudgeAssertions['isObject']."</option>
                                    </select>
                                  </td>
                                  <td><input type='text' name='auto_judge_scenarios[0][output]' class='auto_judge_output' /></td>
                          <td><input type='text' name='auto_judge_scenarios[0][weight]' class='auto_judge_weight'/></td>
                                  <td><a href='#' class='autojudge_remove_scenario' style='display: none;'>X</a></td>
                                </tr>
                                <tr>
                                    <td> </td>
                                    <td> </td>
                                    <td> </td>
                                    <td style='text-align:center;'> $langAutoJudgeSum: <span id='weights-sum'>0</span></td>
                                    <td> <input type='submit' value='$langAutoJudgeNewScenario' id='autojudge_new_scenario' /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class='form-group'>
                  <label class='col-sm-2 control-label'>$langAutoJudgeProgrammingLanguage:</label>
                  <div class='col-sm-10'>
                    <select id='lang' name='lang'>";
                    foreach($connector->getSupportedLanguages() as $lang => $ext) {
                        $tool_content .= "<option value='$lang'>$lang</option>\n";
                    }
                    $tool_content .= "</select>
                  </div>
                </div>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='submit' class='btn btn-primary' name='new_assign' value='$langAdd' onclick=\"selectAll('assignee_box',true)\" />
                <a href='$_SERVER[SCRIPT_NAME]?course=$course_code' class='btn btn-default'>$langCancel</a>
            </div>
        </fieldset>
        </form></div></div></div>";
}

//form for editing
function show_edit_assignment($id) {

    global $tool_content, $m, $langEdit, $langBack, $course_code, $langCancel,
        $urlAppend, $works_url, $course_id, $head_content, $language,
        $langStudents, $langMove, $langWorkFile, $themeimg, $langDelWarnUserAssignment,
        $langLessOptions, $langMoreOptions, $langAutoJudgeInputNotSupported,
        $langAutoJudgeSum, $langAutoJudgeNewScenario, $langAutoJudgeEnable,
        $langAutoJudgeInput, $langAutoJudgeExpectedOutput, $langAutoJudgeOperator,
        $langAutoJudgeWeight, $langAutoJudgeProgrammingLanguage, $langAutoJudgeAssertions;

    load_js('bootstrap-datetimepicker');
    $head_content .= "<script type='text/javascript'>
        $(function() {
            $('#enddatepicker').datetimepicker({
                format: 'dd-mm-yyyy hh:ii',
                pickerPosition: 'bottom-left', language: '".$language."',
                autoclose: true
            });
            $('#hidden-opt-btn').on('click', function(e) {
                e.preventDefault();
                $('#hidden-opt').collapse('toggle');
            });
            $('#hidden-opt').on('shown.bs.collapse', function () {
                $('#hidden-opt-btn i').removeClass('fa-caret-down').addClass('fa-caret-up');
                var caret = '<i class=\"fa fa-caret-up\"></i>';
                $('#hidden-opt-btn').html('$langLessOptions '+caret);
            })
            $('#hidden-opt').on('hidden.bs.collapse', function () {
                var caret = '<i class=\"fa fa-caret-down\"></i>';
                $('#hidden-opt-btn').html('$langMoreOptions '+caret);
            })
        });
    </script>";

    $row = Database::get()->querySingle("SELECT * FROM assignment WHERE id = ?d", $id);
    if ($row->assign_to_specific) {
        //preparing options in select boxes for assigning to speficic users/groups
        $assignee_options='';
        $unassigned_options='';
        if ($row->group_submissions) {
            $assignees = Database::get()->queryArray("SELECT `group`.id AS id, `group`.name
                                   FROM assignment_to_specific, `group`
                                   WHERE `group`.id = assignment_to_specific.group_id AND assignment_to_specific.assignment_id = ?d", $id);
            $all_groups = Database::get()->queryArray("SELECT name,id FROM `group` WHERE course_id = ?d", $course_id);
            foreach ($assignees as $assignee_row) {
                $assignee_options .= "<option value='".$assignee_row->id."'>".$assignee_row->name."</option>";
            }
            $unassigned = array_udiff($all_groups, $assignees,
              function ($obj_a, $obj_b) {
                return $obj_a->id - $obj_b->id;
              }
            );
            foreach ($unassigned as $unassigned_row) {
                $unassigned_options .= "<option value='$unassigned_row->id'>$unassigned_row->name</option>";
            }
        } else {
            $assignees = Database::get()->queryArray("SELECT user.id AS id, surname, givenname
                                   FROM assignment_to_specific, user
                                   WHERE user.id = assignment_to_specific.user_id AND assignment_to_specific.assignment_id = ?d", $id);
            $all_users = Database::get()->queryArray("SELECT user.id AS id, user.givenname, user.surname
                                    FROM user, course_user
                                    WHERE user.id = course_user.user_id
                                    AND course_user.course_id = ?d AND course_user.status = 5
                                    AND user.id", $course_id);
            foreach ($assignees as $assignee_row) {
                $assignee_options .= "<option value='$assignee_row->id'>$assignee_row->surname $assignee_row->givenname</option>";
            }
            $unassigned = array_udiff($all_users, $assignees,
              function ($obj_a, $obj_b) {
                return $obj_a->id - $obj_b->id;
              }
            );
            foreach ($unassigned as $unassigned_row) {
                $unassigned_options .= "<option value='$unassigned_row->id'>$unassigned_row->surname $unassigned_row->givenname</option>";
            }
        }
    }
    if ((int)$row->deadline) {
        $deadline = date('d-m-Y H:i',strtotime($row->deadline));
    } else {
        $deadline = '';
    }
    $comments = trim($row->comments);
    $tool_content .= action_bar(array(
        array('title' => $langBack,
              'level' => 'primary-label',
              'url' => "$_SERVER[PHP_SELF]?course=$course_code",
              'icon' => 'fa-reply')));

    //Get possible validation errors
    $title_error = Session::getError('title');
    $max_grade_error = Session::getError('max_grade');

    $tool_content .= "
    <div class='form-wrapper'>
    <form class='form-horizontal' role='form' enctype='multipart/form-data' action='$_SERVER[SCRIPT_NAME]?course=$course_code' method='post' onsubmit='return check_weights();'>
    <input type='hidden' name='id' value='$id' />
    <input type='hidden' name='choice' value='do_edit' />
    <fieldset>
            <div class='form-group ".($title_error ? "has-error" : "")."'>
                <label for='title' class='col-sm-2 control-label'>$m[title]:</label>
                <div class='col-sm-10'>
                  <input name='title' type='text' class='form-control' id='title' value='".q($row->title)."' placeholder='$m[title]'>
                  <span class='help-block'>$title_error</span>
                </div>
            </div>
            <div class='form-group'>
                <label for='desc' class='col-sm-2 control-label'>$m[description]:</label>
                <div class='col-sm-10'>
                " . rich_text_editor('desc', 4, 20, $row->description) . "
                </div>
            </div>";
    if (!empty($comments)) {
    $tool_content .= "<div class='form-group'>
                <label for='desc' class='col-sm-2 control-label'>$m[comments]:</label>
                <div class='col-sm-10'>
                " . rich_text_editor('comments', 5, 65, $comments) . "
                </div>
            </div>";
    }

    $tool_content .= "
            <div class='col-sm-10 col-sm-offset-2 margin-top-fat margin-bottom-fat'>
                <a id='hidden-opt-btn' class='btn btn-success btn-xs' href='#' style='text-decoration:none;'>$langMoreOptions <i class='fa fa-caret-down'></i></a>
            </div>
            <div class='collapse ".(Session::hasErrors() ? "in" : "")."' id='hidden-opt'>
                <div class='form-group'>
                    <label for='userfile' class='col-sm-2 control-label'>$langWorkFile:</label>
                    <div class='col-sm-10'>
                      ".(($row->file_name)? "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;get=$row->id&amp;file_type=1'>".q($row->file_name)."</a>"
                . "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id&amp;choice=do_delete_file' onClick='return confirmation(\"$m[WorkDeleteAssignmentFileConfirm]\");'>
                                     <img src='$themeimg/delete.png' title='$m[WorkDeleteAssignmentFile]' /></a>" : "<input type='file' id='userfile' name='userfile' />")."
                    </div>
                </div>
                <div class='form-group ".($max_grade_error ? "has-error" : "")."'>
                    <label for='max_grade' class='col-sm-2 control-label'>$m[max_grade]:</label>
                    <div class='col-sm-10'>
                        <input name='max_grade' type='text' class='form-control' id='max_grade' value='$row->max_grade' placeholder='$m[max_grade]'>
                        <span class='help-block'>$max_grade_error</span>
                    </div>
                </div>
                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$m[deadline]:</label>
                    <div class='col-sm-10'>
                        <div class='radio'>
                          <label>
                            <input type='radio' name='is_deadline' value='0' ". ((!empty($deadline)) ? "" : "checked") ." onclick='$(\"#enddatepicker, #late_sub_row\").addClass(\"hide\");$(\"#deadline\").val(\"\");'>
                            $m[no_deadline]
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' name='is_deadline' value='1' ". ((!empty($deadline)) ? "checked" : "") ." onclick='$(\"#enddatepicker, #late_sub_row\").removeClass(\"hide\")'>
                            $m[with_deadline]
                          </label>
                        </div>
                    </div>
                </div>
                <div class='input-append date form-group ". (!empty($deadline) ? "" : "hide") ."' id='enddatepicker' data-date='$deadline' data-date-format='dd-mm-yyyy'>
                    <div class='col-xs-8 col-xs-offset-2'>
                        <input class='form-control' name='WorkEnd' id='deadline' type='text' value='$deadline'>
                    </div>
                    <div class='col-xs-2'>
                        <span class='add-on'><i class='fa fa-times'></i></span>
                        <span class='add-on'><i class='fa fa-calendar'></i></span>
                    </div>
                    <div class='col-xs-10 col-xs-offset-2'>$m[deadline_notif]</div>
                </div>
                <div class='form-group ". (!empty($deadline) ? "" : "hide") ."' id='late_sub_row'>
                    <div class='col-xs-10 col-xs-offset-2'>
                        <div class='checkbox'>
                          <label>
                            <input type='checkbox' name='late_submission' value='1' ".(($row->late_submission)? 'checked' : '').">
                            $m[late_submission_enable]
                          </label>
                        </div>
                    </div>
                </div>
                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$m[group_or_user]:</label>
                    <div class='col-sm-10'>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='user_button' name='group_submissions' value='0' ".(($row->group_submissions==1) ? '' : 'checked').">
                            $m[user_work]
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='group_button' name='group_submissions' value='1' ".(($row->group_submissions==1) ? 'checked' : '').">
                            $m[group_work]
                          </label>
                        </div>
                    </div>
                </div>
                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$m[WorkAssignTo]:</label>
                    <div class='col-sm-10'>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='assign_button_all' name='assign_to_specific' value='0' ".(($row->assign_to_specific==1) ? '' : 'checked').">
                            <span id='assign_button_all_text'>$m[WorkToAllUsers]</span>
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='assign_button_some' name='assign_to_specific' value='1' ".(($row->assign_to_specific==1) ? 'checked' : '').">
                            <span id='assign_button_some_text'>$m[WorkToUser]</span>
                          </label>
                        </div>
                    </div>
                </div>
                <table id='assignees_tbl' class='table ".(($row->assign_to_specific==1) ? '' : 'hide')."'>
                <tr class='title1'>
                  <td id='assignees'>$langStudents</td>
                  <td class='text-center'>$langMove</td>
                  <td>$m[WorkAssignTo]</td>
                </tr>
                <tr>
                  <td>
                    <select id='assign_box' size='15' multiple>
                    ".((isset($unassigned_options)) ? $unassigned_options : '')."
                    </select>
                  </td>
                  <td class='text-center'>
                    <input type='button' onClick=\"move('assign_box','assignee_box')\" value='   &gt;&gt;   ' /><br /><input type='button' onClick=\"move('assignee_box','assign_box')\" value='   &lt;&lt;   ' />
                  </td>
                  <td width='40%'>
                    <select id='assignee_box' name='ingroup[]' size='15' multiple>
                    ".((isset($assignee_options)) ? $assignee_options : '')."
                    </select>
                  </td>
                </tr>
                </table>";
                $auto_judge = $row->auto_judge;
                $lang = $row->lang;
                $tool_content .= "
                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$langAutoJudgeEnable:</label>
                    <div class='col-sm-10'>
                        <div class='radio'><input type='checkbox' id='auto_judge' name='auto_judge' value='1' ".($auto_judge == true ? "checked='1'" : '')." /></div>
                        <table>
                            <thead>
                                <tr>
                                    <th>$langAutoJudgeInput</th>
                                    <th>$langAutoJudgeOperator</th>
                                    <th>$langAutoJudgeExpectedOutput</th>
                                    <th>$langAutoJudgeWeight</th>
                                    <th>".$m['delete']."</th>
                                </tr>
                            </thead>
                            <tbody>";
                            $auto_judge_scenarios = $auto_judge == true ? unserialize($row->auto_judge_scenarios) : null;
                            $connector = q(get_config('autojudge_connector'));
                            $connector = new $connector();
                            $rows    = 0;
                            $display = 'visible';
                            if ($auto_judge_scenarios != null) {
                                $scenariosCount = count($auto_judge_scenarios);
                                foreach ($auto_judge_scenarios as $aajudge) {
                                    $tool_content .=
                                    "<tr>
                                        <td><input type='text' value='".htmlspecialchars($aajudge['input'], ENT_QUOTES)."' name='auto_judge_scenarios[$rows][input]' ".($connector->supportsInput() ? '' : 'readonly="readonly" placeholder="'.$langAutoJudgeInputNotSupported.'"')." /></td>";

                                    $tool_content .=
                                    "<td>
                                        <select name='auto_judge_scenarios[$rows][assertion]' class='auto_judge_assertion'>
                                            <option value='eq'"; if ($aajudge['assertion'] === 'eq') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['eq']."</option>
                                            <option value='same'"; if ($aajudge['assertion'] === 'same') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['same']."</option>
                                            <option value='notEq'"; if ($aajudge['assertion'] === 'notEq') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['notEq']."</option>
                                            <option value='notSame'"; if ($aajudge['assertion'] === 'notSame') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['notSame']."</option>
                                            <option value='integer'"; if ($aajudge['assertion'] === 'integer') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['integer']."</option>
                                            <option value='float'"; if ($aajudge['assertion'] === 'float') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['float']."</option>
                                            <option value='digit'"; if ($aajudge['assertion'] === 'digit') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['digit']."</option>
                                            <option value='boolean'"; if ($aajudge['assertion'] === 'boolean') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['boolean']."</option>
                                            <option value='notEmpty'"; if ($aajudge['assertion'] === 'notEmpty') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['notEmpty']."</option>
                                            <option value='notNull'"; if ($aajudge['assertion'] === 'notNull') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['notNull']."</option>
                                            <option value='string'"; if ($aajudge['assertion'] === 'string') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['string']."</option>
                                            <option value='startsWith'"; if ($aajudge['assertion'] === 'startsWith') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['startsWith']."</option>
                                            <option value='endsWith'"; if ($aajudge['assertion'] === 'endsWith') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['endsWith']."</option>
                                            <option value='contains'"; if ($aajudge['assertion'] === 'contains') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['contains']."</option>
                                            <option value='numeric'"; if ($aajudge['assertion'] === 'numeric') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['numeric']."</option>
                                            <option value='isArray'"; if ($aajudge['assertion'] === 'isArray') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['isArray']."</option>
                                            <option value='true'"; if ($aajudge['assertion'] === 'true') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['true']."</option>
                                            <option value='false'"; if ($aajudge['assertion'] === 'false') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['false']."</option>
                                            <option value='isJsonString'"; if ($aajudge['assertion'] === 'isJsonString') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['isJsonString']."</option>
                                            <option value='isObject'"; if ($aajudge['assertion'] === 'isObject') {$tool_content .= " selected='selected'";} $tool_content .=">".$langAutoJudgeAssertions['isObject']."</option>
                                        </select>
                                    </td>";

                                    if (isset($aajudge['output'])) {
                                        $tool_content .= "<td><input type='text' value='".htmlspecialchars($aajudge['output'], ENT_QUOTES)."' name='auto_judge_scenarios[$rows][output]' class='auto_judge_output' /></td>";
                                    } else {
                                        $tool_content .= "<td><input type='text' value='' name='auto_judge_scenarios[$rows][output]' disabled='disabled' class='auto_judge_output' /></td>";
                                    }

                                    $tool_content .=
                                        "<td><input type='text' value='$aajudge[weight]' name='auto_judge_scenarios[$rows][weight]' class='auto_judge_weight'/></td>
                                        <td><a href='#' class='autojudge_remove_scenario' style='display: ".($rows <= 0 ? 'none': 'visible').";'>X</a></td>
                                    </tr>";

                                    $rows++;
                                }
                            } else {
                                $tool_content .= "<tr>
                                            <td><input type='text' name='auto_judge_scenarios[$rows][input]' /></td>
                                            <td>
                                                <select name='auto_judge_scenarios[$rows][assertion]' class='auto_judge_assertion'>
                                                    <option value='eq' selected='selected'>".$langAutoJudgeAssertions['eq']."</option>
                                                    <option value='same'>".$langAutoJudgeAssertions['same']."</option>
                                                    <option value='notEq'>".$langAutoJudgeAssertions['notEq']."</option>
                                                    <option value='notSame'>".$langAutoJudgeAssertions['notSame']."</option>
                                                    <option value='integer'>".$langAutoJudgeAssertions['integer']."</option>
                                                    <option value='float'>".$langAutoJudgeAssertions['float']."</option>
                                                    <option value='digit'>".$langAutoJudgeAssertions['digit']."</option>
                                                    <option value='boolean'>".$langAutoJudgeAssertions['boolean']."</option>
                                                    <option value='notEmpty'>".$langAutoJudgeAssertions['notEmpty']."</option>
                                                    <option value='notNull'>".$langAutoJudgeAssertions['notNull']."</option>
                                                    <option value='string'>".$langAutoJudgeAssertions['string']."</option>
                                                    <option value='startsWith'>".$langAutoJudgeAssertions['startsWith']."</option>
                                                    <option value='endsWith'>".$langAutoJudgeAssertions['endsWith']."</option>
                                                    <option value='contains'>".$langAutoJudgeAssertions['contains']."</option>
                                                    <option value='numeric'>".$langAutoJudgeAssertions['numeric']."</option>
                                                    <option value='isArray'>".$langAutoJudgeAssertions['isArray']."</option>
                                                    <option value='true'>".$langAutoJudgeAssertions['true']."</option>
                                                    <option value='false'>".$langAutoJudgeAssertions['false']."</option>
                                                    <option value='isJsonString'>".$langAutoJudgeAssertions['isJsonString']."</option>
                                                    <option value='isObject'>".$langAutoJudgeAssertions['isObject']."</option>
                                                </select>
                                            </td>
                                            <td><input type='text' name='auto_judge_scenarios[$rows][output]' class='auto_judge_output' /></td>
                                            <td><input type='text' name='auto_judge_scenarios[$rows][weight]' class='auto_judge_weight'/></td>
                                            <td><a href='#' class='autojudge_remove_scenario' style='display: none;'>X</a></td>
                                        </tr>
                                ";
                            }
                            $tool_content .=
                            "<tr>
                                <td> </td>
                                <td> </td>
                                <td> </td>
                                <td style='text-align:center;'> $langAutoJudgeSum: <span id='weights-sum'>0</span></td>
                                <td> <input type='submit' value='$langAutoJudgeNewScenario' id='autojudge_new_scenario' /></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class='form-group'>
                  <label class='col-sm-2 control-label'>$langAutoJudgeProgrammingLanguage:</label>
                  <div class='col-sm-10'>
                    <select id='lang' name='lang'>";
                    foreach($connector->getSupportedLanguages() as $llang => $ext) {
                        $tool_content .= "<option value='$llang' ".($llang === $lang ? "selected='selected'" : "").">$llang</option>\n";
                    }
                    $tool_content .= "</select>
                  </div>
                </div>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='submit' class='btn btn-primary' name='do_edit' value='$langEdit' onclick=\"selectAll('assignee_box',true)\" />
                <a href='$_SERVER[SCRIPT_NAME]?course=$course_code' class='btn btn-default'>$langCancel</a>
            </div>
    </fieldset>
    </form></div>";
}

// edit assignment
function edit_assignment($id) {

    global $tool_content, $langBackAssignment, $langEditSuccess, $m, $langTheField,
    $langEditError, $course_code, $works_url, $course_id, $uid, $workPath, $langFormErrors;

    $v = new Valitron\Validator($_POST);
    $v->rule('required', array('title', 'max_grade'));
    $v->rule('numeric', array('max_grade'));
    $v->labels(array(
        'title' => "$langTheField $m[title]",
        'max_grade' => "$langTheField $m[max_grade]"
    ));
    if($v->validate()) {
        $row = Database::get()->querySingle("SELECT * FROM assignment WHERE id = ?d", $id);
        $title                = $_POST['title'];
        $desc                 = purify($_POST['desc']);
        $deadline             = trim($_POST['WorkEnd']) == FALSE ? '0000-00-00 00:00': date('Y-m-d H:i', strtotime($_POST['WorkEnd']));
        $late_submission      = ((isset($_POST['late_submission']) && trim($_POST['WorkEnd']) != FALSE) ? 1 : 0);
        $group_submissions    = $_POST['group_submissions'];
        $max_grade            = filter_input(INPUT_POST, 'max_grade', FILTER_VALIDATE_FLOAT);
        $assign_to_specific   = filter_input(INPUT_POST, 'assign_to_specific', FILTER_VALIDATE_INT);
        $assigned_to          = filter_input(INPUT_POST, 'ingroup', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
        $auto_judge           = filter_input(INPUT_POST, 'auto_judge', FILTER_VALIDATE_INT);
        $auto_judge_scenarios = serialize($_POST['auto_judge_scenarios']);
        $lang                 = filter_input(INPUT_POST, 'lang');

        if ($assign_to_specific == 1 && empty($assigned_to)) {
             $assign_to_specific = 0;
         }

         if (!isset($_POST['comments'])) {
             $comments = '';
         } else {
             $comments = purify($_POST['comments']);
         }

         if (!isset($_FILES) || !$_FILES['userfile']['size']) {
             $_FILES['userfile']['name'] = '';
             $_FILES['userfile']['tmp_name'] = '';
             $filename = $row->file_path;
             $file_name = $row->file_name;
         } else {
             validateUploadedFile($_FILES['userfile']['name'], 2);
             if (preg_match('/\.(ade|adp|bas|bat|chm|cmd|com|cpl|crt|exe|hlp|hta|' .
                                'inf|ins|isp|jse|lnk|mdb|mde|msc|msi|msp|mst|pcd|pif|reg|scr|sct|shs|' .
                                'shb|url|vbe|vbs|wsc|wsf|wsh)$/', $_FILES['userfile']['name'])) {
                 $tool_content .= "<p class=\"caution\">$langUnwantedFiletype: {$_FILES['userfile']['name']}<br />";
                 $tool_content .= "<a href=\"$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id\">$langBack</a></p><br />";
                 return;
             }
             $local_name = uid_to_name($uid);
             $am = Database::get()->querySingle("SELECT am FROM user WHERE id = ?d", $uid)->am;
             if (!empty($am)) {
                 $local_name .= $am;
             }
             $local_name = greek_to_latin($local_name);
             $local_name = replace_dangerous_char($local_name);
             $secret = $row->secret_directory;
             $ext = get_file_extension($_FILES['userfile']['name']);
             $filename = "$secret/$local_name" . (empty($ext) ? '' : '.' . $ext);
             if (move_uploaded_file($_FILES['userfile']['tmp_name'], "$workPath/admin_files/$filename")) {
                 @chmod("$workPath/admin_files/$filename", 0644);
                 $file_name = $_FILES['userfile']['name'];
             }
         }
         Database::get()->query("UPDATE assignment SET title = ?s, description = ?s, deadline = ?t, late_submission = ?d, comments = ?s,
                                group_submissions = ?d, max_grade = ?d, assign_to_specific = ?d, file_path = ?s, file_name = ?s,
                                auto_judge = ?d, auto_judge_scenarios = ?s, lang = ?s WHERE course_id = ?d AND id = ?d",
                                $title, $desc, $deadline, $late_submission, $comments, $group_submissions, $max_grade, $assign_to_specific,
                                $filename, $file_name, $auto_judge, $auto_judge_scenarios, $lang, $course_id, $id);

         Database::get()->query("DELETE FROM assignment_to_specific WHERE assignment_id = ?d", $id);

         if ($assign_to_specific && !empty($assigned_to)) {
             if ($group_submissions == 1) {
                 $column = 'group_id';
                 $other_column = 'user_id';
             } else {
                 $column = 'user_id';
                 $other_column = 'group_id';
             }
             foreach ($assigned_to as $assignee_id) {
                 Database::get()->query("INSERT INTO assignment_to_specific ({$column}, {$other_column}, assignment_id) VALUES (?d, ?d, ?d)", $assignee_id, 0, $id);
             }
         }
         Log::record($course_id, MODULE_ID_ASSIGN, LOG_MODIFY, array('id' => $id,
                 'title' => $title,
                 'description' => $desc,
                 'deadline' => $deadline,
                 'group' => $group_submissions));   \

        Session::Messages($langEditSuccess,'alert-success');
        redirect_to_home_page("modules/work/index.php?course=$course_code");
    } else {
//        $new_or_modify = isset($_GET['NewExercise']) ? "&NewExercise=Yes" : "&exerciseId=$_GET[exerciseId]&modifyExercise=yes";
        Session::flashPost()->Messages($langFormErrors)->Errors($v->errors());
        redirect_to_home_page("modules/work/index.php?course=$course_code&id=$id&choice=edit");
    }
}

/**
 * @brief delete assignment
 * @global type $tool_content
 * @global string $workPath
 * @global type $course_code
 * @global type $webDir
 * @global type $langBack
 * @global type $langDeleted
 * @global type $course_id
 * @param type $id
 */
function delete_assignment($id) {

    global $tool_content, $workPath, $course_code, $webDir, $langBack, $langDeleted, $course_id;

    $secret = work_secret($id);
    $row = Database::get()->querySingle("SELECT title,assign_to_specific FROM assignment WHERE course_id = ?d
                                        AND id = ?d", $course_id, $id);
    if (count($row) > 0) {
        if (Database::get()->query("DELETE FROM assignment WHERE course_id = ?d AND id = ?d", $course_id, $id)->affectedRows > 0){
            Database::get()->query("DELETE FROM assignment_submit WHERE assignment_id = ?d", $id);
            if ($row->assign_to_specific) {
                Database::get()->query("DELETE FROM assignment_to_specific WHERE assignment_id = ?d", $id);
            }
            move_dir("$workPath/$secret", "$webDir/courses/garbage/${course_code}_work_${id}_$secret");

            Log::record($course_id, MODULE_ID_ASSIGN, LOG_DELETE, array('id' => $id,
                'title' => $row->title));
            return true;
        }
        return false;
    }
    return false;
}
/**
 * @brief delete assignment's submissions
 * @global type $tool_content
 * @global string $workPath
 * @global type $course_code
 * @global type $webDir
 * @global type $langBack
 * @global type $langDeleted
 * @global type $course_id
 * @param type $id
 */
function purge_assignment_subs($id) {

	global $tool_content, $workPath, $webDir, $langBack, $langDeleted, $langAssignmentSubsDeleted, $course_code, $course_id;

	$secret = work_secret($id);
        $row = Database::get()->querySingle("SELECT title,assign_to_specific FROM assignment WHERE course_id = ?d
                                        AND id = ?d", $course_id, $id);
        if (Database::get()->query("DELETE FROM assignment_submit WHERE assignment_id = ?d", $id)->affectedRows > 0) {
            if ($row->assign_to_specific) {
                Database::get()->query("DELETE FROM assignment_to_specific WHERE assignment_id = ?d", $id);
            }
            move_dir("$workPath/$secret",
            "$webDir/courses/garbage/${course_code}_work_${id}_$secret");
            return true;
        }
        return false;
}
/**
 * @brief delete user assignment
 * @global string $tool_content
 * @global type $course_id
 * @global type $course_code
 * @global type $webDir
 * @param type $id
 */
function delete_user_assignment($id) {
    global $tool_content, $course_code, $webDir;

    $filename = Database::get()->querySingle("SELECT file_path FROM assignment_submit WHERE id = ?d", $id);
    if (Database::get()->query("DELETE FROM assignment_submit WHERE id = ?d", $id)->affectedRows > 0) {
        if ($filename->file_path) {
            $file = $webDir . "/courses/" . $course_code . "/work/" . $filename->file_path;
            if (!my_delete($file)) {
                return false;
            }
        }
        return true;
    }
    return false;
}
/**
 * @brief delete teacher assignment file
 * @global string $tool_content
 * @global type $course_id
 * @global type $course_code
 * @global type $webDir
 * @param type $id
 */
function delete_teacher_assignment_file($id) {
    global $tool_content, $course_code, $webDir;

    $filename = Database::get()->querySingle("SELECT file_path FROM assignment WHERE id = ?d", $id);
    $file = $webDir . "/courses/" . $course_code . "/work/admin_files/" . $filename->file_path;
    if (Database::get()->query("UPDATE assignment SET file_path='', file_name='' WHERE id = ?d", $id)->affectedRows > 0) {
        if (my_delete($file)) {
            return true;
        }
        return false;
    }
}
/**
 * @brief display user assignment
 * @global type $tool_content
 * @global type $m
 * @global type $uid
 * @global type $langUserOnly
 * @global type $langBack
 * @global type $course_code
 * @global type $course_id
 * @global type $course_code
 * @param type $id
 */
function show_student_assignment($id) {
    global $tool_content, $m, $uid, $langUserOnly, $langBack,
    $course_code, $course_id, $course_code;

    $user_group_info = user_group_info($uid, $course_id);
    $row = Database::get()->querySingle("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                         FROM assignment WHERE course_id = ?d AND id = ?d", $course_id, $id);
    $tool_content .= action_bar(array(
       array(
           'title' => $langBack,
           'icon' => 'fa-reply',
           'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code",
           'level' => "primary-label"
       )
    ));
    assignment_details($id, $row);

    $submit_ok = ($row->time > 0 || !(int) $row->deadline || $row->time <= 0 && $row->late_submission);

    if (!$uid) {
        $tool_content .= "<p>$langUserOnly</p>";
        $submit_ok = FALSE;
    } elseif ($GLOBALS['status'] == 10) {
        $tool_content .= "\n  <div class='alert alert-warning'>$m[noguest]</div>";
        $submit_ok = FALSE;;
    } else {
        foreach (find_submissions($row->group_submissions, $uid, $id, $user_group_info) as $sub) {
            if ($sub->grade != '') {
                $submit_ok = false;

            }
            show_submission_details($sub->id);
        }
    }
    if ($submit_ok) {
        show_submission_form($id, $user_group_info);
    }
}

function show_submission_form($id, $user_group_info, $on_behalf_of = false) {
    global $tool_content, $m, $langWorkFile, $langSendFile, $langSubmit, $uid, $langNotice3, $gid, $is_member,
    $urlAppend, $langGroupSpaceLink, $langOnBehalfOf, $course_code, $langBack, $is_editor, $langCancel;

    $group_select_hidden_input = $group_select_form = '';
    $is_group_assignment = is_group_assignment($id);
    if ($is_group_assignment) {
        if (!$on_behalf_of) {
            if (count($user_group_info) == 1) {
                $gids = array_keys($user_group_info);
                $group_link = $urlAppend . '/modules/group/document.php?gid=' . $gids[0];
                $group_select_hidden_input = "<input type='hidden' name='group_id' value='$gids[0]' />";
            } elseif ($user_group_info) {
                $group_select_form = "
                        <div class='form-group'>
                            <label for='userfile' class='col-sm-2 control-label'>$langGroupSpaceLink:</label>
                            <div class='col-sm-10'>
                              " . selection($user_group_info, 'group_id') . "
                            </div>
                        </div>";
            } else {
                $group_link = $urlAppend . 'modules/group/';
                $tool_content .= "<div class='alert alert-warning'>$m[this_is_group_assignment] <br />" .
                        sprintf(count($user_group_info) ?
                                        $m['group_assignment_publish'] :
                                        $m['group_assignment_no_groups'], $group_link) .
                        "</p>\n";
            }
        } else {
            $groups_with_no_submissions = groups_with_no_submissions($id);
            if (count($groups_with_no_submissions)>0) {
                $group_select_form = "
                        <div class='form-group'>
                            <label for='userfile' class='col-sm-2 control-label'>$langGroupSpaceLink:</label>
                            <div class='col-sm-10'>
                              " . selection($groups_with_no_submissions, 'group_id') . "
                            </div>
                        </div>";
            }else{
                Session::Messages($m['NoneWorkGroupNoSubmission'], 'alert-danger');
                redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$id);
            }
        }
    } elseif ($on_behalf_of) {
            $users_with_no_submissions = users_with_no_submissions($id);
            if (count($users_with_no_submissions)>0) {
                $group_select_form = "
                        <div class='form-group'>
                            <label for='userfile' class='col-sm-2 control-label'>$langOnBehalfOf:</label>
                            <div class='col-sm-10'>
                              " .selection($users_with_no_submissions, 'user_id', '', "class='form-control'") . "
                            </div>
                        </div>";
            } else {
                Session::Messages($m['NoneWorkUserNoSubmission'], 'alert-danger');
                redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$id);
            }
    }
    $notice = $on_behalf_of ? '' : "<div class='alert alert-info'>".icon('fa-info-circle')." $langNotice3</div>";
    $extra = $on_behalf_of ? "
                        <div class='form-group'>
                            <label for='userfile' class='col-sm-2 control-label'>$m[grade]:</label>
                            <div class='col-sm-10'>
                              <input class='form-control' type='text' name='grade' maxlength='3' size='3'> ($m[max_grade]:)
                              <input type='hidden' name='on_behalf_of' value='1'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <div class='col-sm-10 col-sm-offset-2'>
                                <div class='checkbox'>
                                  <label>
                                    <input type='checkbox' name='email' id='email_button' value='1'>
                                    $m[email_users]
                                  </label>
                                </div>
                            </div>
                        </div>" : '';
    if (!$is_group_assignment || count($user_group_info) || $on_behalf_of) {
        $back_link = $is_editor ? "index.php?course=$course_code&id=$id" : "index.php?course=$course_code";
        $tool_content .= action_bar(array(
                array(
                    'title' => $langBack,
                    'icon' => 'fa-reply',
                    'level' => 'primary-label',
                    'url' => "index.php?course=$course_code&id=$id",
                    'show' => $is_editor
                )
            ))."
                    $notice
                    <div class='form-wrapper'>
                     <form class='form-horizontal' role='form' enctype='multipart/form-data' action='$_SERVER[SCRIPT_NAME]?course=$course_code' method='post'>
                        <input type='hidden' name='id' value='$id' />$group_select_hidden_input
                        <fieldset>
                        $group_select_form
                        <div class='form-group'>
                            <label for='userfile' class='col-sm-2 control-label'>$langWorkFile:</label>
                            <div class='col-sm-10'>
                              <input type='file'  name='userfile' id='userfile'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='stud_comments' class='col-sm-2 control-label'>$m[comments]:</label>
                            <div class='col-sm-10'>
                              <textarea class='form-control' name='stud_comments' id='stud_comments' rows='5'></textarea>
                            </div>
                        </div>
                        $extra
                        <div class='form-group'>
                            <div class='col-sm-10 col-sm-offset-2'>
                                <input class='btn btn-primary' type='submit' value='$langSubmit' name='work_submit'>
                                <a class='btn btn-default' href='$back_link'>$langCancel</a>
                            </div>
                        </div>
                        </fieldset>
                     </form>
                     </div>
                     <div class='pull-right'><small>$GLOBALS[langMaxFileSize] " .
                ini_get('upload_max_filesize') . "</small></div><br>";
    }
}

// Print a box with the details of an assignment
function assignment_details($id, $row) {
    global $tool_content, $is_editor, $course_code, $themeimg, $m, $langDaysLeft,
    $langDays, $langWEndDeadline, $langNEndDeadLine, $langNEndDeadline,
    $langEndDeadline, $langDelAssign, $langAddGrade, $langZipDownload,
    $langSaved, $langGraphResults, $langConfirmDelete, $langWorkFile;

    if ($is_editor) {
        $tool_content .= action_bar(array(
            array(
                'title' => $langAddGrade,
                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id&amp;choice=add",
                'icon' => 'fa-plus-circle',
                'level' => 'primary-label',
                'button-class' => 'btn-success'
            ),
            array(
                'title' => $langZipDownload,
                'icon' => 'fa-file-archive-o',
                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;download=$id",
                'level' => 'primary'
            ),
            array(
                'title' => $langGraphResults,
                'icon' => 'fa-bar-chart',
                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id&amp;disp_results=true"
            ),
            array(
                'title' => $m['WorkUserGroupNoSubmission'],
                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id&amp;disp_non_submitted=true",
                'icon' => 'fa-minus-square'
            ),
            array(
                'title' => $langDelAssign,
                'icon' => 'fa-times',
                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id&amp;choice=do_delete",
                'button-class' => "btn-danger",
                'confirm' => "$langConfirmDelete"
            )
        ));
    }
    $deadline = (int)$row->deadline ? nice_format($row->deadline, true) : $m['no_deadline'];
    if ($row->time > 0) {
        $deadline_notice = "<br><span>($langDaysLeft " . format_time_duration($row->time) . ")</span>";
    } elseif ((int)$row->deadline) {
        $deadline_notice = "<br><span class='text-danger'>$langEndDeadline</span>";
    }
    $tool_content .= "
    <div class='panel panel-primary'>
        <div class='panel-heading'>
            <h3 class='panel-title'>$m[WorkInfo] &nbsp;". (($is_editor) ? icon('fa-edit', $m['edit'], "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id&amp;choice=edit"): "")."</h3>
        </div>
        <div class='panel-body'>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$m[title]:</strong>
                </div>
                <div class='col-sm-9'>
                    $row->title
                </div>
            </div>";
        if (!empty($row->description)) {
            $tool_content .= "<div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$m[description]:</strong>
                </div>
                <div class='col-sm-9'>
                    $row->description
                </div>
            </div>";
        }
        if (!empty($row->comments)) {
            $tool_content .= "<div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$m[comments]:</strong>
                </div>
                <div class='col-sm-9'>
                    $row->comments
                </div>
            </div>";
        }
        if (!empty($row->file_name)) {
            $tool_content .= "<div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langWorkFile:</strong>
                </div>
                <div class='col-sm-9'>
                    <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;get=$row->id&amp;file_type=1'>$row->file_name</a>
                </div>
            </div>";
        }
        $tool_content .= "
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$m[max_grade]:</strong>
                </div>
                <div class='col-sm-9'>
                    $row->max_grade
                </div>
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$m[start_date]:</strong>
                </div>
                <div class='col-sm-9'>
                    " . nice_format($row->submission_date, true) . "
                </div>
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$m[deadline]:</strong>
                </div>
                <div class='col-sm-9'>
                    $deadline ".(isset($deadline_notice) ? $deadline_notice : "")."
                </div>
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$m[group_or_user]:</strong>
                </div>
                <div class='col-sm-9'>
                    ".(($row->group_submissions == '0') ? $m['user_work'] : $m['group_work'])."
                </div>
            </div>
        </div>
    </div>";

}

// Show a table header which is a link with the appropriate sorting
// parameters - $attrib should contain any extra attributes requered in
// the <th> tags
function sort_link($title, $opt, $attrib = '') {
    global $tool_content, $course_code;
    $i = '';
    if (isset($_REQUEST['id'])) {
        $i = "&id=$_REQUEST[id]";
    }
    if (@($_REQUEST['sort'] == $opt)) {
        if (@($_REQUEST['rev'] == 1)) {
            $r = 0;
        } else {
            $r = 1;
        }
        $tool_content .= "
                  <th $attrib><a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;sort=$opt&rev=$r$i'>" . "$title</a></th>";
    } else {
        $tool_content .= "
                  <th $attrib><a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;sort=$opt$i'>$title</a></th>";
    }
}

// show assignment - prof view only
// the optional message appears instead of assignment details
function show_assignment($id, $display_graph_results = false) {
    global $tool_content, $m, $langBack, $langNoSubmissions, $langSubmissions,
    $langEndDeadline, $langWEndDeadline, $langNEndDeadline,
    $langDays, $langDaysLeft, $langGradeOk, $course_code, $webDir, $urlServer,
    $langGraphResults, $m, $course_code, $themeimg, $works_url, $course_id, $langDelWarnUserAssignment,
    $langAutoJudgeShowWorkResultRpt;

    $row = Database::get()->querySingle("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                FROM assignment
                                WHERE course_id = ?d AND id = ?d", $course_id, $id);

    $nav[] = $works_url;
    assignment_details($id, $row);

    $rev = (@($_REQUEST['rev'] == 1)) ? ' DESC' : '';
    if (isset($_REQUEST['sort'])) {
        if ($_REQUEST['sort'] == 'am') {
            $order = 'am';
        } elseif ($_REQUEST['sort'] == 'date') {
            $order = 'submission_date';
        } elseif ($_REQUEST['sort'] == 'grade') {
            $order = 'grade';
        } elseif ($_REQUEST['sort'] == 'filename') {
            $order = 'file_name';
        } else {
            $order = 'surname';
        }
    } else {
        $order = 'surname';
    }

    $result = Database::get()->queryArray("SELECT * FROM assignment_submit AS assign, user
                                 WHERE assign.assignment_id = ?d AND user.id = assign.uid
                                 ORDER BY ?s ?s", $id, $order, $rev);

    $num_results = count($result);
    if ($num_results > 0) {
        if ($num_results == 1) {
            $num_of_submissions = $m['one_submission'];
        } else {
            $num_of_submissions = sprintf("$m[more_submissions]", $num_results);
        }

        $gradeOccurances = array(); // Named array to hold grade occurances/stats
        $gradesExists = 0;
        foreach ($result as $row) {
            $theGrade = $row->grade;
            if ($theGrade) {
                $gradesExists = 1;
                if (!isset($gradeOccurances[$theGrade])) {
                    $gradeOccurances[$theGrade] = 1;
                } else {
                    if ($gradesExists) {
                        ++$gradeOccurances[$theGrade];
                    }
                }
            }
        }
        if (!$display_graph_results) {
            $result = Database::get()->queryArray("SELECT assign.id id, assign.file_name file_name,
                                                   assign.uid uid, assign.group_id group_id,
                                                   assign.submission_date submission_date,
                                                   assign.grade_submission_date grade_submission_date,
                                                   assign.grade grade, assign.comments comments,
                                                   assign.grade_comments grade_comments,
                                                   assignment.deadline deadline
                                                   FROM assignment_submit AS assign, user, assignment
                                                   WHERE assign.assignment_id = ?d AND assign.assignment_id = assignment.id AND user.id = assign.uid
                                                   ORDER BY ?s ?s", $id, $order, $rev);

            $tool_content .= "
                        <form action='$_SERVER[SCRIPT_NAME]?course=$course_code' method='post'>
                        <input type='hidden' name='grades_id' value='$id' />
                        <p><div class='sub_title1'>$langSubmissions:</div><p>
                        <p>$num_of_submissions</p>
                        <table width='100%' class='sortable'>
                        <tr>
                      <th width='3'>&nbsp;</th>";
            sort_link($m['username'], 'username');
            sort_link($m['am'], 'am');
            sort_link($m['filename'], 'filename');
            sort_link($m['sub_date'], 'date');
            sort_link($m['grade'], 'grade');
            $tool_content .= "</tr>";

            $i = 1;
            foreach ($result as $row) {
                //is it a group assignment?
                if (!empty($row->group_id)) {
                    $subContentGroup = "$m[groupsubmit] " .
                            "<a href='../group/group_space.php?course=$course_code&amp;group_id=$row->group_id'>" .
                            "$m[ofgroup] " . gid_to_name($row->group_id) . "</a>";
                } else {
                    $subContentGroup = '';
                }
                $uid_2_name = display_user($row->uid);
                $stud_am = Database::get()->querySingle("SELECT am FROM user WHERE id = ?d", $row->uid)->am;
                if ($i % 2 == 1) {
                    $row_color = "class='even'";
                } else {
                    $row_color = "class='odd'";
                }
                $filelink = empty($row->file_name) ? '&nbsp;' :
                        ("<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;get=$row->id'>" .
                        q($row->file_name) . "</a>");

                $late_sub_text = ((int) $row->deadline && $row->submission_date > $row->deadline) ?  '<div style="color:red;">$m[late_submission]</div>' : '';
                $tool_content .= "
                                <tr $row_color>
                                <td align='right' width='4' rowspan='2' valign='top'>$i.</td>
                                <td>${uid_2_name}</td>
                                <td width='85'>" . q($stud_am) . "</td>
                                <td width='180'>$filelink
                                <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$id&amp;as_id=$row->id' onClick='return confirmation(\"$langDelWarnUserAssignment\");'>
                                 <img src='$themeimg/delete.png' title='$m[WorkDelete]' />
                                </a>
                                </td>
                                <td width='100'>" . nice_format($row->submission_date, TRUE) .$late_sub_text. "</td>
                                <td width='5'>
                                <div align='center'><input type='text' value='{$row->grade}' maxlength='3' size='3' name='grades[{$row->id}]'></div>
                                </td>
                                </tr>
                                <tr $row_color>
                                <td colspan='5'>
                                <div>$subContentGroup</div>";
                if (trim($row->comments != '')) {
                    $tool_content .= "<div style='margin-top: .5em;'><b>$m[comments]:</b> " .
                            q($row->comments) . '</div>';
                }
                //professor comments
                $gradelink = "grade_edit.php?course=$course_code&amp;assignment=$id&amp;submission=$row->id";
                $reportlink = "work_result_rpt.php?course=$course_code&amp;assignment=$id&amp;submission=$row->id";
                if (trim($row->grade_comments)) {
                    $label = $m['gradecomments'] . ':';
                    $icon = 'edit.png';
                    $comments = "<div class='smaller'>" . standard_text_escape($row->grade_comments) . "</div>";
                } else {
                    $label = $m['addgradecomments'];
                    $icon = 'add.png';
                    $comments = '';
                }
                if ($row->grade_comments || $row->grade != '') {
                    $comments .= "<div class='smaller'><i>($m[grade_comment_date]: " .
                            nice_format($row->grade_submission_date) . ")</i></div>";
                }
                $tool_content .= "<div style='padding-top: .5em;'><a href='$gradelink'><b>$label</b></a>
				  <a href='$gradelink'><img src='$themeimg/$icon'></a>
				  $comments
				  <a href='$reportlink'><b>$langAutoJudgeShowWorkResultRpt</b></a>
                                </td>
                                </tr>";
                $i++;
            } //END of Foreach

            $tool_content .= "</table>
                        <p class='smaller right'><img src='$themeimg/email.png' alt='' >
                                $m[email_users]: <input type='checkbox' value='1' name='email'></p>
                        <p><input class='btn btn-primary' type='submit' name='submit_grades' value='$langGradeOk'></p>
                        </form>";
        } else {
        // display pie chart with grades results
            if ($gradesExists) {
                // Used to display grades distribution chart
                $graded_submissions_count = Database::get()->querySingle("SELECT COUNT(*) AS count FROM assignment_submit AS assign, user
                                                             WHERE assign.assignment_id = ?d AND user.id = assign.uid AND
                                                             assign.grade <> ''", $id)->count;
                $chart = new Plotter();
                $chart->setTitle("$langGraphResults");
                foreach ($gradeOccurances as $gradeValue => $gradeOccurance) {
                    $percentage = round((100.0 * $gradeOccurance / $graded_submissions_count),2);
                    $chart->growWithPoint("$gradeValue ($percentage%)", $percentage);
                }
                $tool_content .= $chart->plot();
            }
        }
    } else {
        $tool_content .= "
                      <p class='sub_title1'>$langSubmissions:</p>
                      <div class='alert alert-warning'>$langNoSubmissions</div>";
    }
}

function show_non_submitted($id) {
    global $tool_content, $works_url, $course_id, $m, $langSubmissions,
            $langGroup, $course_code;
    $row = Database::get()->querySingle("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                FROM assignment
                                WHERE course_id = ?d AND id = ?d", $course_id, $id);

    $nav[] = $works_url;
    assignment_details($id, $row);
    if ($row->group_submissions) {
        $groups = groups_with_no_submissions($id);
        $num_results = count($groups);
        if ($num_results > 0) {
            if ($num_results == 1) {
                $num_of_submissions = $m['one_submission'];
            } else {
                $num_of_submissions = sprintf("$m[more_submissions]", $num_results);
            }
                $tool_content .= "
                            <p><div class='sub_title1'>$m[WorkGroupNoSubmission]:</div><p>
                            <p>$num_of_submissions</p>
                            <div class='row'><div class='col-sm-12'>
                            <div class='table-responsive'>
                            <table class='sortable'>
                            <tr>
                          <th width='3'>&nbsp;</th>";
                sort_link($langGroup, 'username');
                $tool_content .= "</tr>";
                $i=1;
                foreach ($groups as $row => $value){
                    if ($i % 2 == 1) {
                        $row_color = "class='even'";
                    } else {
                        $row_color = "class='odd'";
                    }
                    $tool_content .= "<tr>
                            <td>$i.</td>
                            <td><a href='../group/group_space.php?course=$course_code&amp;group_id=$row'>$value</a></td>
                            </tr>";
                    $i++;
                }
                $tool_content .= "</table></div></div></div>";
        } else {
            $tool_content .= "
                      <p class='sub_title1'>$m[WorkGroupNoSubmission]:</p>
                      <div class='alert alert-warning'>$m[NoneWorkGroupNoSubmission]</div>";
        }

    } else {
        $users = users_with_no_submissions($id);
        $num_results = count($users);
        if ($num_results > 0) {
            if ($num_results == 1) {
                $num_of_submissions = $m['one_non_submission'];
            } else {
                $num_of_submissions = sprintf("$m[more_non_submissions]", $num_results);
            }
                $tool_content .= "
                            <p><div class='sub_title1'>$m[WorkUserNoSubmission]:</div><p>
                            <p>$num_of_submissions</p>
                            <div class='row'><div class='col-sm-12'>
                            <div class='table-responsive'>
                            <table class='table-default'>
                            <tr>
                          <th width='3'>&nbsp;</th>";
                sort_link($m['username'], 'username');
                sort_link($m['am'], 'am');
                $tool_content .= "</tr>";
                $i=1;
                foreach ($users as $row => $value){
                    if ($i % 2 == 1) {
                        $row_color = "class='even'";
                    } else {
                        $row_color = "class='odd'";
                    }
                    $tool_content .= "<tr>
                    <td>$i.</td>
                    <td>".display_user($row)."</td>
                    <td>".  uid_to_am($row) ."</td>
                    </tr>";

                    $i++;
                }
                $tool_content .= "</table></div></div></div>";
        } else {
            $tool_content .= "
                      <p class='sub_title1'>$m[WorkUserNoSubmission]:</p>
                      <div class='alert alert-warning'>$m[NoneWorkUserNoSubmission]</div>";
        }
    }
}
// show all the assignments - student view only
function show_student_assignments() {
    global $tool_content, $m, $uid, $course_id, $course_code,
    $langDaysLeft, $langDays, $langNoAssign, $urlServer,
    $course_code, $themeimg, $langAutoJudgeRank;


    $gids = user_group_info($uid, $course_id);
    if (!empty($gids)) {
        $gids_sql_ready = implode(',',array_keys($gids));
    } else {
        $gids_sql_ready = "''";
    }

    $result = Database::get()->queryArray("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                 FROM assignment WHERE course_id = ?d AND active = 1 AND
                                 (assign_to_specific = 0 OR assign_to_specific = 1 AND id IN
                                    (SELECT assignment_id FROM assignment_to_specific WHERE user_id = ?d UNION SELECT assignment_id FROM assignment_to_specific WHERE group_id IN ($gids_sql_ready))
                                 )
                                 ORDER BY CASE WHEN CAST(deadline AS UNSIGNED) = '0' THEN 1 ELSE 0 END, deadline", $course_id, $uid);

    if (count($result)>0) {
        $tool_content .= "
            <div class='row'><div class='col-sm-12'>
            <div class='table-responsive'><table class='table-default'>
                                  <tr>
                                      <th>$m[title]</th>
                                      <th class='text-center'>$m[deadline]</th>
                                      <th class='text-center'>$m[submitted]</th>
                                      <th>$m[grade]</th>
                                      <th>$langAutoJudgeRank</th>
                                  </tr>";
        $k = 0;
        foreach ($result as $row) {
            $title_temp = q($row->title);
            $test = (int)$row->deadline;
            $rankreportlink = "rank_report.php?course=$course_code&amp;assignment=$row->id";
            if((int)$row->deadline){
                $deadline = nice_format($row->deadline, true);
            }else{
                $deadline = $m['no_deadline'];
            }
            $tool_content .= "
                                <tr>
                                    <td><a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$row->id'>$title_temp</a></td>
                                    <td width='150' class='text-center'>" . $deadline ;
            if ($row->time > 0) {
                $tool_content .= "<br> (<span>$langDaysLeft" . format_time_duration($row->time) . ")</span>";
            } else if((int)$row->deadline){
                $tool_content .= "<br> (<span class='expired'>$m[expired]</span>)";
            }
            $tool_content .= "</td><td width='170' align='center'>";

            if ($submission = find_submissions(is_group_assignment($row->id), $uid, $row->id, $gids)) {
                foreach ($submission as $sub) {
                    if (isset($sub->group_id)) { // if is a group assignment
                        $tool_content .= "<div style='padding-bottom: 5px;padding-top:5px;font-size:9px;'>($m[groupsubmit] " .
                                "<a href='../group/group_space.php?course=$course_code&amp;group_id=$sub->group_id'>" .
                                "$m[ofgroup] " . gid_to_name($sub['group_id']) . "</a>)</div>";
                    }
                    $tool_content .= icon('fa-check-square-o', $m['yes'])."<br>";
                }
            } else {
                $tool_content .= icon('fa-square-o', $m['no']);
            }
            $tool_content .= "</td>
                                    <td width='30' align='center'>";
            foreach ($submission as $sub) {
                $grade = submission_grade($sub->id);
                if (!$grade) {
                    $grade = "<div style='padding-bottom: 5px;padding-top:5px;'> - </div>";
                }
                $tool_content .= "<div style='padding-bottom: 5px;padding-top:5px;'>$grade</div>";
            }
            $tool_content .= "</td><td class='option-btn-cell' align='center'><div style='padding-bottom: 5px;padding-top:10px;'><a href='". $rankreportlink ."'>". icon('fa-sort-alpha-asc') ."</a></div></td>
                            </tr>";
            
            $k++;
        }
        $tool_content .= '
                                  </table></div></div></div>';
    } else {
        $tool_content .= "<div class='alert alert-warning'>$langNoAssign</div>";
    }
}

// show all the assignments
function show_assignments() {
    global $tool_content, $m, $langEdit, $langDelete, $langNoAssign, $langNewAssign, $langCommands,
    $course_code, $themeimg, $course_id, $langConfirmDelete, $langDaysLeft, $m,
    $langWarnForSubmissions, $langDelSure;


    $result = Database::get()->queryArray("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
              FROM assignment WHERE course_id = ?d ORDER BY CASE WHEN CAST(deadline AS UNSIGNED) = '0' THEN 1 ELSE 0 END, deadline", $course_id);
 $tool_content .= action_bar(array(
            array('title' => $langNewAssign,
                  'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;add=1",
                  'button-class' => 'btn-success',
                  'icon' => 'fa-plus-circle',
                  'level' => 'primary-label')
            ));

    if (count($result)>0) {
        $tool_content .= "
            <div class='row'><div class='col-sm-12'>
                    <div class='table-responsive'>
                    <table class='table-default'>
                    <tr>
                      <th>$m[title]</th>
                      <th class='text-center'>$m[subm]</th>
                      <th class='text-center'>$m[nogr]</th>
                      <th class='text-center'>$m[deadline]</th>
                      <th class='text-center'>".icon('fa-gears')."</th>
                    </tr>";
        $index = 0;
        foreach ($result as $row) {
            // Check if assignement contains submissions
            $num_submitted = Database::get()->querySingle("SELECT COUNT(*) AS count FROM assignment_submit WHERE assignment_id = ?d", $row->id)->count;
            if (!$num_submitted) {
                $num_submitted = '&nbsp;';
            }

            $num_ungraded = Database::get()->querySingle("SELECT COUNT(*) AS count FROM assignment_submit WHERE assignment_id = ?d AND grade IS NULL", $row->id)->count;
            if (!$num_ungraded) {
                $num_ungraded = '&nbsp;';
            }

            $tool_content .= "<tr class='".(!$row->active ? "not_visible":"")."'>";
            $deadline = (int)$row->deadline ? nice_format($row->deadline, true) : $m['no_deadline'];
            $tool_content .= "<td>
                                <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id={$row->id}'>$row->title</a>
                            </td>
                            <td class='text-center'>$num_submitted</td>
                            <td class='text-center'>$num_ungraded</td>
                            <td class='text-center'>$deadline";
            if ($row->time > 0) {
                $tool_content .= " <br><span class='label label-warning'>$langDaysLeft" . format_time_duration($row->time) . "</span>";
            } else if((int)$row->deadline){
                $tool_content .= " <br><span class='label label-danger'>$m[expired]</span>";
            }
           $tool_content .= "</td>
              <td class='option-btn-cell'>" .
              action_button(array(
                    array('title' => $langDelete,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$row->id&amp;choice=do_delete",
                          'icon' => 'fa-times',
                          'class' => 'delete',
                          'confirm' => $langConfirmDelete),
                    array('title' => $langEdit,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$row->id&amp;choice=edit",
                          'icon' => 'fa-edit'),
                    array('title' => $m['WorkSubsDelete'],
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$row->id&amp;choice=do_purge",
                          'icon' => 'fa-eraser',
                          'confirm' => "$langWarnForSubmissions $langDelSure",
                          'show' => is_numeric($num_submitted) && $num_submitted > 0),
                    array('title' => $row->active == 1 ? $m['deactivate']: $m['activate'],
                          'url' => $row->active == 1 ? "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;choice=disable&amp;id=$row->id" : "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;choice=enable&amp;id=$row->id",
                          'icon' => $row->active == 1 ? 'fa-eye': 'fa-eye-slash'))).
                   "</td></tr>";
            $index++;
        }
        $tool_content .= '</table></div></div></div>';
    } else {
        $tool_content .= "<div class='alert alert-warning'>$langNoAssign</div>";
    }
}

// submit grade and comment for a student submission
function submit_grade_comments($id, $sid, $grade, $comment, $email, $auto_judge_scenarios_output, $preventUiAlterations = false){
    global $tool_content, $langGrades, $langWorkWrongInput, $course_id;

    $grade_valid = filter_var($grade, FILTER_VALIDATE_FLOAT);
    (isset($grade) && $grade_valid!== false) ? $grade = $grade_valid : $grade = NULL;

    if(isset($auto_judge_scenarios_output)){
        Database::get()->query("UPDATE assignment_submit SET auto_judge_scenarios_output = ?s
                                WHERE id = ?d",serialize($auto_judge_scenarios_output), $sid);
    }

    if (Database::get()->query("UPDATE assignment_submit
                                SET grade = ?d, grade_comments = ?s,
                                grade_submission_date = NOW(), grade_submission_ip = ?s
                                WHERE id = ?d", $grade, $comment, $_SERVER['REMOTE_ADDR'], $sid)->affectedRows>0) {
        $title = Database::get()->querySingle("SELECT title FROM assignment WHERE id = ?d", $id)->title;
        Log::record($course_id, MODULE_ID_ASSIGN, LOG_MODIFY, array('id' => $sid,
                'title' => $title,
                'grade' => $grade,
                'comments' => $comment));
        //update gradebook if needed
        $quserid = Database::get()->querySingle("SELECT uid FROM assignment_submit WHERE id = ?d", $sid)->uid;
        update_gradebook_book($quserid, $id, $grade, 'assignment');
        if(!$preventUiAlterations) Session::Messages($langGrades, 'alert-success');
    } else {
        if(!$preventUiAlterations) Session::Messages($langGrades);
    }
    if ($email) {
        grade_email_notify($id, $sid, $grade, $comment);
    }
    if(!$preventUiAlterations) show_assignment($id);
}

// submit grades to students
function submit_grades($grades_id, $grades, $email = false) {
    global $tool_content, $langGrades, $langWorkWrongInput, $course_id;

    foreach ($grades as $sid => $grade) {
        $sid = intval($sid);
        $val = Database::get()->querySingle("SELECT grade from assignment_submit WHERE id = ?d", $sid)->grade;
        $grade_valid = filter_var($grade, FILTER_VALIDATE_FLOAT);
        (isset($grade) && $grade_valid!== false) ? $grade = $grade_valid : $grade = NULL;
        if ($val != $grade) {
            if (Database::get()->query("UPDATE assignment_submit
                                        SET grade = ?d, grade_submission_date = NOW(), grade_submission_ip = ?s
                                        WHERE id = ?d", $grade, $_SERVER['REMOTE_ADDR'], $sid)->affectedRows > 0) {
                $assign_id = Database::get()->querySingle("SELECT assignment_id FROM assignment_submit WHERE id = ?d", $sid)->assignment_id;
                $title = Database::get()->querySingle("SELECT title FROM assignment WHERE assignment.id = ?d", $assign_id)->title;
                Log::record($course_id, MODULE_ID_ASSIGN, LOG_MODIFY, array('id' => $sid,
                        'title' => $title,
                        'grade' => $grade));

                //update gradebook if needed
                $quserid = Database::get()->querySingle("SELECT uid FROM assignment_submit WHERE id = ?d", $sid)->uid;
                update_gradebook_book($quserid, $assign_id, $grade, 'assignment');

                if ($email) {
                    grade_email_notify($grades_id, $sid, $grade, '');
                }
                Session::Messages($langGrades, 'alert-success');
            }
        }
    }
    show_assignment($grades_id);
}

// functions for downloading
function send_file($id, $file_type) {
    global $course_code, $uid, $is_editor;
    if (isset($file_type)) {
        $info = Database::get()->querySingle("SELECT * FROM assignment WHERE id = ?d", $id);
        if (count($info)==0) {
            return false;
        }
        if (!($is_editor || $GLOBALS['is_member'])) {
            return false;
        }
        send_file_to_client("$GLOBALS[workPath]/admin_files/$info->file_path", $info->file_name, null, true);
    } else {
        $info = Database::get()->querySingle("SELECT * FROM assignment_submit WHERE id = ?d", $id);
        if (count($info)==0) {
            return false;
        }
        if ($info->group_id) {
            initialize_group_info($info->group_id);
        }
        if (!($is_editor or $info->uid == $uid or $GLOBALS['is_member'])) {
            return false;
        }
        send_file_to_client("$GLOBALS[workPath]/$info->file_path", $info->file_name, null, true);
    }
    exit;
}

// Zip submissions to assignment $id and send it to user
function download_assignments($id) {
    global $workPath, $course_code;
    $counter = Database::get()->querySingle('SELECT COUNT(*) AS count FROM assignment_submit WHERE assignment_id = ?d', $id)->count;
    if ($counter>0) {
        $secret = work_secret($id);
        $filename = "{$course_code}_work_$id.zip";
        chdir($workPath);
        create_zip_index("$secret/index.html", $id);
        $zip = new PclZip($filename);
        $flag = $zip->create($secret, "work_$id", $secret);
        header("Content-Type: application/x-zip");
        header("Content-Disposition: attachment; filename=$filename");
        stop_output_buffering();
        @readfile($filename);
        @unlink($filename);
        exit;
    }else{
        return false;
    }
}

// Create an index.html file for assignment $id listing user submissions
// Set $online to TRUE to get an online view (on the web) - else the
// index.html works for the zip file
function create_zip_index($path, $id, $online = FALSE) {
    global $charset, $m;

    $fp = fopen($path, "w");
    if (!$fp) {
        die("Unable to create assignment index file - aborting");
    }
    fputs($fp, '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '">
                <style type="text/css">
                .sep td, th { border: 1px solid; }
                td { border: none; }
                table { border-collapse: collapse; border: 2px solid; }
                .sep { border-top: 2px solid black; }
                </style>
	</head>
	<body>
		<table width="95%" class="tbl">
			<tr>
				<th>' . $m['username'] . '</th>
				<th>' . $m['am'] . '</th>
				<th>' . $m['filename'] . '</th>
				<th>' . $m['sub_date'] . '</th>
				<th>' . $m['grade'] . '</th>
			</tr>');

    $result = Database::get()->queryArray("SELECT a.uid, a.file_path, a.submission_date, a.grade, a.comments, a.grade_comments, a.group_id, b.deadline FROM assignment_submit a, assignment b WHERE a.assignment_id = ?d AND a.assignment_id = b.id ORDER BY a.id", $id);

    foreach ($result as $row) {
        $filename = basename($row->file_path);
        $filelink = empty($filename) ? '&nbsp;' :
                ("<a href='$filename'>" . htmlspecialchars($filename) . '</a>');
        $late_sub_text = ((int) $row->deadline && $row->submission_date > $row->deadline) ?  '<div style="color:red;">$m[late_submission]</div>' : '';
        fputs($fp, '
			<tr class="sep">
				<td>' . q(uid_to_name($row->uid)) . '</td>
				<td>' . q(uid_to_am($row->uid)) . '</td>
				<td align="center">' . $filelink . '</td>
				<td align="center">' . $row->submission_date .$late_sub_text. '</td>
				<td align="center">' . $row->grade . '</td>
			</tr>');
        if (trim($row->comments != '')) {
            fputs($fp, "
			<tr><td colspan='6'><b>$m[comments]: " .
                    "</b>$row->comments</td></tr>");
        }
        if (trim($row->grade_comments != '')) {
            fputs($fp, "
			<tr><td colspan='6'><b>$m[gradecomments]: " .
                    "</b>$row->grade_comments</td></tr>");
        }
        if (!empty($row->group_id)) {
            fputs($fp, "<tr><td colspan='6'>$m[groupsubmit] " .
                    "$m[ofgroup] $row->group_id</td></tr>\n");
        }
    }
    fputs($fp, ' </table></body></html>');
    fclose($fp);
}

// Show a simple html page with grades and submissions
function show_plain_view($id) {
    global $workPath, $charset;

    $secret = work_secret($id);
    create_zip_index("$secret/index.html", $id, TRUE);
    header("Content-Type: text/html; charset=$charset");
    readfile("$workPath/$secret/index.html");
    exit;
}

// Notify students by email about grade/comment submission
// Send to single user for individual submissions or group members for group
// submissions
function grade_email_notify($assignment_id, $submission_id, $grade, $comments) {
    global $m, $currentCourseName, $urlServer, $course_code;
    static $title, $group;

    if (!isset($title)) {
        $res = Database::get()->querySingle("SELECT title, group_submissions FROM assignment WHERE id = ?d", $assignment_id);
        $title = $res->title;
        $group = $res->group_submissions;
    }
    $info = Database::get()->querySingle("SELECT uid, group_id
                                         FROM assignment_submit WHERE id= ?d", $submission_id);

    $subject = sprintf($m['work_email_subject'], $title);
    $body = sprintf($m['work_email_message'], $title, $currentCourseName) . "\n\n";
    if ($grade != '') {
        $body .= "$m[grade]: $grade\n";
    }
    if ($comments) {
        $body .= "$m[gradecomments]: $comments\n";
    }
    $body .= "\n$m[link_follows]\n{$urlServer}modules/work/work.php?course=$course_code&id=$assignment_id\n";
    if (!$group or !$info->group_id) {
        send_mail_to_user_id($info->uid, $subject, $body);
    } else {
        send_mail_to_group_id($info->group_id, $subject, $body);
    }
}

function send_mail_to_group_id($gid, $subject, $body) {
    global $charset;
    $res = Database::get()->queryArray("SELECT surname, givenname, email
                                 FROM user, group_members AS members
                                 WHERE members.group_id = ?d
                                 AND user.id = members.user_id", $gid);
    foreach ($res as $info) {
        send_mail('', '', "$info->givenname $info->surname", $info->email, $subject, $body, $charset);
    }
}

function send_mail_to_user_id($uid, $subject, $body) {
    global $charset;
    $user = Database::get()->querySingle("SELECT surname, givenname, email FROM user WHERE id = ?d", $uid);
    send_mail('', '', "$user->givenname $user->surname", $user->email, $subject, $body, $charset);
}

// Return a list of users with no submissions for assignment $id
function users_with_no_submissions($id) {
    global $course_id;
    if (Database::get()->querySingle("SELECT assign_to_specific FROM assignment WHERE id = ?d", $id)->assign_to_specific) {
        $q = Database::get()->queryArray("SELECT user.id AS id, surname, givenname
                                FROM user, course_user
                                WHERE user.id = course_user.user_id
                                AND course_user.course_id = ?d AND course_user.status = 5
                                AND user.id NOT IN (SELECT uid FROM assignment_submit
                                                    WHERE assignment_id = ?d) AND user.id IN (SELECT user_id FROM assignment_to_specific WHERE assignment_id = ?d)", $course_id, $id, $id);
    } else {
        $q = Database::get()->queryArray("SELECT user.id AS id, surname, givenname
                                FROM user, course_user
                                WHERE user.id = course_user.user_id
                                AND course_user.course_id = ?d AND course_user.status = 5
                                AND user.id NOT IN (SELECT uid FROM assignment_submit
                                                    WHERE assignment_id = ?d)", $course_id, $id);
    }
    $users = array();
    foreach ($q as $row) {
        $users[$row->id] = "$row->surname $row->givenname";
    }
    return $users;
}

// Return a list of groups with no submissions for assignment $id
function groups_with_no_submissions($id) {
    global $course_id;

    $q = Database::get()->queryArray('SELECT group_id FROM assignment_submit WHERE assignment_id = ?d', $id);
    $groups = user_group_info(null, $course_id, $id);
    if (count($q)>0) {
        foreach ($q as $row) {
            unset($groups[$row->group_id]);
        }
    }
    return $groups;
}

function doScenarioAssertion($scenarionAssertion, $scenarioInputResult, $scenarioOutputExpectation) {
    switch($scenarionAssertion) {
        case 'eq':
            $assertionResult = ($scenarioInputResult == $scenarioOutputExpectation);
            break;
        case 'same':
            $assertionResult = ($scenarioInputResult === $scenarioOutputExpectation);
            break;
        case 'notEq':
            $assertionResult = ($scenarioInputResult != $scenarioOutputExpectation);
            break;
        case 'notSame':
            $assertionResult = ($scenarioInputResult !== $scenarioOutputExpectation);
            break;
        case 'integer':
            $assertionResult = (is_int($scenarioInputResult));
            break;
        case 'float':
            $assertionResult = (is_float($scenarioInputResult));
            break;
        case 'digit':
            $assertionResult = (ctype_digit($scenarioInputResult));
            break;
        case 'boolean':
            $assertionResult = (is_bool($scenarioInputResult));
            break;
        case 'notEmpty':
            $assertionResult = (empty($scenarioInputResult) === false);
            break;
        case 'notNull':
            $assertionResult = ($scenarioInputResult !== null);
            break;
        case 'string':
            $assertionResult = (is_string($scenarioInputResult));
            break;
        case 'startsWith':
            $assertionResult = (mb_strpos($scenarioInputResult, $scenarioOutputExpectation, null, 'utf8') === 0);
            break;
        case 'endsWith':
            $stringPosition  = mb_strlen($scenarioInputResult, 'utf8') - mb_strlen($scenarioOutputExpectation, 'utf8');
            $assertionResult = (mb_strripos($scenarioInputResult, $scenarioOutputExpectation, null, 'utf8') === $stringPosition);
            break;
        case 'contains':
            $assertionResult = (mb_strpos($scenarioInputResult, $scenarioOutputExpectation, null, 'utf8'));
            break;
        case 'numeric':
            $assertionResult = (is_numeric($scenarioInputResult));
            break;
        case 'isArray':
            $assertionResult = (is_array($scenarioInputResult));
            break;
        case 'true':
            $assertionResult = ($scenarioInputResult === true);
            break;
        case 'false':
            $assertionResult = ($scenarioInputResult === false);
            break;
        case 'isJsonString':
            $assertionResult = (json_decode($value) !== null && JSON_ERROR_NONE === json_last_error());
            break;
        case 'isObject':
            $assertionResult = (is_object($scenarioInputResult));
            break;
    }

    return $assertionResult;
}