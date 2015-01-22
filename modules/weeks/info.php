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
 *  @file info.php
 *  @brief edit course unit
 */

$require_current_course = true;
$require_editor = true;
$require_help = true;
$helpTopic = '';
require_once '../../include/baseTheme.php';

$pageName = $langEditWeek;

load_js('tools.js');

if (isset($_GET['edit'])) { // display form for editing course unit
    $id = $_GET['edit'];
    $cu = Database::get()->querySingle("SELECT id, title, comments FROM course_weekly_view WHERE id = ?d  AND course_id = ?d",$id,$course_id);
    if (!$cu) {
        $pageName = $langUnitUnknown;
        $tool_content .= "<div class='alert alert-danger'>$langUnknownResType</div>";
        draw($tool_content, 2, null, $head_content);
        exit;
    }
    $weektitle = " value='" . htmlspecialchars($cu->title, ENT_QUOTES) . "'";
    $weekdescr = $cu->comments;
    $week_id = $cu->id;    
} else {
    $pageName = $langAddWeek;
    $weekdescr = $weektitle = '';
}

$action = "${urlServer}courses/$course_code/";

$tool_content .= "<div class='form-wrapper'>
        <form class='form-horizontal' role='form' method='post' action='$action' onsubmit=\"return checkrequired(this, 'weektitle');\">";

if (isset($week_id)) {
    $tool_content .= "<input type='hidden' name='week_id' value='$week_id'>";
}

$tool_content .= "<div class='form-group'>
                    <label for='weekTitle' class='col-sm-2 control-label'>$langWeekTitle:</label>
                    <div class='col-sm-10'>
                        <input type='text' class='form-control' id='weekTitle' name='weektitle' $weektitle>
                    </div>
                  </div>
            <div class='form-group'>
                <label class='col-sm-2 control-label'>$langUnitDescr</label>
                <div class='col-sm-10'>
                    " . rich_text_editor('weekdescr', 10, 20, $weekdescr) . "
                </div>
            </div>
            <div class='form-group'>
                <div class='col-sm-offset-5 col-sm-12'>
                    <input class='btn btn-primary' type='submit' name='edit_submitW' value='" . q($langSubmit) . "'>
                </div>
            </div>            
        </form>
    </div>";

draw($tool_content, 2, null, $head_content);

