<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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

$require_current_course = true;
require_once '../../include/baseTheme.php';
require_once 'work_functions.php';
require_once 'modules/group/group_functions.php';

$nameTools = $m['grades'];

if (isset($_GET['assignment']) && isset($_GET['submission'])) {
    $as_id = intval($_GET['assignment']);
    $sub_id = intval($_GET['submission']);
    $assign = get_assignment_details($as_id);

    $navigation[] = array("url" => "index.php?course=$course_code", "name" => $langWorks);
    $navigation[] = array("url" => "index.php?course=$course_code&amp;id=$as_id", "name" => q($assign->title));
    show_report_table($as_id, $sub_id, $assign);
    draw($tool_content, 2);
} else {
    redirect_to_home_page('modules/work/index.php?course='.$course_code);
}

// Returns an array of the details of assignment $id
function get_assignment_details($id) {
    global $course_id;
    return Database::get()->querySingle("SELECT * FROM assignment WHERE course_id = ?d AND id = ?d", $course_id, $id);
}

// $assign contains an array with the assignment's details
function show_report_table($id, $sid, $assign) {
        global $m, $langGradeOk, $tool_content, $course_code;
    $sub = Database::get()->querySingle("SELECT * FROM assignment_submit WHERE id = ?d",$sid);
    if (count($sub)>0) {
    
        $auto_judge_scenarios = unserialize($assign->auto_judge_scenarios);
        $auto_judge_scenarios_output = unserialize($assign->auto_judge_scenarios_output);
        $table_content = "";
        $i=0;
         global $themeimg;
         //  print_r($auto_judge_scenarios_output[$i]['student_output']);
        foreach($auto_judge_scenarios as $cur_senarios){
             $icon = $auto_judge_scenarios_output[$i]['passed']==1?'tick.png': 'delete.png';
             $table_content.="<tr><td>".$cur_senarios['input']."</td><td>".$auto_judge_scenarios_output[$i]['student_output']."              </td><td>".$cur_senarios['output']."</td><td><img src='".$themeimg."/" .$icon."'></td></tr>";
             $i++;
        }
        $tool_content .= "
                <table width='99%' class='table'>
                <tr> <td> Αποτελέσματα για $assign->title </td> </tr>
                <tr> <td> Βαθμός $sub->grade /$assign->max_grade </td>
                     <td> Κατάταξη: - </td>
                </tr>
                  <tr> <td> Είσοδος </td>
                       <td> Έξοδος </td>
                       <td> Αναμενόμενη έξοδος </td>
                       <td> Αποτέλεσμα </td>
                </tr>
                ".$table_content."
                </table>
             <br>";
    } else {
        Session::Messages($m['WorkNoSubmission'], 'alert-danger');
        redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$id);
    }
}
