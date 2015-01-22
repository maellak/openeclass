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

/* ===========================================================================
  toc.php
  @authors list: Yannis Exidaridis <jexi@noc.uoa.gr>
  Alexandros Diamantidis <adia@noc.uoa.gr>
  Thanos Kyritsis <atkyritsis@upnet.gr>
  ==============================================================================
  @Description: Aristerh sthlh me ta Table Of Contents mias grammhs ma8hshs,
  dhladh lista me ola ta modules ths.

  @Comments:
  ==============================================================================
 */

$require_current_course = TRUE;
require_once '../../include/baseTheme.php';
require_once 'include/lib/learnPathLib.inc.php';
require_once 'include/lib/fileDisplayLib.inc.php';

echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>
<head><title>-</title>
    <meta http-equiv='Content-Type' content='text/html; charset=$charset'>
    <link href='{$urlAppend}template/$theme/lp.css' rel='stylesheet' type='text/css' />
</head>
<body style='padding-right: 5px;'>
<div class='menu_left'>";

if ($uid) {
    $uidCheckString = "AND UMP.`user_id` = " . intval($uid);
} else {
    // anonymous
    $uidCheckString = "AND UMP.`user_id` IS NULL ";
}

//  -------------------------- learning path list content ----------------------------
$sql = "SELECT M.*, LPM.*, A.`path`, UMP.`lesson_status`, UMP.`credit`
        FROM (`lp_module` AS M,
             `lp_rel_learnPath_module` AS LPM)
        LEFT JOIN `lp_asset` AS A ON M.`startAsset_id` = A.`asset_id`
        LEFT JOIN `lp_user_module_progress` AS UMP
           ON UMP.`learnPath_module_id` = LPM.`learnPath_module_id`
           " . $uidCheckString . "
        WHERE M.`module_id` = LPM.`module_id`
          AND LPM.`learnPath_id` = ?d
          AND M.`course_id` = ?d
        ORDER BY LPM.`rank` ASC";
$result = Database::get()->queryArray($sql, $_SESSION['path_id'], $course_id);

if (count($result) == 0) {
    echo "<div class='alert alert-warning'>$langNoModule</div>";
    exit();
}

$extendedList = array();
$modar = array();
foreach ($result as $list) {
    $modar['module_id'] = $list->module_id;
    $modar['course_id'] = $list->course_id;
    $modar['name'] = $list->name;
    $modar['comment'] = $list->comment;
    $modar['accessibility'] = $list->accessibility;
    $modar['startAsset_id'] = $list->startAsset_id;
    $modar['contentType'] = $list->contentType;
    $modar['launch_data'] = $list->launch_data;
    $modar['learnPath_module_id'] = $list->learnPath_module_id;
    $modar['learnPath_id'] = $list->learnPath_id;
    $modar['lock'] = $list->lock;
    $modar['visible'] = $list->visible;
    $modar['specificComment'] = $list->specificComment;
    $modar['rank'] = $list->rank;
    $modar['parent'] = $list->parent;
    $modar['raw_to_pass'] = $list->raw_to_pass;
    $modar['path'] = $list->path;
    $modar['lesson_status'] = $list->lesson_status;
    $modar['credit'] = $list->credit;
    $extendedList[] = $modar;
}

// build the array of modules
// build_element_list return a multi-level array, where children is an array with all nested modules
// build_display_element_list return an 1-level array where children is the deep of the module

$flatElementList = build_display_element_list(build_element_list($extendedList, 'parent', 'learnPath_module_id'));
$i = 0;
$is_blocked = false;

// look for maxDeep
$maxDeep = 1; // used to compute colspan of <td> cells
for ($i = 0; $i < sizeof($flatElementList); $i++) {
    if ($flatElementList[$i]['children'] > $maxDeep) {
        $maxDeep = $flatElementList[$i]['children'];
    }
}

// -------------------------- learning path list header ----------------------------
echo "<ul><li class='category'>$langContents</li>";

// ----------------------- LEARNING PATH LIST DISPLAY ---------------------------------
foreach ($flatElementList as $module) {
    //-------------visibility-----------------------------
    if ($module['visible'] == 0 || $is_blocked) {
        if ($is_editor) {
            $style = " class='invisible'";
            $image_bullet = "off";
        } else {
            continue; // skip the display of this file
        }
    } else {
        $style = "";
        $image_bullet = "on"; //image bullet no longer used in fa icons. Should be checked.
    }

    // indent a child based on label ownership
    $marginIndent = 0;
    for ($i = 0; $i < $module['children']; $i++) {
        $marginIndent += 10;
    }

    if ($module['contentType'] == CTLABEL_) { // chapter head
        echo "<li style=\"margin-left: " . $marginIndent . "px;\"><font " . $style . " style=\"font-weight: bold\">" . htmlspecialchars($module['name']) . "</font></li>";
    } else { // module
        if ($module['contentType'] == CTEXERCISE_) {
            $moduleImg = "exercise_$image_bullet";
        } else if ($module['contentType'] == CTLINK_) {
            $moduleImg = "links_$image_bullet";
        } else if ($module['contentType'] == CTCOURSE_DESCRIPTION_) {
            $moduleImg = "description_$image_bullet";
        } else if ($module['contentType'] == CTDOCUMENT_) {
            $moduleImg = choose_image(basename($module['path']));
        } else if ($module['contentType'] == CTSCORM_ || $module['contentType'] == CTSCORMASSET_) { // eidika otan einai scorm module, deixnoume allo eikonidio pou exei na kanei me thn proodo
            $moduleImg = "lp_check";
        } else if ($module['contentType'] == CTMEDIA_ || $module['contentType'] == CTMEDIALINK_) {
            $moduleImg = "videos_on";
        } else {
            $moduleImg = choose_image(basename($module['path']));
        }

        $contentType_alt = selectAlt($module['contentType']);

        // eikonidio pou deixnei an perasame h oxi to sygkekrimeno module
        unset($imagePassed);
        if ($module['credit'] == 'CREDIT' || $module['lesson_status'] == 'COMPLETED' || $module['lesson_status'] == 'PASSED') {
            if ($module['contentType'] == CTSCORM_ || $module['contentType'] == CTSCORMASSET_) {
                $moduleImg = 'tick';
            } else {
                $imagePassed = icon_old_style('tick', $module['lesson_status']);
            }
        }

        if (($module['contentType'] == CTSCORM_ || $module['contentType'] == CTSCORMASSET_) && $module['lesson_status'] == 'FAILED') {
            $moduleImg = 'lp_failed';
        }

        echo "<li style=\"margin-left: " . $marginIndent . "px;\">" . icon_old_style($moduleImg, '');

        // emphasize currently displayed module or not
        if ($_SESSION['lp_module_id'] == $module['module_id']) {
            echo "<em>" . htmlspecialchars($module['name']) . "</em>";
        } else {
            echo "<a href='navigation/viewModule.php?course=$course_code&amp;viewModule_id=$module[module_id]'" . $style . " target='scoFrame'>" . htmlspecialchars($module['name']) . "</a>";
        }
        if (isset($imagePassed)) {
            echo "&nbsp;&nbsp;" . $imagePassed;
        }
        echo "</li>";

        if (($module['lock'] == 'CLOSE') && ($module['credit'] != 'CREDIT' || ($module['lesson_status'] != 'COMPLETED' && $module['lesson_status'] != 'PASSED'))) {
            $is_blocked = true;
        }
    }
} // end of foreach

echo "</ul></div></body></html>";
