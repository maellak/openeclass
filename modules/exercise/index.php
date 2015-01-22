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
/**
 * @file index.php
 * @brief main exercise module script
 */

require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
$require_current_course = TRUE;

$require_help = TRUE;
$helpTopic = 'Exercise';
$guest_allowed = true;

include '../../include/baseTheme.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
require_once 'modules/search/indexer.class.php';
ModalBoxHelper::loadModalBox();
/* * ** The following is added for statistics purposes ** */
require_once 'include/action.php';
$action = new action();
$action->record(MODULE_ID_EXERCISE);

$pageName = $langExercices;

/* * **************************** */
/* Clears the exercise session */
/* * **************************** */
if (isset($_SESSION['objExercise'])) {
    unset($_SESSION['objExercise']);
}
if (isset($_SESSION['objQuestion'])) {
    unset($_SESSION['objQuestion']);
}
if (isset($_SESSION['objAnswer'])) {
    unset($_SESSION['objAnswer']);
}
if (isset($_SESSION['questionList'])) {
    unset($_SESSION['questionList']);
}
if (isset($_SESSION['exerciseResult'])) {
    unset($_SESSION['exerciseResult']);
}
//Unsetting the redirect cookie which is set in case of exercise page unload event
//More info in exercise_submit.php comments
if (isset($_COOKIE['inExercise'])) {
    setcookie("inExercise", "", time() - 3600);
}

// maximum number of exercises on a same page
$limitExPage = 15;
if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
} else {
    $page = 0;
}
// selects $limitExPage exercises at the same time
$from = $page * $limitExPage;

// only for administrator
if ($is_editor) {
    load_js('tools.js');

    if (isset($_GET['exerciseId'])) {
        $exerciseId = $_GET['exerciseId'];
    }
    if (!empty($_GET['choice'])) {
        // construction of Exercise
        $objExerciseTmp = new Exercise();
        if ($objExerciseTmp->read($exerciseId)) {
            switch ($_GET['choice']) {
                case 'delete': // deletes an exercise
                    $objExerciseTmp->delete();
                    Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                    Session::Messages($langPurgeExerciseSuccess, 'alert-success');
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                case 'purge': // purge exercise results
                    $objExerciseTmp->purge();
                    Session::Messages($langPurgeExerciseResultsSuccess);
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                case 'enable':  // enables an exercise
                    $objExerciseTmp->enable();
                    $objExerciseTmp->save();
                    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                case 'disable': // disables an exercise
                    $objExerciseTmp->disable();
                    $objExerciseTmp->save();
                    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                case 'public':  // make exercise public
                    $objExerciseTmp->makepublic();
                    $objExerciseTmp->save();
                    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                    break;
                case 'limited':  // make exercise limited
                    $objExerciseTmp->makelimited();
                    $objExerciseTmp->save();
                    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                    break;
                case 'clone':  // make exercise limited
                    $objExerciseTmp->duplicate();
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                    break;                
            }
        }
        // destruction of Exercise
        unset($objExerciseTmp);
    }
    $result = Database::get()->queryArray("SELECT id, title, description, type, active, public FROM exercise WHERE course_id = ?d ORDER BY id LIMIT ?d, ?d", $course_id, $from, $limitExPage);
    $qnum = Database::get()->querySingle("SELECT COUNT(*) as count FROM exercise WHERE course_id = ?d", $course_id)->count;
} else {
	$result = Database::get()->queryArray("SELECT id, title, description, type, active, public, start_date, end_date, time_constraint, attempts_allowed, score " .
            "FROM exercise WHERE course_id = ?d AND active = 1 ORDER BY id LIMIT ?d, ?d", $course_id, $from, $limitExPage);
	$qnum = Database::get()->querySingle("SELECT COUNT(*) as count FROM exercise WHERE course_id = ?d AND active = 1", $course_id)->count;
}
$paused_exercises = Database::get()->queryArray("SELECT eid, title FROM exercise_user_record a "
        . "JOIN exercise b ON a.eid = b.id WHERE b.course_id = ?d AND a.uid = ?d "
        . "AND a.attempt_status = ?d", $course_id, $uid, ATTEMPT_PAUSED);
$num_of_ex = $qnum; //Getting number of all active exercises of the course
$nbrExercises = count($result); //Getting number of limited (offset and limit) exercises of the course (active and inactive)
if (count($paused_exercises) > 0) {
    foreach ($paused_exercises as $row) {       
        $paused_exercises_ids[] = $row->eid;        
        $tool_content .="<div class='alert alert-info'>$langTemporarySaveNotice " . q($row->title) . ". <a href='exercise_submit.php?course=$course_code&exerciseId=$row->eid'>($langCont)</a></div>";
    }
}
if ($is_editor) {
    $pending_exercises = Database::get()->queryArray("SELECT eid, title FROM exercise_user_record a "
            . "JOIN exercise b ON a.eid = b.id WHERE a.attempt_status = ?d AND b.course_id = ?d", ATTEMPT_PENDING, $course_id);
    if (count($pending_exercises) > 0) {
        foreach ($pending_exercises as $row) {           
            $tool_content .="<div class='alert alert-info'>$langPendingExercise " . q($row->title) . ". (<a href='results.php?course=$course_code&exerciseId=$row->eid&status=2'>$langView</a>)</div>";
        }
    }
    $tool_content .= action_bar(array(
        array('title' => $langNewEx,
            'url' => "admin.php?course=$course_code&amp;NewExercise=Yes",
            'icon' => 'fa-plus-circle',
            'level' => 'primary-label',
            'button-class' => 'btn-success'
        ),
        array('title' => $langQuestionCats,
            'url' => "question_categories.php?course=$course_code",
            'icon' => 'fa-cubes',
            'level' => 'primary'
            ),        
        array('title' => $langQuestionPool,
            'url' => "question_pool.php?course=$course_code",
            'icon' => 'fa-university',
            'level' => 'primary'
            )
    ));

} else {
    $tool_content .= "";
}

if (!$nbrExercises) {
    $tool_content .= "<div class='alert alert-warning'>$langNoEx</div>";
} else {
    $maxpage = 1 + intval($num_of_ex / $limitExPage);
    if ($maxpage > 0) {
        $prevpage = $page - 1;
        $nextpage = $page + 1;
        if ($prevpage >= 0) {
            $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;page=$prevpage'>&lt;&lt; $langPreviousPage</a>&nbsp;";
        }
        if ($nextpage < $maxpage) {
            $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;page=$nextpage'>$langNextPage &gt;&gt;</a>";
        }
    }

    $tool_content .= "<div class='table-responsive'><table class='table-default'><tr>";

    // shows the title bar only for the administrator
    if ($is_editor) {
        $tool_content .= "
                <th>$langExerciseName</th>
                <th class='text-center'>$langResults</th>
                <th class='text-center'>".icon('fa-gears')."</th>
              </tr>";
    } else { // student view
        $tool_content .= "
                <th>$langExerciseName</th>
                <th class='text-center'>$langExerciseStart / $langExerciseEnd</th>
                <th class='text-center'>$langExerciseConstrain</th>
                <th class='text-center'>$langExerciseAttemptsAllowed</th>
                <th class='text-center'>$langResults</th>
              </tr>";
    }
    // display exercise list
    $k = 0;
    foreach ($result as $row) {
        
        $tool_content .= "<tr ".($is_editor && !$row->active ? "class='not_visible'" : "").">";
        $row->description = standard_text_escape($row->description);

        // prof only
        if ($is_editor) {
            if (!empty($row->description)) {
                $descr = "<br/>$row->description";
            } else {
                $descr = '';
            }
            $tool_content .= "<td><a ".(isset($paused_exercises_ids) && in_array($row->id,$paused_exercises_ids)?'class="paused_exercise"':'')." href='exercise_submit.php?course=$course_code&amp;exerciseId={$row->id}'>" . q($row->title) . "</a>$descr</td>";
            $eid = $row->id;
			$NumOfResults = Database::get()->querySingle("SELECT COUNT(*) as count FROM exercise_user_record WHERE eid = ?d", $eid)->count;

            if ($NumOfResults) {
                $tool_content .= "<td class='text-center'><a href='results.php?course=$course_code&amp;exerciseId={$row->id}'>$langExerciseScores1</a> |
				<a href='csv.php?course=$course_code&amp;exerciseId=" . $row->id . "' target=_blank>" . $langExerciseScores3 . "</a></td>";
            } else {
                $tool_content .= "<td class='text-center'>	-&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;- </td>";
            }
            $langModify_temp = htmlspecialchars($langModify);
            $langConfirmYourChoice_temp = addslashes(htmlspecialchars($langConfirmYourChoice));
            $langDelete_temp = htmlspecialchars($langDelete);
            
            $tool_content .= "<td class='option-btn-cell'>".action_button(array(
                    array('title' => $langModify,
                          'url' => "admin.php?course=$course_code&amp;exerciseId=$row->id",
                          'icon' => 'fa-edit'),
                    array('title' => $langPurgeExercise,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;choice=delete&amp;exerciseId=$row->id",
                          'icon' => 'fa-times',
                          'class' => 'delete',
                          'confirm' => $langConfirmPurgeExercise),
                    array('title' => $langPurgeExerciseResults,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;choice=purge&amp;exerciseId=$row->id",
                          'icon' => 'fa-trash',
                          'confirm' => $langConfirmPurgeExerciseResults),
                    array('title' => $langVisible,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;".($row->active ? "choice=disable" : "choice=enable").(isset($page) ? "&amp;page=$page" : "")."&amp;exerciseId=" . $row->id,
                          'icon' => $row->active ? 'fa-eye': 'fa-eye-slash'),
                    array('title' => $langResourceAccess,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;".($row->public ? "choice=limited" : "choice=public")."&amp;exerciseId=$row->id",
                          'icon' => $row->public ? 'fa-unlock' : 'fa-lock',
                          'show' => course_status($course_id) == COURSE_OPEN),
                    array('title' => $langExerciseStats,
                          'url' => "exercise_stats.php?course=$course_code&amp;exerciseId=$row->id",
                          'icon' => 'fa-pie-chart'),
                    array('title' => $langCreateDuplicate,
                          'url' => "index.php?course=$course_code&amp;choice=clone&amp;exerciseId=$row->id",
                          'icon' => 'fa-copy')                
                    ))."</td></tr>";
            
        // student only
    } else {
            if (!resource_access($row->active, $row->public)) {
                continue;
            }
            $currentDate = date("Y-m-d H:i");
			//These convertions do not seem to be necessary
            $temp_StartDate = mktime(substr($row->start_date, 11, 2), substr($row->start_date, 14, 2), 0, substr($row->start_date, 5, 2), substr($row->start_date, 8, 2), substr($row->start_date, 0, 4));
            $temp_EndDate = mktime(substr($row->end_date, 11, 2), substr($row->end_date, 14, 2), 0, substr($row->end_date, 5, 2), substr($row->end_date, 8, 2), substr($row->end_date, 0, 4));
            $currentDate = mktime(substr($currentDate, 11, 2), substr($currentDate, 14, 2), 0, substr($currentDate, 5, 2), substr($currentDate, 8, 2), substr($currentDate, 0, 4));
        
            if (($currentDate >= $temp_StartDate) && ($currentDate <= $temp_EndDate)) {
                $tool_content .= "<td>".icon('fa-angle-double-right')." <a href='exercise_submit.php?course=$course_code&amp;exerciseId=$row->id'>" . q($row->title) . "</a>";
             } elseif ($currentDate <= $temp_StartDate) { // exercise has not yet started
                $tool_content .= "<td class='not_visible'>".icon('fa-angle-double-right')." " . q($row->title) . "&nbsp;&nbsp;";
            } else { // exercise has expired
                $tool_content .= "<td>".icon('fa-angle-double-right')." " . q($row->title) . "&nbsp;&nbsp;(<font color='red'>$m[expired]</font>)";
            }
            $tool_content .= "<br />" . $row->description . "</td><td class='smaller' align='center'>
                                " . nice_format(date("Y-m-d H:i", strtotime($row->start_date)), true) . " /
                                " . nice_format(date("Y-m-d H:i", strtotime($row->end_date)), true) . "</td>";          														  
            if ($row->time_constraint > 0) {
                $tool_content .= "<td class='text-center'>{$row->time_constraint} $langExerciseConstrainUnit</td>";
            } else {
                $tool_content .= "<td class='text-center'> - </td>";
            }
            // how many attempts we have.
            $currentAttempt = Database::get()->querySingle("SELECT COUNT(*) AS count FROM exercise_user_record WHERE eid = ?d AND uid = ?d", $row->id, $uid)->count;            
            if ($row->attempts_allowed > 0) {
                $tool_content .= "<td class='text-center'>$currentAttempt/$row->attempts_allowed</td>";
            } else {
                $tool_content .= "<td class='text-center'> - </td>";
            }
            if ($row->score) {
                // user last exercise score
                $attempts = Database::get()->querySingle("SELECT COUNT(*) AS count
                                            FROM exercise_user_record WHERE uid = ?d
                                            AND eid = ?d", $uid, $row->id)->count;
                if ($attempts > 0) {
                    $tool_content .= "<td class='text-center'><a href='results.php?course=$course_code&amp;exerciseId={$row->id}'>$langExerciseScores1</a></td>";
                } else {
                    $tool_content .= "<td class='text-center''>&dash;</td>";
                }
            $tool_content .= "</tr>";
            } else {
                $tool_content .= "<td class='text-center'>$langNotAvailable</td>";
            }
        }
        // skips the last exercise, that is only used to know if we have or not to create a link "Next page"
        if ($k + 1 == $limitExPage) {
            break;
        }
        $k++;
    } // end while()
    $tool_content .= "</table></div>";
}
add_units_navigation(TRUE);
$head_content .= "<script type='text/javascript'>
    $(document).ready(function(){
        $('.paused_exercise').click(function(e){
            e.preventDefault();
            var link = $(this).attr('href');
            bootbox.confirm('$langTemporarySaveNotice2', function(result) {
                if(result) {
                    document.location.href = link;
                }
            });             
        });
    });
</script>";
draw($tool_content, 2, null, $head_content);
