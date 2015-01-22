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
 * @file index.php
 * @brief display forum page
 */
$require_login = TRUE;
$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'For';
require_once '../../include/baseTheme.php';
$toolName = $langForums;

/* * ** The following is added for statistics purposes ** */
require_once 'include/action.php';
$action = new action();
$action->record(MODULE_ID_FORUM);
/* * *********************************** */

require_once 'config.php';
require_once 'functions.php';
require_once 'modules/group/group_functions.php';

load_js('tools.js');

if ($is_editor) {
    $tool_content .= "
	<div id='operations_container'>" .
            action_bar(array(
                array('title' => $langAddCategory,
                    'url' => "forum_admin.php?course=$course_code",
                    'icon' => 'fa-plus-circle',
                    'level' => 'primary-label',
                    'button-class' => 'btn-success'),
                array('title' => $langConfig,
                    'url' => "forum_admin.php?course=$course_code&amp;settings=yes",
                    'icon' => 'fa-gear',
                    'level' => 'primary'),
            )) .
            "</div>";
}


if (isset($_GET['forumcatnotify'])) { // modify forum category notification
    if (isset($_GET['cat_id'])) {
        $cat_id = $_GET['cat_id'];
    }
    $rows = Database::get()->querySingle("SELECT COUNT(*) AS count FROM forum_notify
		WHERE user_id = ?d AND cat_id = ?d AND course_id = ?d", $uid, $cat_id, $course_id);
    if ($rows->count > 0) {
        Database::get()->query("UPDATE forum_notify SET notify_sent = ?d WHERE user_id = ?d AND cat_id = ?d AND course_id = ?d", $_GET['forumcatnotify'], $uid, $cat_id, $course_id);
    } else {
        Database::get()->query("INSERT INTO forum_notify SET user_id = ?d, cat_id = ?d, notify_sent = 1, course_id = ?d", $uid, $cat_id, $course_id);
    }
} elseif (isset($_GET['forumnotify'])) { // modify forum notification
    if (isset($_GET['forum_id'])) {
        $forum_id = $_GET['forum_id'];
    }
    $rows = Database::get()->querySingle("SELECT COUNT(*) AS count FROM forum_notify
		WHERE user_id = ?d AND forum_id = ?d AND course_id = ?d", $uid, $forum_id, $course_id);
    if ($rows->count > 0) {
        Database::get()->query("UPDATE forum_notify SET notify_sent = ?d WHERE user_id = ?d AND forum_id = ?d AND course_id = ?d", $_GET['forumnotify'], $uid, $forum_id, $course_id);
    } else {
        Database::get()->query("INSERT INTO forum_notify SET user_id = ?d, forum_id = ?d, notify_sent = 1, course_id = ?d", $uid, $forum_id, $course_id);
    }
}

/*
 * Populate data with forum categories
 */
$categories = Database::get()->queryArray("SELECT id, cat_title FROM forum_category WHERE course_id = ?d ORDER BY id ", $course_id);

$total_categories = count($categories);

if ($total_categories > 0) {
    $forum_row = Database::get()->queryArray("SELECT f.id forum_id, f.*, p.post_time, p.topic_id, p.poster_id
		FROM forum f LEFT JOIN forum_post p ON p.id = f.last_post_id
                WHERE f.course_id = ?d
		ORDER BY f.cat_id, f.id", $course_id);

    foreach ($categories as $cat_row) {
        $cat_title = q($cat_row->cat_title);
        $catNum = $cat_row->id;
        $sql = Database::get()->querySingle("SELECT notify_sent FROM forum_notify
                                                        WHERE user_id = ?d AND cat_id = ?d AND course_id = ?d", $uid, $catNum, $course_id);
        if ($sql) {
            $action_notify = $sql->notify_sent;
        }

        if (!isset($action_notify)) {
            $link_notify = FALSE;
            $icon = '_off';
        } else {
            $link_notify = toggle_link($action_notify);
            $icon = toggle_icon($action_notify);
        }
        $tool_content .= "<table class='table-default'>";
        $tool_content .= "<tr class='odd'>
		<th colspan='5'><b>$cat_title</b></th>
		<th class='option-btn-cell text-center'>";

        $dyntools = (!$is_editor) ? array() : array(
            array(
                'title' => $langNewForum,
                'url' => "forum_admin.php?course=$course_code&amp;forumgo=yes&amp;cat_id=$catNum",
                'icon' => 'fa-plus-circle'
            ),
            array('title' => $langModify,
                'url' => "forum_admin.php?course=$course_code&amp;forumcatedit=yes&amp;cat_id=$catNum",
                'icon' => 'fa-edit'),
            array('title' => $langDelete,
                'url' => "forum_admin.php?course=$course_code&amp;forumcatdel=yes&amp;cat_id=$catNum",
                'icon' => 'fa-times',
                'class' => 'delete',
                'confirm' => $langConfirmDelete)
        );
        $dyntools[] = array('title' => $langNotify,
            'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;forumcatnotify=$link_notify&amp;cat_id=$catNum",
            'icon' => 'fa-envelope');
        $tool_content .= action_button($dyntools);

        $tool_content .= "<tr>
		<td colspan='2'>$langForums</td>
		<td class='text-center'>$langSubjects</td>
		<td class='text-center'>$langPosts</td>
		<td class='text-center'>$langLastPost</td>
		<td class='text-center option-btn-cell'>" . icon('fa-gears') . "</td>
		</tr>";

        // display forum topics
        foreach ($forum_row as $forum_data) {
            unset($last_post);
            $cat_id = $cat_row->id;
            if (Database::get()->querySingle("SELECT COUNT(*) AS count FROM forum WHERE cat_id = ?d AND course_id = ?d", $cat_id, $course_id)->count > 0) {
                // if category forum topics are found
                if ($forum_data->cat_id == $cat_id) {
                    if ($forum_data->post_time) {
                        $last_post = $forum_data->post_time;
                        $last_post_datetime = $forum_data->post_time;
                        list($last_post_date, $last_post_time) = explode(' ', $last_post_datetime);
                        list($year, $month, $day) = explode('-', $last_post_date);
                        list($hour, $min) = explode(':', $last_post_time);
                        $last_post_time = mktime($hour, $min, 0, $month, $day, $year);
                        $human_last_post_time = date('d/m/Y -  H:i', $last_post_time);
                    }
                    if (empty($last_post)) {
                        $last_post = $langNoPosts;
                    }
                    $tool_content .= "<tr>";
                    if (!isset($last_visit)) {
                        $last_visit = 0;
                    }/*
                    if (@$last_post_time > $last_visit && $last_post != $langNoPosts) {
                        $tool_content .= "<td><img src='$newposts_image' /></td>";
                    } else {
                        $tool_content .= "<td>".icon('fa-comments')."</td>";
                    }*/
                    $tool_content .= "<td>".icon('fa-comments')."</td>";
                    $forum_name = q($forum_data->name);
                    if ($forum_data->poster_id) {
                        $last_user_post = uid_to_name($forum_data->poster_id);
                    } else {
                        $last_user_post = '';
                    }
                    $last_post_topic_id = $forum_data->topic_id;
                    $total_posts = $forum_data->num_posts;
                    $total_topics = $forum_data->num_topics;
                    $desc = q($forum_data->desc);
                    $tool_content .= "<td>";
                    $forum_id = $forum_data->id;
                    $is_member = false;
                    $group_id = init_forum_group_info($forum_id);
                    $member = $is_member ? "&nbsp;&nbsp;($langMyGroup)" : '';
                    // Show link to forum if:
                    //  - user is admin of course
                    //  - forum doesn't belong to group
                    //  - forum belongs to group and group forums are enabled and
                    //     - user is member of group
                    if ($is_editor or ! $group_id or ( $has_forum and $is_member)) {
                        $tool_content .= "<a href='viewforum.php?course=$course_code&amp;forum=$forum_id'>
                                                                <b>$forum_name</b>
                                                                </a><div class='smaller'>" . $member . "</div>";
                    } else {
                        $tool_content .= $forum_name;
                    }
                    $tool_content .= "<div class='smaller'>$desc</div>";
                    $tool_content .= "</td>";
                    $tool_content .= "<td class='text-center'>$total_topics</td>";
                    $tool_content .= "<td class='text-center'>$total_posts</td>";
                    $tool_content .= "<td class='text-center'>";
                    if ($total_topics > 0 && $total_posts > 0) {
                        $tool_content .= "<span class='smaller'>" . q($last_user_post) . "&nbsp;
                                                <a href='viewtopic.php?course=$course_code&amp;topic=$last_post_topic_id&amp;forum=$forum_id'>
						".icon('fa-comment-o', $langLastPost)."
						</a>
						$human_last_post_time</span></td>";
                    } else {
                        $tool_content .= "<div class='inactive'>$langNoPosts</div></td>";
                    }
                    $forum_action_notify = Database::get()->querySingle("SELECT notify_sent FROM forum_notify
								WHERE user_id = ?d
								AND forum_id = ?d
								AND course_id = ?d", $uid, $forum_id, $course_id);
                    if ($forum_action_notify) {
                        $forum_action_notify = $forum_action_notify->notify_sent;
                    }
                    if (!isset($forum_action_notify)) {
                        $forum_link_notify = false;
                        $forum_icon = '_off';
                    } else {
                        $forum_link_notify = toggle_link($forum_action_notify);
                        $forum_icon = toggle_icon($forum_action_notify);
                    }
                    $tool_content .= "<td class='option-btn-cell'>";

                    $dyntools = (!$is_editor) ? array() : array(
                        array('title' => $langModify,
                            'url' => "forum_admin.php?course=$course_code&amp;forumgoedit=yes&amp;forum_id=$forum_id&amp;cat_id=$catNum",
                            'icon' => 'fa-edit'),
                        array('title' => $langDelete,
                            'url' => "forum_admin.php?course=$course_code&amp;forumgodel=yes&amp;forum_id=$forum_id&amp;cat_id=$catNum",
                            'icon' => 'fa-times',
                            'class' => 'delete',
                            'confirm' => $langConfirmDelete)
                    );
                    $dyntools[] = array('title' => $langNotify,
                        'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;forumnotify=$forum_link_notify&amp;forum_id=$forum_id",
                        'icon' => 'fa-envelope');
                    $tool_content .= action_button($dyntools);
                }
            } else {
                $tool_content .= "<tr>";
                $tool_content .= "<td colspan='6' class='alert2'>$langNoForumsCat</td>";
                $tool_content .= "</tr>";
                break;
            }
        }
        $tool_content .= "</table>";
    }
} else {
    $tool_content .= "<div class='alert alert-warning'>$langNoForums</div>";
}
add_units_navigation(true);
if ($is_editor) {
    draw($tool_content, 2, null, $head_content);
} else {
    draw($tool_content, 2);
}
