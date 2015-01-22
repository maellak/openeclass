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
 * @file pollparticipate.php
 */

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Questionnaire';

require_once '../../include/baseTheme.php';
require_once 'functions.php';

load_js('bootstrap-slider');

$toolName = $langQuestionnaire;
$pageName = $langParticipate;
$navigation[] = array("url" => "index.php?course=$course_code", "name" => $langQuestionnaire);

if (!isset($_REQUEST['UseCase']))
    $_REQUEST['UseCase'] = "";
if (!isset($_REQUEST['pid']))
    die();
$p = Database::get()->querySingle("SELECT pid FROM poll WHERE course_id = ?d AND pid = ?d ORDER BY pid", $course_id, $_REQUEST['pid']);
if(!$p){
    redirect_to_home_page("modules/questionnaire/index.php?course=$course_code");
}

switch ($_REQUEST['UseCase']) {
    case 1:       
        printPollForm();
        break;
    case 2:
        submitPoll();
        break;
    default:       
        printPollForm();
}

draw($tool_content, 2, null, $head_content);

function printPollForm() {
    global $course_id, $course_code, $tool_content,
    $langSubmit, $langPollInactive, $langPollUnknown, $uid,
    $langPollAlreadyParticipated, $is_editor, $langBack, $langQuestion,
    $langCancel, $head_content;
    
    $head_content .= " 
    <script>
        $(function() {
            $('.grade_bar').slider();    
        });
    </script>";
    
    $pid = $_REQUEST['pid'];
    
    // check if user has participated
    $has_participated = Database::get()->querySingle("SELECT COUNT(*) AS count FROM poll_answer_record WHERE user_id = ?d AND pid = ?d", $uid, $pid)->count;
    if ($has_participated > 0 && !$is_editor){
        Session::Messages($langPollAlreadyParticipated);
        redirect_to_home_page('modules/questionnaire/index.php?course='.$course_code);
    }        
    // *****************************************************************************
    //		Get poll data
    //******************************************************************************/

    $thePoll = Database::get()->querySingle("SELECT * FROM poll WHERE course_id = ?d AND pid = ?d "
            . "ORDER BY pid",$course_id, $pid);
    $temp_CurrentDate = date("Y-m-d H:i");
    $temp_StartDate = $thePoll->start_date;
    $temp_EndDate = $thePoll->end_date;
    $temp_StartDate = mktime(substr($temp_StartDate, 11, 2), substr($temp_StartDate, 14, 2), 0, substr($temp_StartDate, 5, 2), substr($temp_StartDate, 8, 2), substr($temp_StartDate, 0, 4));
    $temp_EndDate = mktime(substr($temp_EndDate, 11, 2), substr($temp_EndDate, 14, 2), 0, substr($temp_EndDate, 5, 2), substr($temp_EndDate, 8, 2), substr($temp_EndDate, 0, 4));
    $temp_CurrentDate = mktime(substr($temp_CurrentDate, 11, 2), substr($temp_CurrentDate, 14, 2), 0, substr($temp_CurrentDate, 5, 2), substr($temp_CurrentDate, 8, 2), substr($temp_CurrentDate, 0, 4));
    
    if (($temp_CurrentDate >= $temp_StartDate) && ($temp_CurrentDate < $temp_EndDate)) {
        $tool_content .= action_bar(array(
            array(
                'title' => $langBack,
                'url' => "index.php?course=$course_code",
                'icon' => 'fa-reply',
                'level' => 'primary-label'
            )
        ));
        $tool_content .= "
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <h3 class='panel-title'>$thePoll->name</h3>
                </div>";
        if ($thePoll->description) {
            $tool_content .= "
                <div class='panel-body'>
                    <p>$thePoll->description</p>
                </div>";
        }
        $tool_content .= "
            </div>
            <form class='form-horizontal' role='form' action='$_SERVER[SCRIPT_NAME]?course=$course_code' id='poll' method='post'>
            <input type='hidden' value='2' name='UseCase'>
            <input type='hidden' value='$pid' name='pid'>";     

        //*****************************************************************************
        //		Get answers + questions
        //******************************************************************************/
        $questions = Database::get()->queryArray("SELECT * FROM poll_question
			WHERE pid = ?d ORDER BY q_position ASC", $pid);
        $i=1;
        foreach ($questions as $theQuestion) {           
            $pqid = $theQuestion->pqid;
            $qtype = $theQuestion->qtype;
            if($qtype==QTYPE_LABEL) {
                $tool_content .= "    
                    <div class='alert alert-info' role='alert'>
                        $theQuestion->question_text
                    </div>";                
            } else {
                $tool_content .= "
                    <div class='panel panel-success'>
                        <div class='panel-heading'>
                            $langQuestion $i
                        </div>
                        <div class='panel-body'>
                            <h4>".q($theQuestion->question_text)."</h4>
                            <input type='hidden' name='question[$pqid]' value='$qtype'>";
                if ($qtype == QTYPE_SINGLE || $qtype == QTYPE_MULTIPLE) {
                    $name_ext = ($qtype == QTYPE_SINGLE)? '': '[]';
                    $type_attr = ($qtype == QTYPE_SINGLE)? "radio": "checkbox";
                    $answers = Database::get()->queryArray("SELECT * FROM poll_question_answer 
                                WHERE pqid = ?d ORDER BY pqaid", $pqid);                   
                    if ($qtype == QTYPE_MULTIPLE) $tool_content .= "<input type='hidden' name='answer[$pqid]' value='-1'>";
                    foreach ($answers as $theAnswer) {
                        $tool_content .= "
                        <div class='form-group'>
                            <div class='col-sm-offset-1 col-sm-11'>
                                <div class='$type_attr'>
                                    <label>
                                        <input type='$type_attr' name='answer[$pqid]$name_ext' value='$theAnswer->pqaid'>".q($theAnswer->answer_text)." 
                                    </label>
                                </div>
                            </div>
                        </div>";
                    }
                    if ($qtype == QTYPE_SINGLE) {
                        $tool_content .= "
                        <div class='form-group'>
                            <div class='col-sm-offset-1 col-sm-11'>                            
                                <div class='$type_attr'>
                                    <label>
                                        <input type='$type_attr' name='answer[$pqid]' value='-1' checked>$langPollUnknown
                                    </label>
                                </div>
                            </div>
                        </div>";
                               
                    }
                } elseif ($qtype == QTYPE_SCALE) {
                        $tool_content .= "
                        <div class='form-group'>
                            <div class='col-sm-offset-2 col-sm-10'>
                                <input name='answer[$pqid]' class='grade_bar' data-slider-id='ex1Slider' type='text' data-slider-min='1' data-slider-max='$theQuestion->q_scale' data-slider-step='1' data-slider-value='1'>
                            </div>
                            
                        </div>";
                } elseif ($qtype == QTYPE_FILL) {
                    $tool_content .= "
                        <div class='form-group margin-bottom-fat'>                           
                            <div class='col-sm-12 margin-top-thin'>
                                <textarea class='form-control' name='answer[$pqid]'></textarea>
                            </div>
                        </div>";
                }                
                $tool_content .= "
                        </div>
                    </div>
                ";
                $i++;
            }           
        }
        $tool_content .= "<div class='text-center'>";
        if (!$is_editor) {
            $tool_content .= "<input class='btn btn-primary' name='submit' type='submit' value='".q($langSubmit)."'> ";
        }
        $tool_content .= "<a class='btn btn-default' href='index.php?course=$course_code'>".(($is_editor) ? q($langBack) : q($langCancel) )."</a></div></form>";
    } else {
        Session::Messages($langPollInactive);
        redirect_to_home_page("modules/questionnaire/index.php?course=$course_code");
    }	
}

function submitPoll() {
    global $tool_content, $course_code, $user_id, $langPollSubmitted, $langBack;

    // first populate poll_answer
    $user_id = $GLOBALS['uid'];
    $CreationDate = date("Y-m-d H:i");
    $pid = intval($_POST['pid']);
    $answer = $_POST['answer'];
    foreach ($_POST['question'] as $pqid => $qtype) {
        $pqid = intval($pqid);
        if ($qtype == QTYPE_MULTIPLE) {
            if(is_array($answer[$pqid])){
                foreach ($answer[$pqid] as $aid) {
                    $aid = intval($aid);
                    Database::get()->query("INSERT INTO poll_answer_record (pid, qid, aid, answer_text, user_id, submit_date)
                        VALUES (?d, ?d, ?d, '', ?d , NOW())", $pid, $pqid, $aid, $user_id);
                }
            } else {
                $aid = -1;
                Database::get()->query("INSERT INTO poll_answer_record (pid, qid, aid, answer_text, user_id, submit_date)
                    VALUES (?d, ?d, ?d, '', ?d , NOW())", $pid, $pqid, $aid, $user_id);                
            }
            continue;
        } elseif ($qtype == QTYPE_SCALE) {
            $aid = 0;
            $answer_text = $answer[$pqid];         
        } elseif ($qtype == QTYPE_SINGLE) {
            $aid = intval($answer[$pqid]);
            $answer_text = '';
        } elseif ($qtype == QTYPE_FILL) {
            $answer_text = $answer[$pqid];
            $aid = 0;
        } else {
            continue;
        }
        Database::get()->query("INSERT INTO poll_answer_record (pid, qid, aid, answer_text, user_id, submit_date)
			VALUES (?d, ?d, ?d, ?s, ?d , ?t)", $pid, $pqid, $aid, $answer_text, $user_id, $CreationDate);
    }
    $end_message = Database::get()->querySingle("SELECT end_message FROM poll WHERE pid = ?d", $pid)->end_message;
    $tool_content .= "<div class='alert alert-success'>".$langPollSubmitted."</div>";
    if (!empty($end_message)) {
        $tool_content .=  $end_message;
    }
    $tool_content .= "<br><div class=\"text-center\"><a class='btn btn-default' href=\"index.php?course=$course_code\">".$langBack."</a></div>";
}
