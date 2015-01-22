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


/* ===========================================================================
  learningPathAdmin.php
  @authors list: Thanos Kyritsis <atkyritsis@upnet.gr>

  based on Claroline version 1.7 licensed under GPL
  copyright (c) 2001, 2006 Universite catholique de Louvain (UCL)

  original file: learningPathAdmin.php Revision: 1.40.2.1

  Claroline authors: Piraux Sebastien <pir@cerdecam.be>
  Lederer Guillaume <led@cerdecam.be>
  ==============================================================================
  @Description: This file is available only to the course admin

  It allow course admin to :
  - change learning path name
  - change learning path comment
  - links to
  - create empty module
  - use document as module
  - use exercise as module
  - use link as module
  - use course description as module
  - re-use a module of the same course
  - remove modules from learning path (it doesn't delete it ! )
  - change locking , visibility, order
  - access to config page of modules in this learning path

  @Comments:
  ==============================================================================
 */

$require_current_course = TRUE;

require_once '../../include/baseTheme.php';
require_once 'include/lib/learnPathLib.inc.php';
require_once 'include/lib/fileDisplayLib.inc.php';
require_once 'include/log.php';

$body_action = '';
$dialogBox = '';

if (!add_units_navigation()) {
    $pageName = $langAdm;
    $navigation[] = array("url" => "index.php?course=$course_code", "name" => $langLearningPaths);
}

// $_SESSION
if (isset($_GET['path_id']) && $_GET['path_id'] > 0) {
    $_SESSION['path_id'] = intval($_GET['path_id']);
}

// get user out of here if he is not allowed to edit
if (!$is_editor) {
    if (isset($_SESSION['path_id'])) {
        header("Location: ./learningPath.php?course=$course_code&path_id=" . $_SESSION['path_id']);
    } else {
        header("Location: ./index.php?course=$course_code");
    }
    exit();
}

load_js('tools.js');

$cmd = ( isset($_REQUEST['cmd']) ) ? $_REQUEST['cmd'] : '';

switch ($cmd) {
    // MODULE DELETE
    case "delModule" :
        //--- BUILD ARBORESCENCE OF MODULES IN LEARNING PATH
        $sql = "SELECT M.*, LPM.*
                FROM `lp_module` AS M, `lp_rel_learnPath_module` AS LPM
                WHERE M.`module_id` = LPM.`module_id`
                AND LPM.`learnPath_id` = ?d
                AND M.`course_id` = ?d
                ORDER BY LPM.`rank` ASC";
        $result = Database::get()->queryArray($sql, $_SESSION['path_id'], $course_id);

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
            $extendedList[] = $modar;
        }

        //-- delete module cmdid and his children if it is a label
        // get the modules tree ( cmdid module and all its children)
        //$temp[0] = get_module_tree( build_element_list($extendedList, 'parent', 'learnPath_module_id'), $_REQUEST['cmdid'] , 'learnPath_module_id');
        $temp[0] = get_module_tree(build_element_list($extendedList, 'parent', 'learnPath_module_id'), $_REQUEST['cmdid'], 'learnPath_module_id');
        // delete the tree
        delete_module_tree($temp);

        break;

    // VISIBILITY COMMAND
    case "mkVisibl" :
    case "mkInvisibl" :
        $visibility = ($cmd == "mkVisibl") ? 1 : 0;
        //--- BUILD ARBORESCENCE OF MODULES IN LEARNING PATH
        $sql = "SELECT M.*, LPM.*
                FROM `lp_module` AS M, `lp_rel_learnPath_module` AS LPM
                WHERE M.`module_id` = LPM.`module_id`
                AND LPM.`learnPath_id` = ?d
                AND M.`course_id` = ?d
                ORDER BY LPM.`rank` ASC";
        $result = Database::get()->queryArray($sql, $_SESSION['path_id'], $course_id);

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
            $extendedList[] = $modar;
        }

        //-- set the visibility for module cmdid and his children if it is a label
        // get the modules tree ( cmdid module and all its children)
        $temp[0] = get_module_tree(build_element_list($extendedList, 'parent', 'learnPath_module_id'), $_REQUEST['cmdid']);
        // change the visibility according to the new father visibility
        set_module_tree_visibility($temp, $visibility);

        break;

    // ACCESSIBILITY COMMAND
    case "mkBlock" :
    case "mkUnblock" :
        $blocking = ($cmd == "mkBlock") ? 'CLOSE' : 'OPEN';
        Database::get()->query("UPDATE `lp_rel_learnPath_module`
                SET `lock` = ?s
                WHERE `learnPath_module_id` = ?d
                AND `lock` != ?s", $blocking, $_REQUEST['cmdid'], $blocking);
        break;

    // ORDER COMMAND
    case "changePos" :
        // changePos form sent
        if (isset($_POST["newPos"]) && $_POST["newPos"] != "") {
            // get order of parent module
            $movedModule = Database::get()->querySingle("SELECT *
                    FROM `lp_rel_learnPath_module`
                    WHERE `learnPath_module_id` = ?d LIMIT 1", $_REQUEST['cmdid']);

            // if origin and target are the same ... cancel operation
            if ($movedModule->learnPath_module_id == $_POST['newPos']) {
                $dialogBox .= $langWrongOperation;
            } else {
                //--
                // select max order
                // get the max rank of the children of the new parent of this module
                $order = 1 + intval(Database::get()->querySingle("SELECT MAX(`rank`) AS max
                        FROM `lp_rel_learnPath_module`
                        WHERE `parent` = ?d
                        AND `learnPath_id` = ?d", $_POST['newPos'], $_SESSION['path_id'])->max);

                // change parent module reference in the moved module and set order (added to the end of target group)
                Database::get()->query("UPDATE `lp_rel_learnPath_module`
                        SET `parent` = ?d,
                            `rank` = ?d
                        WHERE `learnPath_module_id` = ?d
                        AND `learnPath_id` = ?d", $_POST['newPos'], $order, $_REQUEST['cmdid'], $_SESSION['path_id']);
                $dialogBox .= "<p class=\"success\">$langModuleMoved</p>";
            }
        } else {  // create form requested
            // create elementList
            $sql = "SELECT M.*, LPM.*
                    FROM `lp_module` AS M, `lp_rel_learnPath_module` AS LPM
                    WHERE M.`module_id` = LPM.`module_id`
                      AND LPM.`learnPath_id` = ?d
                      AND M.`contentType` = ?s
                      AND M.`course_id` = ?d
                    ORDER BY LPM.`rank` ASC";
            $result = Database::get()->queryArray($sql, $_SESSION['path_id'], CTLABEL_, $course_id);

            $extendedList = array();
            $modar = array();
            foreach ($result as $list) {
                // this array will display target for the "move" command
                // so don't add the module itself build_element_list will ignore all childre so that
                // children of the moved module won't be shown, a parent cannot be a child of its own children
                if ($list->learnPath_module_id != $_REQUEST['cmdid']) {
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
                    $extendedList[] = $modar;
                }
            }

            // build the array that will be used by thebuild_nested_select_menu function
            $elementList = array();
            $elementList = build_element_list($extendedList, 'parent', 'learnPath_module_id');

            $topElement['name'] = $langRoot;
            $topElement['value'] = 0;    // value is required by claro_nested_build_select_menu
            if (!is_array($elementList)) {
                $elementList = array();
            }
            array_unshift($elementList, $topElement);

            // get infos about the moved module
            $moduleInfos = Database::get()->querySingle("SELECT M.`name`
                    FROM `lp_rel_learnPath_module` AS LPM,
                         `lp_module` AS M
                    WHERE LPM.`module_id` = M.`module_id`
                      AND LPM.`learnPath_module_id` = ?d
                      AND M.`course_id` = ?d", $_REQUEST['cmdid'], $course_id);

            $displayChangePosForm = true; // the form code comes after name and comment boxes section
        }
        break;

    case "moveUp" :
        $thisLPMId = $_REQUEST['cmdid'];
        $sortDirection = "DESC";
        break;

    case "moveDown" :
        $thisLPMId = $_REQUEST['cmdid'];
        $sortDirection = "ASC";
        break;

    case "createLabel" :
        // create form sent
        if (isset($_REQUEST["newLabel"]) && trim($_REQUEST["newLabel"]) != "") {
            // determine the default order of this Learning path ( a new label is a root child)
            $order = 1 + intval(Database::get()->querySingle("SELECT MAX(`rank`) AS max
                    FROM `lp_rel_learnPath_module`
                    WHERE `parent` = 0
                    AND `learnPath_id` = ?d", $_SESSION['path_id'])->max);

            // create new module
            // request ID of the last inserted row (module_id in $TABLEMODULE) to add it in $TABLELEARNPATHMODULE
            $thisInsertedModuleId = Database::get()->query("INSERT INTO `lp_module`
                   (`course_id`, `name`, `comment`, `contentType`, `launch_data`)
                   VALUES (?d, ?s, '', ?s, '')", $course_id, $_POST['newLabel'], CTLABEL_)->lastInsertID;

            // create new learning path module
            Database::get()->query("INSERT INTO `lp_rel_learnPath_module`
                   (`learnPath_id`, `module_id`, `specificComment`, `rank`, `parent`)
                   VALUES (?d, ?d, '', ?d, 0)", $_SESSION['path_id'], $thisInsertedModuleId, $order);
        } else {  // create form requested
            $displayCreateLabelForm = true; // the form code comes after name and comment boxes section
        }
        break;

    default:
        break;
}

// IF ORDER COMMAND RECEIVED
// CHANGE ORDER

if (isset($sortDirection) && $sortDirection) {

    // get list of modules with same parent as the moved module
    $sql = "SELECT LPM.`learnPath_module_id`, LPM.`rank`
            FROM (`lp_rel_learnPath_module` AS LPM, `lp_learnPath` AS LP)
              LEFT JOIN `lp_rel_learnPath_module` AS LPM2 ON LPM2.`parent` = LPM.`parent`
            WHERE LPM2.`learnPath_module_id` = ?d
              AND LPM.`learnPath_id` = LP.`learnPath_id`
              AND LP.`learnPath_id` = ?d
              AND LP.`course_id` = ?d
            ORDER BY LPM.`rank` $sortDirection";

    $listModules = Database::get()->queryArray($sql, $thisLPMId, $_SESSION['path_id'], $course_id);

    // LP = learningPath
    foreach ($listModules as $module) {
        // STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER.
        //          COMMIT ORDER SWAP ON THE DB

        if (isset($thisLPMOrderFound) && $thisLPMOrderFound == true) {

            $nextLPMId = $module->learnPath_module_id;
            $nextLPMOrder = $module->rank;

            Database::get()->query("UPDATE `lp_rel_learnPath_module`
                    SET `rank` = ?d
                    WHERE `learnPath_module_id` =  ?d
                    AND `learnPath_id` = ?d", $nextLPMOrder, $thisLPMId, $_SESSION['path_id']);

            Database::get()->query("UPDATE `lp_rel_learnPath_module`
                    SET `rank` = ?d
                    WHERE `learnPath_module_id` =  ?d
                    AND `learnPath_id` = ?d", $thisLPMOrder, $nextLPMId, $_SESSION['path_id']);

            break;
        }

        // STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT
        if ($module->learnPath_module_id == $thisLPMId) {
            $thisLPMOrder = $module->rank;
            $thisLPMOrderFound = true;
        }
    }
}

$tool_content .="<fieldset><legend>$langLearningPathData</legend><table class='table-default'>";


//############################ LEARNING PATH NAME BOX ################################\\
$tool_content .="<tr><th width='70'>$langTitle:</th>";

if ($cmd == "updateName") {
    $tool_content .= disp_message_box(nameBox(LEARNINGPATH_, UPDATE_, $langModify));
} else {
    $tool_content .= "<td>" . nameBox(LEARNINGPATH_, DISPLAY_);
}

$tool_content .= "</td></tr>";

//############################ LEARNING PATH COMMENT BOX #############################\\
$tool_content .="
    <tr>
      <th width=\"90\">$langComments:</th>
      <td>";
if ($cmd == "updatecomment") {
    $tool_content .= commentBox(LEARNINGPATH_, UPDATE_);
} elseif ($cmd == "delcomment") {
    $tool_content .= commentBox(LEARNINGPATH_, DELETE_);
} else {
    $tool_content .= commentBox(LEARNINGPATH_, DISPLAY_);
}

$tool_content .= "</td>
    </tr>
    </table>
    </fieldset>
    <fieldset>
    <legend>$langLearningPathConfigure</legend>
    <table class='table-default'>";

// -------------------- create label -------------------
if (isset($displayCreateLabelForm) && $displayCreateLabelForm) {
    $tool_content .= "
    <tr>
      <th width=\"200\">$langLabel:</th>
      <td>
        <form action=\"" . $_SERVER['SCRIPT_NAME'] . "?course=$course_code\" method=\"post\">
          <label for=\"newLabel\">" . $langNewLabel . ": </label>&nbsp;
          <input type=\"text\" name=\"newLabel\" id=\"newLabel\" maxlength=\"255\" / size=\"30\" >
          <input type=\"hidden\" name=\"cmd\" value=\"createLabel\" />
          <input type=\"submit\" value=\"" . $langCreate . "\" />
        </form>
      </td>
    </tr>";
}


// --------------- learning path course admin links ------------------------------

if (!isset($displayCreateLabelForm)) {

    $tool_content .="
    <tr>
      <th width=\"200\">$langLabel:</th>
      <td><a href=\"" . $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=createLabel\">" . $langCreate . "</a></td>
    </tr>";
}
$tool_content .="
    <tr>
      <th rowspan=\"2\">$langLearningObjects:</th>
      <td>";
$tool_content .= "$langAdd: <a href=\"insertMyDoc.php?course=$course_code\" title=\"$langDocumentAsModule\">" .
        $langDocumentAsModuleLabel . "</a> | <a href=\"insertMyExercise.php?course=$course_code\" title=\"$langExerciseAsModule\">" .
        $langExerciseAsModuleLabel . "</a> | <a href=\"insertMyLink.php?course=$course_code\" title=\"$langLinkAsModule\">" .
        $langLinkAsModuleLabel . "</a> | <a href=\"insertMyMedia.php?course=$course_code\" title=\"$langMediaAsModule\">" .
        $langMediaAsModuleLabel . "</a> | <a href=\"insertMyDescription.php?course=$course_code\" title=\"$langCourseDescriptionAsModule\">" .
        $langCourseDescriptionAsModuleLabel . "</a>
      </td>
    </tr>";

$tool_content .="
    <tr>
      <td>$langReuse: <a href=\"insertMyModule.php?course=$course_code\" title=\"$langModuleOfMyCourse\">" . $langModuleOfMyCourse . "</a>
      </td>
    </tr>";

$tool_content .="
    </table>
    </fieldset>";

if (isset($displayChangePosForm) && $displayChangePosForm) {
    $dialogBox = "
    <table class=\"table-default\">
    <tr>
      <th>" . $langMove . ":</th>
      <td>
        <form action=\"" . $_SERVER['SCRIPT_NAME'] . "?course=$course_code\" method=\"post\">\"<b>" . $moduleInfos->name . "</b>\" &nbsp;" . $langTo . ":&nbsp;&nbsp;";
    // build select input - $elementList has been declared in the previous big cmd case
    $dialogBox .= build_nested_select_menu("newPos", $elementList);
    $dialogBox .= "
         <input type=\"hidden\" name=\"cmd\" value=\"changePos\" />
         <input type=\"hidden\" name=\"cmdid\" value=\"" . $_REQUEST['cmdid'] . "\" />
         <input type=\"submit\" value=\"" . $langOk . "\" />
        </form>
      </td>
    </tr>
    </table>";
}


//####################################################################################\\
//############################### DIALOG BOX SECTION #################################\\
//####################################################################################\\

if (isset($dialogBox) && $dialogBox != "") {
    $tool_content .= $dialogBox;
}


$tool_content .="
     <p class='sub_title1'>$langLearningPathStructure:</p>
     ";

//  -------------------------- learning path list content ----------------------------
$sql = "SELECT M.*, LPM.*, A.`path`
        FROM (`lp_module` AS M,
             `lp_rel_learnPath_module` AS LPM)
        LEFT JOIN `lp_asset` AS A ON M.`startAsset_id` = A.`asset_id`
        WHERE M.`module_id` = LPM.`module_id`
          AND LPM.`learnPath_id` = ?d
          AND M.`course_id` = ?d
        ORDER BY LPM.`rank` ASC";

$result = Database::get()->queryArray($sql, $_SESSION['path_id'], $course_id);

if (count($result) == 0) {
    $tool_content .= "<div class='alert alert-warning'>$langNoModule</div>";
    draw($tool_content, 2, null, $head_content, $body_action);
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
    $extendedList[] = $modar;
}

// build the array of modules
// build_element_list return a multi-level array, where children is an array with all nested modules
// build_display_element_list return an 1-level array where children is the deep of the module

$flatElementList = build_display_element_list(build_element_list($extendedList, 'parent', 'learnPath_module_id'));
$i = 0;

// look for maxDeep
$maxDeep = 1; // used to compute colspan of <td> cells
for ($i = 0; $i < sizeof($flatElementList); $i++) {
    if ($flatElementList[$i]['children'] > $maxDeep) {
        $maxDeep = $flatElementList[$i]['children'];
    }
}

// -------------------------- learning path list header ----------------------------
$tool_content .="
    <div class='table-responsive'>
    <table class=\"table-default\">
    <tr>
      <th colspan=\"" . ($maxDeep + 1) . "\"><div align=\"left\">&nbsp;" . $langContents . "</div></th>
      <th><div align=\"center\">" . icon('fa-gears') . "</div></th>
    </tr>";

// -------------------- LEARNING PATH LIST DISPLAY ---------------------------------
$ind = 1;
foreach ($flatElementList as $module) {
    //-------------visibility-----------------------------
    if ($module['visible'] == 0) {
        if ($is_editor) {
            $style = " class='invisible'";
            $image_bullet = "off";
        } else {
            continue; // skip the display of this file
        }
    } else {
        $style = "";
        $image_bullet = "on"; // to be revised as it not used in font awsome
    }
    $spacingString = "";
    for ($i = 0; $i < $module['children']; $i++) {
        $spacingString .= "
      <td width='5'>&nbsp;</td>";
    }

    $colspan = $maxDeep - $module['children'] + 1;

    if ($ind % 2 == 0) {
        $classvis = 'class="even"';
    } else {
        $classvis = 'class="odd"';
    }


    $tool_content .= "
    <tr $classvis " . $style . ">" . $spacingString . "
      <td colspan=\"" . $colspan . "\">&nbsp;&nbsp;&nbsp;";

    if ($module['contentType'] == CTLABEL_) { // chapter head
        $tool_content .= "<font " . $style . " style=\"font-weight: bold\">" . htmlspecialchars($module['name']) . "</font>";
    } else { // module
        if ($module['contentType'] == CTEXERCISE_) {
            $moduleImg = "fa-pencil-square-o";
        } else if ($module['contentType'] == CTLINK_) {
            $moduleImg = "fa-link";
        } else if ($module['contentType'] == CTCOURSE_DESCRIPTION_) {
            $moduleImg = "fa-info-circle";
        } else if ($module['contentType'] == CTDOCUMENT_) {
            $moduleImg = "fa-folder-open-o";
        } else if ($module['contentType'] == CTMEDIA_ || $module['contentType'] == CTMEDIALINK_) {
            $moduleImg = "fa-film";
        } else {
            $moduleImg = choose_image(basename($module['path']));
        }

        $contentType_alt = selectAlt($module['contentType']);
        $tool_content .= "<span style=\"vertical-align: middle;\">" . icon($moduleImg, $contentType_alt) . "</span>&nbsp;<a href=\"viewer.php?course=$course_code&amp;path_id=" . (int) $_SESSION['path_id'] . "&amp;module_id=" . $module['module_id'] . "\"" . $style . ">" . htmlspecialchars($module['name']) . "</a>";
    }
    $tool_content .= "</td>"; // end of td of module name


    if ($module['contentType'] == CTSCORM_ || $module['contentType'] == CTSCORMASSET_) {
        $del_conf_text = clean_str_for_javascript($langAreYouSureToRemoveSCORM);
    } else if ($module['contentType'] == CTLABEL_) {
        $del_conf_text = clean_str_for_javascript($langAreYouSureToRemoveLabel);
    } else {
        $del_conf_text = clean_str_for_javascript($langAreYouSureToRemoveStd);
    }
    $tool_content .= "<td class='option-btn-cell'>" .
            action_button(array(
                // LOCK
                array('title' => $langBlock,
                    'url' => $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=mkBlock&amp;cmdid=" . $module['learnPath_module_id'],
                    'icon' => 'fa-unlock',
                    'show' => $module['lock'] == 'OPEN'),
                array('title' => $langAltMakeNotBlocking,
                    'url' => $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=mkUnblock&amp;cmdid=" . $module['learnPath_module_id'],
                    'icon' => 'fa-lock',
                    'show' => $module['lock'] == 'CLOSE'),
                array('title' => $langModify, // Modify command / go to other page
                    'url' => "module.php?course=$course_code&amp;module_id=" . $module['module_id'],
                    'icon' => 'fa-edit'),
                array('title' => $langRemove, // DELETE ROW. In case of SCORM module, the pop-up window to confirm must be different as the action will be different on the server
                    'url' => $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=delModule&amp;cmdid=" . $module['learnPath_module_id'],
                    'class' => 'delete',
                    'confirm' => $del_conf_text + " " + $module['name'],
                    'icon' => 'fa-times'),
                // VISIBILITY
                array('title' => $langVisible,
                    'url' => $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=mkVisibl&amp;cmdid=" . $module['module_id'],
                    'icon' => 'fa-eye-slash',
                    'show' => $module['visible'] == 0),
                array('title' => $langVisible,
                    'url' => $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=mkInvisibl&amp;cmdid=" . $module['module_id'],
                    'icon' => 'fa-eye',
                    'confirm' => $module['lock'] == 'CLOSE' ? $langAlertBlockingMakedInvisible : null,
                    'confirm_title' => "",
                    'confirm_button' => $langAccept,
                    'show' => $module['visible'] != 0),
                array('title' => $langMove, // DISPLAY CATEGORY MOVE COMMAND
                    'url' => $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=changePos&amp;cmdid=" . $module['learnPath_module_id'],
                    'icon' => 'fa-mail-forward'),
                array('title' => $langUp, // DISPLAY MOVE UP COMMAND only if it is not the top learning path
                    'url' => $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=moveUp&amp;cmdid=" . $module['learnPath_module_id'],
                    'level' => 'primary',
                    'icon' => 'fa-arrow-up',
                    'disabled' => !$module['up']),
                array('title' => $langDown, // DISPLAY MOVE DOWN COMMAND only if it is not the bottom learning path
                    'url' => $_SERVER['SCRIPT_NAME'] . "?course=$course_code&amp;cmd=moveDown&amp;cmdid=" . $module['learnPath_module_id'],
                    'level' => 'primary',
                    'icon' => 'fa-arrow-down',
                    'disabled' => !$module['down'])                
            )) .
            "</td>";


    $tool_content .= "</tr>";

    $ind++;
} // end of foreach

$tool_content .= "</table></div><br>";
draw($tool_content, 2, null, $head_content, $body_action);
