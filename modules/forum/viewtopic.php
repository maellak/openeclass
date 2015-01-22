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
$require_login = true;
$require_help = true;
$helpTopic = 'For';
require_once '../../include/baseTheme.php';
require_once 'modules/forum/config.php';
require_once 'modules/forum/functions.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
require_once 'include/course_settings.php';
require_once 'modules/search/indexer.class.php';
require_once 'modules/rating/class.rating.php';

ModalBoxHelper::loadModalBox();

$toolName = $langForums;
if ($is_editor) {
    load_js('tools.js');
}

if (isset($_GET['all'])) {
    $paging = false;
} else {
    $paging = true;
}

if (isset($_GET['forum'])) {
    $forum = intval($_GET['forum']);
} else {
    header("Location: index.php?course=$course_code");
    exit();
}
if (isset($_GET['topic'])) {
    $topic = intval($_GET['topic']);
}
if (isset($_GET['post_id'])) {//needed to find post page for anchors
    $post_id = intval($_GET['post_id']);
    $myrow = Database::get()->querySingle("SELECT f.id, f.name, p.post_time, p.poster_id, t.locked FROM forum f, forum_topic t, forum_post p
            WHERE f.id = ?d
            AND t.id = ?d
            AND p.id = ?d
            AND t.forum_id = f.id
            AND p.topic_id = t.id
            AND f.course_id = ?d", $forum, $topic, $post_id, $course_id);
} else {
    $myrow = Database::get()->querySingle("SELECT f.id, f.name, t.locked FROM forum f, forum_topic t
            WHERE f.id = ?d
            AND t.id = ?d
            AND t.forum_id = f.id
            AND f.course_id = ?d", $forum, $topic, $course_id);
}


if (!$myrow) {
    $tool_content .= "<div class='alert alert-warning'>$langErrorTopicSelect</div>";
    draw($tool_content, 2);
    exit();
}
$forum_name = $myrow->name;
$forum = $myrow->id;
$topic_locked = $myrow->locked;
$total = get_total_posts($topic);

if (isset($_GET['delete']) && isset($post_id) && $is_editor) {
    $last_post_in_thread = get_last_post($topic);

    $this_post_time = $myrow->post_time;
    $this_post_author = $myrow->poster_id;

    Database::get()->query("DELETE FROM forum_post WHERE id = ?d", $post_id);
    Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_FORUMPOST, $post_id);

    //orphan replies get -1 to parent_post_id
    Database::get()->query("UPDATE forum_post SET parent_post_id = -1 WHERE parent_post_id = ?d", $post_id);

    $forum_user_stats = Database::get()->querySingle("SELECT COUNT(*) as c FROM forum_post
                        INNER JOIN forum_topic ON forum_post.topic_id = forum_topic.id
                        INNER JOIN forum ON forum.id = forum_topic.forum_id
                        WHERE forum_post.poster_id = ?d AND forum.course_id = ?d", $this_post_author, $course_id);
    Database::get()->query("DELETE FROM forum_user_stats WHERE user_id = ?d AND course_id = ?d", $this_post_author, $course_id);
    if ($forum_user_stats->c != 0) {
        Database::get()->query("INSERT INTO forum_user_stats (user_id, num_posts, course_id) VALUES (?d,?d,?d)", $this_post_author, $forum_user_stats->c, $course_id);
    }

    if ($total == 1) { // if exists one post in topic
        Database::get()->query("DELETE FROM forum_topic WHERE id = ?d AND forum_id = ?d", $topic, $forum);
        Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_FORUMTOPIC, $topic);
        Database::get()->query("UPDATE forum SET num_topics = 0,
                            num_posts = 0
                            WHERE id = ?d
                            AND course_id = ?d", $forum, $course_id);
        header("Location: viewforum.php?course=$course_code&forum=$forum");
    } else {
        $last_post = Database::get()->querySingle("SELECT MAX(id) AS last_post FROM forum_post WHERE topic_id = ?d", $topic)->last_post;

        Database::get()->query("UPDATE forum SET
                        `num_posts` = `num_posts`-1,
                        last_post_id = ?d
                        WHERE id = ?d
                        AND course_id = ?d", $last_post, $forum, $course_id);

        Database::get()->query("UPDATE forum_topic SET
                                `num_replies` = `num_replies`-1,
                                last_post_id = ?d
                        WHERE id = ?d", $last_post, $topic);
    }
    if ($last_post_in_thread == $this_post_time) {
        $topic_time_fixed = $last_post_in_thread;
        $sql = "UPDATE forum_topic
			SET topic_time = '$topic_time_fixed'
			WHERE id = $topic";
    }
    $tool_content .= "<div class='alert alert-success'>$langDeletedMessage</div>";
}



if ($paging and $total > $posts_per_page) {
    $times = 0;
    for ($x = 0; $x < $total; $x += $posts_per_page) {
        $times++;
    }
    $pages = $times;
}

$topic_subject = Database::get()->querySingle("SELECT title FROM forum_topic WHERE id = ?d", $topic)->title;

if (!add_units_navigation(TRUE)) {
    $navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langForums);
    $navigation[] = array('url' => "viewforum.php?course=$course_code&amp;forum=$forum", 'name' => q($forum_name));
}
$pageName = q($topic_subject);

if (isset($_SESSION['message'])) {
    $tool_content .= $_SESSION['message'];
    unset($_SESSION['message']);
}

if ($topic_locked == 1) {
    $tool_content .= "<div class='alert alert-warning'>$langErrorTopicLocked</div>";
} else {
    $tool_content .= 
            action_bar(array(
                array('title' => $langReply,
                    'url' => "reply.php?course=$course_code&amp;topic=$topic&amp;forum=$forum",
                    'icon' => 'fa-plus-circle',
                    'level' => 'primary-label',
                    'button-class' => 'btn-success'),
                array('title' => $langBack,
                    'url' => "viewforum.php?course=$course_code&forum=$forum",
                    'icon' => 'fa-reply',
                    'level' => 'primary-label')                
                ));
}

if ($paging and $total > $posts_per_page) {
    $times = 1;
    $tool_content .= "<div class='table-responsive'><table width='100%' class='table-default'>
	<tr>
          <td width='50%' align='left'>
	  <span class='row'><strong class='pagination'>
	  <span>";

    if (isset($post_id)) {
        $result = Database::get()->querySingle("SELECT COUNT(*) as c FROM forum_post WHERE topic_id = ?d AND post_time <= ?t", $topic, $myrow->post_time);
        $num = $result->c;
        $_GET['start'] = (ceil($num / $posts_per_page) - 1) * $posts_per_page;
    }

    if (isset($_GET['start'])) {
        $start = intval($_GET['start']);
    } else {
        $start = 0;
    }

    $last_page = $start - $posts_per_page;
    $tool_content .= "$langPages: ";

    for ($x = 0; $x < $total; $x += $posts_per_page) {
        if ($times != 1) {
            $tool_content .= "<span class='page-sep'>,</span>";
        }
        if ($start && ($start == $x)) {
            $tool_content .= "" . $times;
        } else if ($start == 0 && $x == 0) {
            $tool_content .= "1";
        } else {
            $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$x'>$times</a>";
        }
        $times++;
    }

    $tool_content .= "</span></strong></span></td>
	<td align='right'>
	<span class='pages'>";
    if (isset($start) && $start > 0) {
        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$last_page'>$langPreviousPage</a>&nbsp;|";
    } else {
        $start = 0;
    }
    if (($start + $posts_per_page) < $total) {
        $next_page = $start + $posts_per_page;
        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$next_page'>$langNextPage</a>&nbsp;|";
    }
    $tool_content .= "&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;all=true'>$langAllOfThem</a></span>
	</td>
	</tr>
	</table></div>";
} else {
    if ($total > $posts_per_page) {
        $tool_content .= "<div class='table-responsive'><table width='100%' class='table-default'>
    	<tr>
    	<td width='60%' class='text-left'>
    	<span class='row'><strong class='pagination'>&nbsp;</strong></span></td>
    	<td align='right'>";
        $tool_content .= "<span class='pages'>
		&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=0'>$langPages</a>
		</span>";
        $tool_content .= "</td></tr></table></div>";
    }
}

$tool_content .= "<div class='table-responsive'><table width='100%' class='table-default'>
    <tr>
      <th width='220'>$langAuthor</th>
      <th>$langMessage</th>";
if ($is_editor || $topic_locked != 1) {
    $tool_content .= "<th width='60' class='text-center'>" . icon('fa-gears') . "</th>";
}
$tool_content .= "</tr>";

if (isset($_GET['all'])) {
    $result = Database::get()->queryArray("SELECT * FROM forum_post WHERE topic_id = ?d ORDER BY id", $topic);
} elseif (isset($_GET['start'])) {
    $start = intval($_GET['start']);
    $result = Database::get()->queryArray("SELECT * FROM forum_post
		WHERE topic_id = ?d
		ORDER BY id
                LIMIT $start, $posts_per_page", $topic);
} else {
    $result = Database::get()->queryArray("SELECT * FROM forum_post
		WHERE topic_id = ?d
		ORDER BY id
                LIMIT $posts_per_page", $topic);
}

$count = 0;
$user_stats = array();
foreach ($result as $myrow) {
    if (!isset($user_stats[$myrow->poster_id])) {
        $user_num_posts = Database::get()->querySingle("SELECT num_posts FROM forum_user_stats WHERE user_id = ?d AND course_id = ?d", $myrow->poster_id, $course_id);
        if ($user_num_posts) {
            if ($user_num_posts->num_posts == 1) {
                $user_stats[$myrow->poster_id] = "<br/>".$user_num_posts->num_posts." $langMessage";
            } else {
                $user_stats[$myrow->poster_id] = "<br/>".$user_num_posts->num_posts." $langMessages";
            }
        } else {
            $user_stats[$myrow->poster_id] = '';
        }
    }
    
    $tool_content .= "<td valign='top'>" . display_user($myrow->poster_id) . $user_stats[$myrow->poster_id]."</td>";    
    $message = $myrow->post_text;
    // support for math symbols
    $message = mathfilter($message, 12, "../../courses/mathimg/");
    if ($count == 0) {
        $postTitle = "<b>$langPostTitle: </b>" . q($topic_subject);
    } else {
        $postTitle = "";
    }

    $rate_str = "";
    if (setting_get(SETTING_FORUM_RATING_ENABLE, $course_id)) {
        $rating = new Rating('thumbs_up', 'forum_post', $myrow->id);
        $rate_str = $rating->put($is_editor, $uid, $course_id);
    }

    $anchor_link = "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;post_id=$myrow->id#$myrow->id'>#$myrow->id</a><br/>";
    if ($myrow->parent_post_id == -1) {
        $parent_post_link = "<br/>$langForumPostParentDel";
    } elseif ($myrow->parent_post_id != 0) {
        $parent_post_link = "$langForumPostParent<a href='viewtopic.php?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;post_id=$myrow->parent_post_id#$myrow->parent_post_id'>#$myrow->parent_post_id</a><br/><br/>";
    } else {
        $parent_post_link = "";
    }

    $tool_content .= "<td>
	  <div>
	    <a name='".$myrow->id."'></a>".$anchor_link;
	$tool_content .= "<b>$langSent: </b>" . $myrow->post_time . "<br>$postTitle
	  </div>
	  <br />$message<br />" . $parent_post_link . $rate_str . "
	</td>";

    $dyntools = (!$is_editor) ? array() : array(
        array('title' => $langModify,
            'url' => "editpost.php?course=$course_code&amp;post_id=" . $myrow->id . "&amp;topic=$topic&amp;forum=$forum",
            'icon' => 'fa-edit'
        ),
        array('title' => $langDelete,
            'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;post_id=$myrow->id&amp;topic=$topic&amp;forum=$forum&amp;delete=on",
            'icon' => 'fa-times',
            'class' => 'delete',
            'confirm' => $langConfirmDelete)
    );
    if ($topic_locked != 1) {
        $dyntools[] = array('title' => $langForumPostReply,
            'url' => "reply.php?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;parent_post=$myrow->id",
            'icon' => 'fa-reply');
    }
    if (!empty($dyntools)) {
        $tool_content .= "<td valign='center'>" . action_button($dyntools) . "</td>";
    }
    $tool_content .= "</tr>";
    $count++;
}

Database::get()->query("UPDATE forum_topic SET num_views = num_views + 1
            WHERE id = ?d AND forum_id = ?d", $topic, $forum);

$tool_content .= "</table></div>";

if ($paging and $total > $posts_per_page) {
    $times = 1;
    $tool_content .= "<div class='table-responsive'><table class='table-default'>
	<tr>
	<td width='50%'>
	<span class='row'><strong class='pagination'><span>";

    $last_page = $start - $posts_per_page;
    $tool_content .= "$langPages: ";

    for ($x = 0; $x < $total; $x += $posts_per_page) {
        if ($times != 1) {
            $tool_content .= "<span class='page-sep'>,</span>";
        }
        if ($start && ($start == $x)) {
            $tool_content .= "" . $times;
        } else if ($start == 0 && $x == 0) {
            $tool_content .= "1";
        } else {
            $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$x'>$times</a>";
        }
        $times++;
    }
    $tool_content .= "</span></strong></span></td>
	<td><span class='pages'>";
    if (isset($start) && $start > 0) {
        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$last_page'>$langPreviousPage</a>&nbsp;|";
    } else {
        $start = 0;
    }
    if (($start + $posts_per_page) < $total) {
        $next_page = $start + $posts_per_page;
        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$next_page'>$langNextPage</a>&nbsp;|";
    }
    $tool_content .= "&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;all=true'>$langAllOfThem</a>
	</span>
	</td></tr></table></div>";
} else {
    $tool_content .= "<div class='table-responsive'><table class='table-responsive'>
	<tr>
	<td width='60%' align='left'>
	<span class='row'><strong class='pagination'>&nbsp;</strong>
	</span></td>
	<td align='right'>
	<span class='pages'>";
    if ($total > $posts_per_page) {
        $tool_content .= "&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=0'>$langPages</a>";
    } else {
        $tool_content .= '&nbsp;';
    }
    $tool_content .= "</span></td></tr></table></div>";
}
draw($tool_content, 2, null, $head_content);
