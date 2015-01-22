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


require_once 'modules/document/doc_init.php';
require_once 'include/lib/mediaresource.factory.php';

/**
 * helper function to get a file path from get variable
 * @param string $name
 * @global array $_GET
 * @return string
 */
function get_dir_path($name) {
    if (isset($_GET[$name])) {
        $path = q($_GET[$name]);
        if ($path == '/' or $path == '\\') {
            $path = '';
        }
    } else {
        $path = '';
    }
    return $path;
}

/**
 * list documents while inserting them in course unit
 * @global type $id
 * @global type $webDir
 * @global type $course_code
 * @global type $tool_content
 * @global type $group_sql
 * @global type $langDirectory
 * @global type $langUp
 * @global type $langName
 * @global type $langSize
 * @global type $langDate
 * @global type $langType
 * @global type $langAddModulesButton
 * @global type $langChoice
 * @global type $langNoDocuments
 * @global type $course_code 
 */
function list_docs() {
    global $id, $webDir, $course_code, $tool_content,
    $group_sql, $langDirectory, $langUp, $langName, $langSize,
    $langDate, $langType, $langAddModulesButton, $langChoice,
    $langNoDocuments, $course_code, $langCommonDocs, $pageName;

    $basedir = $webDir . '/courses/' . $course_code . '/document';
    $path = get_dir_path('path');
    $dir_param = get_dir_path('dir');
    $dir_setter = $dir_param ? ('&amp;dir=' . $dir_param) : '';
    $dir_html = $dir_param ? "<input type='hidden' name='dir' value='$dir_param'>" : '';

    if ($id == -1) {
        $common_docs = true;
        $pageName = $langCommonDocs;
        $group_sql = "course_id = -1 AND subsystem = " . COMMON . "";
        $basedir = $webDir . '/courses/commondocs';
        $result = Database::get()->queryArray("SELECT * FROM document
                                    WHERE $group_sql AND
                                          visible = 1 AND
                                          path LIKE ?s AND
                                          path NOT LIKE ?s",
                                    "$path/%", "$path/%/%");
    } else {
        $common_docs = false;
        $result = Database::get()->queryArray("SELECT * FROM document
                                    WHERE $group_sql AND
                                          path LIKE ?s AND
                                          path NOT LIKE ?s",
                                    "$path/%", "$path/%/%");
    }

    $fileinfo = array();
    $urlbase = $_SERVER['SCRIPT_NAME'] . "?course=$course_code$dir_setter&amp;type=doc&amp;id=$id&amp;path=";

    foreach ($result as $row) {
        $fullpath = $basedir . $row->path;
        if ($row->extra_path) {
            $size = 0;
        } else {
            $size = file_exists($fullpath)? filesize($fullpath): 0;
        }
        $fileinfo[] = array(
            'id' => $row->id,
            'is_dir' => is_dir($fullpath),
            'size' => $size,
            'title' => $row->title,
            'name' => htmlspecialchars($row->filename),
            'format' => $row->format,
            'path' => $row->path,
            'visible' => $row->visible,
            'comment' => $row->comment,
            'copyrighted' => $row->copyrighted,
            'date' => $row->date_modified,
            'object' => MediaResourceFactory::initFromDocument($row));
    }
    if (count($fileinfo) == 0) {
        $tool_content .= "<div class='alert alert-warning'>$langNoDocuments</div>";
    } else {
        if (empty($path)) {
            $dirname = '';
            $parenthtml = '';
            $colspan = 5;
        } else {
            $dirname = Database::get()->querySingle("SELECT filename FROM document
                                                                   WHERE $group_sql AND path = ?s", $path);
            $parentpath = dirname($path);
            $dirname = "/" . htmlspecialchars($dirname);
            $parentlink = $urlbase . $parentpath;
            $parenthtml = "<th class='right'><a href='$parentlink'>$langUp</a> " .
                    icon('fa-upload', $langUp, $parentlink) . "</th>";
            $colspan = 4;
        }
        $tool_content .= "<form action='insert.php?course=$course_code' method='post'><input type='hidden' name='id' value='$id' />" .
                "<table class='table-default'>" .
                "<tr>" .
                "<th colspan='$colspan'><div align='left'>$langDirectory: $dirname</div></th>" .
                $parenthtml .
                "</tr>" .
                "<tr>" .
                "<th>$langType</th>" .
                "<th><div align='left'>$langName</div></th>" .
                "<th width='100'>$langSize</th>" .
                "<th width='80'>$langDate</th>" .
                "<th width='80'>$langChoice</th>" .
                "</tr>";
        $counter = 0;
        foreach (array(true, false) as $is_dir) {
            foreach ($fileinfo as $entry) {
                if ($entry['is_dir'] != $is_dir) {
                    continue;
                }
                $dir = $entry['path'];
                if ($is_dir) {
                    $image = 'fa-folder-o';
                    $file_url = $urlbase . $dir;
                    $link_text = $entry['name'];

                    $link_href = "<a href='$file_url'>$link_text</a>";
                } else {
                    $image = choose_image('.' . $entry['format']);
                    $file_url = file_url($entry['path'], $entry['name'], $common_docs ? 'common' : $course_code);

                    $dObj = $entry['object'];
                    $dObj->setAccessURL($file_url);
                    $dObj->setPlayURL(file_playurl($entry['path'], $entry['name'], $common_docs ? 'common' : $course_code));

                    $link_href = MultimediaHelper::chooseMediaAhref($dObj);
                }
                if ($entry['visible'] == 'i') {
                    $vis = 'invisible';
                } else {
                    $vis = '';                    
                }
                $tool_content .= "<tr class='$vis'>";
                $tool_content .= "<td width='1' class='center'>" . icon($image, '') . "</td>";
                $tool_content .= "<td>$link_href";

                /* * * comments ** */
                if (!empty($entry['comment'])) {
                    $tool_content .= "<br /><div class='comment'>" .
                            standard_text_escape($entry['comment']) .
                            "</div>";
                }
                $tool_content .= "</td>";
                if ($is_dir) {
                    // skip display of date and time for directories
                    $tool_content .= "<td>&nbsp;</td><td>&nbsp;</td>";
                } else {
                    $size = format_file_size($entry['size']);
                    $date = nice_format($entry['date'], true, true);
                    $tool_content .= "<td class='center'>$size</td><td class='center'>$date</td>";
                }
                $tool_content .= "<td class='center'><input type='checkbox' name='document[]' value='$entry[id]' /></td>";
                $tool_content .= "</tr>";
                $counter++;
            }
        }
        $tool_content .= "<tr><th colspan=$colspan><div align='right'>";
        $tool_content .= "<input class='btn btn-primary' type='submit' name='submit_doc' value='$langAddModulesButton' /></div></th>";
        $tool_content .= "</tr></table>$dir_html</form>";
    }
}
