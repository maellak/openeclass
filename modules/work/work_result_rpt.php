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

$nameTools = 'Αυτόματος κριτής: Αναλυτική αναφορά ';

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

function get_assignment_submit_details($sid) {
    return Database::get()->querySingle("SELECT * FROM assignment_submit WHERE id = ?d",$sid);
}

// $assign contains an array with the assignment's details
function show_report_table($id, $sid, $assign) {
     global $m, $langGradeOk, $tool_content, $course_code;
     $sub = get_assignment_submit_details($sid);
         
     if (count($sub)>0) {
        if($assign->auto_judge){// ο αυτόματος κριτής είναι ενεργοποιημένος
                $auto_judge_scenarios = unserialize($assign->auto_judge_scenarios);
                $auto_judge_scenarios_output = unserialize($assign->auto_judge_scenarios_output);
                $tool_content .= "
                        <table width='99%' class='table-default'>
                        <tr> <td> <b>Αποτελέσματα για</b>:".  q(uid_to_name($sub->uid))."</td> </tr>
                        <tr> <td> <b>Βαθμός</b>: $sub->grade /$assign->max_grade </td>
                             <td><b> Κατάταξη</b>: - </td>
                        </tr>
                          <tr> <td> <b>Είσοδος</b> </td>
                               <td> <b>Έξοδος</b> </td>
                               <td> <b>Αναμενόμενη έξοδος</b> </td>
                               <td> <b>Αποτέλεσμα</b> </td>
                        </tr>
                        ".get_table_content($auto_judge_scenarios, $auto_judge_scenarios_output)."
                        </table>
                     <br>";
          }
          else{
               Session::Messages(' Ο αυτόματος κριτής δεν είναι ενεργοποιημένος για την συγκεκριμένη εργασία. ',        'alert-danger');
            }
         
     } else {
            Session::Messages($m['WorkNoSubmission'], 'alert-danger');
            redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$id);
     }
  }

function get_table_content($auto_judge_scenarios, $auto_judge_scenarios_output) {
    global $themeimg;
    $table_content = "";
    $i=0;
    foreach($auto_judge_scenarios as $cur_senarios){
                     $icon = ($auto_judge_scenarios_output[$i]['passed']==1) ? 'tick.png' : 'delete.png';
                     $table_content.="
                                      <tr><td>".$cur_senarios['input']."</td>
                                      <td>".$auto_judge_scenarios_output[$i]['student_output'].
                                      "</td><td>".$cur_senarios['output']."</td>
                                      <td><img src='".$themeimg."/" .$icon."'></td></tr>";
                     $i++;
                }   
    return $table_content;
  }
