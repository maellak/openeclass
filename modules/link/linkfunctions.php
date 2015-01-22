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

require_once 'modules/search/indexer.class.php';

function makedefaultviewcode($locatie) {
    global $aantalcategories;

    $view = str_repeat('0', $aantalcategories);
    $view[$locatie] = '1';
    return $view;
}

/**
 * Function getNumberOfLinks
 * @param unknown_type $catid
 * @return int number of links
 */
function getNumberOfLinks($catid) {
    global $course_id;
    return Database::get()->querySingle("SELECT COUNT(*) as count FROM `link`
                                                        WHERE course_id = ?d AND category = ?d
                                                        ORDER BY `order`", $course_id, $catid)->count;
}

/**
 * @brief display links of category
 * @global type $is_editor
 * @global type $course_id
 * @global type $urlview
 * @global type $tool_content
 * @global type $urlServer
 * @global type $course_code
 * @global type $langLinkDelconfirm
 * @global type $langDelete
 * @global type $langUp
 * @global type $langDown
 * @global type $langModify
 * @global type $is_in_tinymce
 * @param type $catid
 */
function showlinksofcategory($catid) {
    global $is_editor, $course_id, $urlview, $tool_content,
    $urlServer, $course_code,
    $langLinkDelconfirm, $langDelete, $langUp, $langDown,
    $langModify, $is_in_tinymce;

    $tool_content .= "<tr>";
    $result = Database::get()->queryArray("SELECT * FROM `link`
                                   WHERE course_id = ?d AND category = ?d
                                   ORDER BY `order`", $course_id, $catid);
    $numberoflinks = count($result);
    $i = 1;
    foreach ($result as $myrow) {
        $title = empty($myrow->title) ? $myrow->url : $myrow->title;        
        $num_merge_cols = 1;
        $aclass = ($is_in_tinymce) ? " class='fileURL' " : '';
        $tool_content .= "<td colspan='$num_merge_cols'><a href='" . $urlServer . "modules/link/go.php?course=$course_code&amp;id=$myrow->id&amp;url=" .
                urlencode($myrow->url) . "' $aclass target='_blank'>" . q($title) . "</a>";
        if (!empty($myrow->description)) {
            $tool_content .= "<br />" . standard_text_escape($myrow->description);
        }
        $tool_content .= "</td>";
        $tool_content .= "<td class='option-btn-cell'>";
        if ($is_editor && !$is_in_tinymce) {            
            $editlink = "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=editlink&amp;id=$myrow->id&amp;urlview=$urlview";
            if (isset($category)) {
                $editlink .= "&amp;category=$category";
            }
            $tool_content .= action_button(array(
                array('title' => $langDelete,
                      'icon' => 'fa-times',
                      'class' => 'delete',
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=deletelink&amp;id=$myrow->id&amp;urlview=$urlview",
                      'confirm' => $langLinkDelconfirm),
                array('title' => $langModify,
                      'icon' => 'fa-edit',
                      'url' => $editlink),
                array('title' => $langUp,
                      'icon' => 'fa-arrow-up',
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$urlview&amp;up=$myrow->id",
                      'show' => $i != 1),
                array('title' => $langDown,
                      'icon' => 'fa-arrow-down',
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$urlview&amp;down=$myrow->id",
                      'show' => $i < $numberoflinks)
            ));
        } else {
            $tool_content .= "&nbsp;";
        }
        $tool_content .= "</td>";
        $tool_content .= "</tr>";
        $i++;
    }
}

/**
 * @brief display action bar in categories
 * @global type $urlview
 * @global type $aantalcategories
 * @global type $catcounter
 * @global type $langDelete
 * @global type $langModify
 * @global type $langUp
 * @global type $langDown
 * @global type $langCatDel
 * @global type $tool_content
 * @global type $course_code
 * @param type $categoryid
 */
function showcategoryadmintools($categoryid) {
    global $urlview, $aantalcategories, $catcounter, $langDelete,
    $langModify, $langUp, $langDown, $langCatDel, $tool_content,
    $course_code;

    $basecaturl = "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$categoryid&amp;urlview=$urlview&amp;";
    $tool_content .= action_button(array(
                array('title' => $langDelete,
                      'icon' => 'fa-times',
                      'url' => "$basecaturl" . "action=deletecategory",
                      'class' => 'delete',
                      'confirm' => $langCatDel),
                array('title' => $langModify,
                      'icon' => 'fa-edit',
                      'url' => "$basecaturl" . "action=editcategory"),
                array('title' => $langUp,
                      'icon' => 'fa-arrow-up',
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$urlview&amp;cup=$categoryid",
                      'show' => $catcounter != 1),
                 array('title' => $langDown,
                       'icon' => 'fa-arrow-down',
                       'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$urlview&amp;cdown=$categoryid",
                       'show' => $catcounter < $aantalcategories)
                ));           
    $catcounter++;
}

/**
 * @brief Enter the modified info submitted from the link form into the database
 * @global type $course_id
 * @global type $langLinkMod
 * @global type $langLinkAdded
 * @global type $urllink
 * @global type $title
 * @global type $description
 * @global type $selectcategory
 * @global type $langLinkNotPermitted
 * @global string $state
 * @return type
 */
function submit_link() {
    global $course_id, $langLinkMod, $langLinkAdded,
    $urllink, $title, $description, $selectcategory, $langLinkNotPermitted, $state;

    register_posted_variables(array('urllink' => true,
        'title' => true,
        'description' => true,
        'selectcategory' => true), 'all', 'trim');
    $urllink = canonicalize_url($urllink);
    if (!is_url_accepted($urllink,"(https?|ftp)")){
        $message = $langLinkNotPermitted;
        if (isset($_POST['id'])) {
            $id = $_POST['id'];
            redirect_to_home_page("modules/link/index.php?course=$course_code&action=editlink&id=$id&urlview=");
        } else {
            redirect_to_home_page("modules/link/index.php?course=$course_code&action=addlink&urlview=");
        }
    }
    $set_sql = "SET url = ?s, title = ?s, description = ?s, category = ?d";
    $terms = array($urllink, $title, purify($description), $selectcategory);

    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        Database::get()->query("UPDATE `link` $set_sql WHERE course_id = ?d AND id = ?d", $terms, $course_id, $id);

        $log_type = LOG_MODIFY;
    } else {
        $order = Database::get()->querySingle("SELECT MAX(`order`) as maxorder FROM `link`
                                      WHERE course_id = ?d AND category = ?d", $course_id, $selectcategory)->maxorder;
        $order++;
        $id = Database::get()->query("INSERT INTO `link` $set_sql, course_id = ?d, `order` = ?d", $terms, $course_id, $order)->lastInsertID;
        $log_type = LOG_INSERT;
    }
    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_LINK, $id);
    // find category name
    $category_object = Database::get()->querySingle("SELECT link_category.name as name FROM link, link_category
                                                        WHERE link.category = link_category.id
                                                        AND link.course_id = ?s
                                                        AND link.id = ?d", $course_id, $id);
    $category = $category_object ? $category_object->name : 0;
    $txt_description = ellipsize_html(canonicalize_whitespace(strip_tags($description)), 50, '+');
    Log::record($course_id, MODULE_ID_LINKS, $log_type, @array('id' => $id,
        'url' => $urllink,
        'title' => $title,
        'description' => $txt_description,
        'category' => $category));

}

/**
 * @brief fill in category form values
 * @global type $course_id
 * @global type $form_name
 * @global type $form_description
 * @param type $id
 */
function category_form_defaults($id) {
    global $course_id, $form_name, $form_description;

    $myrow = Database::get()->querySingle("SELECT name,description  FROM link_category WHERE course_id = ?d AND id = ?d", $course_id, $id);
    if ($myrow) {
        $form_name = ' value="' . q($myrow->name) . '"';
        $form_description = q($myrow->description);
    } else {
        $form_name = $form_description = '';
    }
}

/**
 * @brief fill in link form values
 * @global type $course_id
 * @global type $form_url
 * @global type $form_title
 * @global type $form_description
 * @global type $category
 * @param type $id
 */
function link_form_defaults($id) {
    global $course_id, $form_url, $form_title, $form_description, $category;

    $myrow = Database::get()->querySingle("SELECT * FROM `link` WHERE course_id = ?d AND id = ?d", $course_id, $id);
    if ($myrow) {
        $form_url = ' value="' . q($myrow->url) . '"';
        $form_title = ' value="' . q($myrow->title) . '"';
        $form_description = purify(trim($myrow->description));
        $category = $myrow->category;
    } else {
        $form_url = $form_title = $form_description = '';
    }
}

/**
 * @brief Enter the modified info submitted from the category form into the database
 * @global type $course_id
 * @global type $langCategoryAdded
 * @global type $langCategoryModded
 * @global type $categoryname
 * @global type $description
 */
function submit_category() {
    global $course_id, $langCategoryAdded, $langCategoryModded,
    $categoryname, $description;

    register_posted_variables(array('categoryname' => true,
                                    'description' => true), 'all', 'trim');
    $set_sql = "SET name = ?s, description = ?s";
    $terms = array($categoryname, purify($description));

    if (isset($_POST['id'])) {
        $id = $_POST['id'];
        Database::get()->query("UPDATE `link_category` $set_sql WHERE course_id = ?d AND id = ?d", $terms, $course_id, $id);
        $log_type = LOG_MODIFY;
    } else {
        $order = Database::get()->querySingle("SELECT MAX(`order`) as maxorder FROM `link_category`
                                      WHERE course_id = ?d", $course_id)->maxorder;
        $order++;
        $id = Database::get()->query("INSERT INTO `link_category` $set_sql, course_id = ?d, `order` = ?d", $terms, $course_id, $order)->lastInsertID;
        $log_type = LOG_INSERT;
    }
    $txt_description = ellipsize(canonicalize_whitespace(strip_tags($description)), 50, '+');
    Log::record($course_id, MODULE_ID_LINKS, $log_type, array('id' => $id,
        'category' => $categoryname,
        'description' => $txt_description));
}

/**
 * @brief delete link
 * @global type $course_id
 * @global type $langLinkDeleted
 * @param type $id
 */
function delete_link($id) {
    global $course_id, $langLinkDeleted;

    $tuple = Database::get()->querySingle("SELECT url, title FROM link WHERE course_id = ?d AND id = ?d", $course_id, $id);
    $url = $tuple->url;
    $title = $tuple->title;
    Database::get()->query("DELETE FROM `link` WHERE course_id = ?d AND id = ?d", $course_id, $id);
    Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_LINK, $id);
    Log::record($course_id, MODULE_ID_LINKS, LOG_DELETE, array('id' => $id,
                                                               'url' => $url,
                                                               'title' => $title));
}

/**
 * @brief delete category
 * @global type $course_id
 * @global type $langCategoryDeleted
 * @global type $catlinkstatus
 * @param type $id
 */
function delete_category($id) {
    global $course_id, $langCategoryDeleted, $catlinkstatus;

    Database::get()->query("DELETE FROM `link` WHERE course_id = ?d AND category = ?d", $course_id, $id);
    $category = Database::get()->querySingle("SELECT name FROM link_category WHERE course_id = ?d AND id = ?d", $course_id, $id)->name;
    Database::get()->query("DELETE FROM `link_category` WHERE course_id = ?d AND id = ?d", $course_id, $id);
    Log::record($course_id, MODULE_ID_LINKS, LOG_DELETE, array('cat_id' => $id,
                                                               'category' => $category));
}
