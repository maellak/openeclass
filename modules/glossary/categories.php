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


$require_current_course = true;
$require_help = true;
$helpTopic = 'Glossary';

require_once '../../include/baseTheme.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
ModalBoxHelper::loadModalBox();

$base_url = 'index.php?course=' . $course_code;
$cat_url = 'categories.php?course=' . $course_code;

$navigation[] = array('url' => $base_url, 'name' => $langGlossary);
$toolName = $langGlossary;

$categories = array();
$q = Database::get()->queryArray("SELECT id, name, description, `order`
                      FROM glossary_category WHERE course_id = ?d
                      ORDER BY name", $course_id);
foreach ($q as $cat) {
    $categories[intval($cat->id)] = $cat->name;
}

if ($is_editor) {
    load_js('tools.js');

    if (isset($_GET['add']) or isset($_GET['config']) or isset($_GET['edit'])) {
        if (isset($_GET['add'])) {
            $pageName = $langCategoryAdd;
        }
        if (isset($_GET['config'])) {
            $pageName = $langConfig;
        }
        if (isset($_GET['edit'])) {
            $pageName = $langCategoryMod;
        }
        
        $tool_content .= action_bar(array(
                array('title' => $langBack,
                      'url' => "$cat_url",
                      'icon' => 'fa-reply',
                      'level' => 'primary-label')));        
    } else {
        $tool_content .= action_bar(array(
                array('title' => $langAddGlossaryTerm,
                      'url' => "$base_url&amp;add=1",
                      'icon' => 'fa-plus-circle',
                      'level' => 'primary-label',
                      'button-class' => 'btn-success'),
                array('title' => $langCategoryAdd,
                      'url' => "$cat_url&amp;add=1",
                      'icon' => 'fa-plus-circle',
                      'level' => 'primary-label',
                      'button-class' => 'btn-success'),
                array('title' => $langConfig,
                      'url' => "$base_url&amp;config=1",                      
                      'icon' => 'fa-gear',
                      'level' => 'primary-label'),
                array('title' => "$langGlossaryToCsv (UTF8)",
                      'url' => "dumpglossary.php?course=$course_code",
                      'icon' => 'fa-file-excel-o'),
                array('title' => "$langGlossaryToCsv (Windows 1253)",
                      'url' => "dumpglossary.php?course=$course_code&amp;enc=1253",
                      'icon' => 'fa-file-excel-o'),
                array('title' => $langGlossaryTerms,
                      'url' => "index.php?course=$course_code",
                      'icon' => 'fa-tasks',
                      'level' => 'primary-label')
            ));        
    }

    if (isset($_POST['submit_category'])) {
        if (isset($_POST['category_id'])) {
            $category_id = intval($_POST['category_id']);
            $q = Database::get()->query("UPDATE glossary_category
                                              SET name = ?s,
                                                  description = ?s
                                              WHERE id = ?d AND course_id = ?d"
                    , $_POST['name'], $_POST['description'], $category_id, $course_id);
            $success_message = $langCategoryModded;
        } else {
            Database::get()->query("SELECT @new_order := (1 + IFNULL(MAX(`order`),0))
                                         FROM glossary_category WHERE course_id = ?d", $course_id);
            $q = Database::get()->query("INSERT INTO glossary_category
                                              SET name = ?s,
                                                  description = ?s,
                                                  course_id = ?d,
                                                  `order` = @new_order"
                    , $_POST['name'], $_POST['description'], $course_id);
            $category_id = $q->lastInsertID;
            $success_message = $langCategoryAdded;
        }
        if ($q and $q->affectedRows) {
            $categories[$category_id] = $_POST['name'];
            $tool_content .= "<div class='alert alert-success'>$success_message</div><br />";
        }
    }

    // Delete category, turn terms in it to uncategorized
    if (isset($_GET['delete'])) {
        $cat_id = $_GET['delete'];
        $q = Database::get()->query("DELETE FROM glossary_category
                                      WHERE id = ?d AND course_id = ?d", $cat_id, $course_id);
        if ($q and $q->affectedRows) {
            Database::get()->query("UPDATE glossary SET category_id = NULL
                                                  WHERE course_id = ?d AND
                                                        category_id = ?d", $course_id, $cat_id);
            Session::Messages($langCategoryDeletedGlossary, 'alert-success');
            redirect_to_home_page("modules/glossary/categories.php?course=$course_code");
        }        
    }


    // display form for adding or editing a category
    if (isset($_GET['add']) or isset($_GET['edit'])) {
        $html_id = $html_name = $description = '';
        if (isset($_GET['add'])) {
            $pageName = $langCategoryAdd;
            $submit_value = $langSubmit;
        } else {
            $pageName = $langCategoryMod;
            $cat_id = intval($_GET['edit']);
            $data = Database::get()->querySingle("SELECT name, description
                                              FROM glossary_category WHERE id = ?d", $cat_id);
            if ($data) {
                $html_name = " value='" . q($data->name) . "'";
                $html_id = "<input type = 'hidden' name='category_id' value='$cat_id'>";
                $description = $data->description;
            }
            $submit_value = $langModify;
        }
        $tool_content .= "<div class='form-wrapper'><form class='form-horizontal' role='form' action='$cat_url' method='post'>
                    $html_id
                    <div class='form-group'>
                         <label for='name' class='col-sm-2 control-label'>$langCategoryName: </label>
                         <div class='col-sm-10'>
                             <input type='text' class='form-control' id='term' name='name' placeholder='$langCategoryName'$html_name>
                         </div>
                    </div>
                    <div class='form-group'>
                         <label for='description' class='col-sm-2 control-label'>$langDescription: </label>
                         <div class='col-sm-10'>
                             " . rich_text_editor('description', 4, 60, $description) . "
                         </div>
                    </div>
                   <div class='form-group'>    
                        <div class='col-sm-10 col-sm-offset-2'>
                             <input class='btn btn-primary' type='submit' name='submit_category' value='$submit_value'>
                             <a href='$cat_url' class='btn btn-default'>$langCancel</a>
                        </div>
                    </div>                            
                </form>
            </div>";                       
    }
}

if (!isset($_GET['edit']) && !isset($_GET['add'])) {
    $q = Database::get()->queryArray("SELECT id, name, description
                          FROM glossary_category WHERE course_id = ?d
                          ORDER BY name", $course_id);

    if ($q and count($q)) {    
            $tool_content .= "
        <div class='table-responsive'>    
            <table class='table-default'>
                <tr><th class='text-left'>$langName</th>" .
             ($is_editor ? "<th class='text-center'>" . icon('fa-gears') . "</th>" : '') . "
                </tr>";

        foreach ($q as $cat) {        
            if ($cat->description) {
                $desc = "<br>" . standard_text_escape($cat->description);
            } else {
                $desc = '';
            }        
            $tool_content .= "<tr><td><a href='$base_url&amp;cat=$cat->id'>" . q($cat->name) . "</a>$desc</td>";                       
            if ($is_editor) {
                $tool_content .= "<td class='option-btn-cell'>";
                $tool_content .= action_button(array(
                        array('title' => $langCategoryMod,
                              'url' => "$cat_url&amp;edit=$cat->id",
                              'icon' => 'fa-edit'),
                        array('title' => $langCategoryDel,
                              'url' => "$cat_url&amp;delete=$cat->id",
                              'icon' => 'fa-times',
                              'class' => 'delete',
                              'confirm' => $langConfirmDelete))
                    );
               $tool_content .= "</td>";                        
            }
            $tool_content .= "</tr>";
        }
        $tool_content .= "</table></div>";
    } else {
        $tool_content .= "<div class='alert alert-warning'>$langNoResult</div>";
    }
}

draw($tool_content, 2, null, $head_content);

