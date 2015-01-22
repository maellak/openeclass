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

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Blog';
require_once '../comments/class.comment.php';
require_once '../comments/class.commenting.php';
require_once '../rating/class.rating.php';
require_once '../../include/baseTheme.php';
require_once 'class.blog.php';
require_once 'class.blogpost.php';
require_once 'include/course_settings.php';
require_once 'modules/sharing/sharing.php';

define ('RSS', 'modules/blog/rss.php?course='.$course_code);
load_js('tools.js');

$pageName = $langBlog;

$head_content .= '<script type="text/javascript">var langEmptyGroupName = "' .
		$langEmptyBlogPostTitle . '";</script>';

//check if commenting is enabled for blogs
$comments_enabled = setting_get(SETTING_BLOG_COMMENT_ENABLE, $course_id);
//check if rating is enabled for blogs
$ratings_enabled = setting_get(SETTING_BLOG_RATING_ENABLE, $course_id);

$sharing_allowed = is_sharing_allowed($course_id); 
$sharing_enabled = setting_get(SETTING_BLOG_SHARING_ENABLE, $course_id);

if ($comments_enabled == 1) {
    commenting_add_js(); //add js files needed for comments
}

//define allowed actions
$allowed_actions = array("showBlog", "showPost", "createPost", "editPost", "delPost", "savePost", "settings");

//initialize $_REQUEST vars
$action = (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $allowed_actions))? $_REQUEST['action'] : "showBlog";
$pId = isset($_REQUEST['pId']) ? intval($_REQUEST['pId']) : 0;
$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;

//config setting allowing students to create posts and edit/delete own posts
$stud_allow_create = setting_get(SETTING_BLOG_STUDENT_POST, $course_id);

$posts_per_page = 10;
$num_popular = 5;//number of popular blog posts to show in sidebar
$num_chars_teaser_break = 500;//chars before teaser break

$navigation[] = array("url" => "index.php?course=$course_code", "name" => $langBlog);

if ($is_editor) {
    $tool_content .= action_bar(array(
                         array('title' => $langBack,
                               'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=showBlog",
                               'icon' => 'fa-reply',
                               'level' => 'primary-label',
                               'show' => isset($action) and $action != "showBlog" and $action != "showPost" and $action != "savePost" and $action != "delPost")
    ));
    if ($action == "settings") {
        if (isset($_POST['submitSettings'])) {
            setting_set(SETTING_BLOG_STUDENT_POST, $_POST['1_radio'], $course_id);
            setting_set(SETTING_BLOG_COMMENT_ENABLE, $_POST['2_radio'], $course_id);
            setting_set(SETTING_BLOG_RATING_ENABLE, $_POST['3_radio'], $course_id);
		    if (isset($_POST['4_radio'])) {
                setting_set(SETTING_BLOG_SHARING_ENABLE, $_POST['4_radio'], $course_id);
		    }
            $message = "<div class='alert alert-success'>$langRegDone</div>";
        }
        
        if (isset($message) && $message) {
        	$tool_content .= $message . "<br/>";
        	unset($message);
        }
        
        
        
        if (setting_get(SETTING_BLOG_STUDENT_POST, $course_id) == 1) {
            $checkTeach = "";
            $checkStud = "checked ";
        } else {
            $checkTeach = "checked ";
            $checkStud = "";
        }
        if (setting_get(SETTING_BLOG_COMMENT_ENABLE, $course_id) == 1) {
        	$checkDis = "";
        	$checkEn = "checked ";
        } else {
        	$checkDis = "checked ";
        	$checkEn = "";
        }
        if (setting_get(SETTING_BLOG_RATING_ENABLE, $course_id) == 1) {
        	$checkDis = "";
        	$checkEn = "checked ";
        } else {
        	$checkDis = "checked ";
        	$checkEn = "";
        }
        if (!$sharing_allowed) {
            $radio_dis = " disabled";
            $sharing_dis_label = "<tr><td><em>";
            if (!get_config('enable_social_sharing_links')) {
                $sharing_dis_label .= $langSharingDisAdmin;
            }
            if (course_status($course_id) != COURSE_OPEN) {
                $sharing_dis_label .= " ".$langSharingDisCourse;
            }
            $sharing_dis_label .= "</em></td></tr>";
        } else {
            $radio_dis = "";
            $sharing_dis_label = "";
        }
		
        if ($sharing_enabled == 1) {
            $checkDis = "";
            $checkEn = "checked";
        } else {
            $checkDis = "checked";
            $checkEn = "";
        }
        
        
        $tool_content .= "
            <div class='row'>
                <div class='col-sm-12'>
                    <div class='form-wrapper'>
                        <form class='form-horizontal' action='' role='form' method='post'>
                            <fieldset>                               
                                <div class='form-group'>
                                    <label class='col-sm-3'>$langBlogPerm</label>
                                    <div class='col-sm-9'> 
                                        <div class='radio'>
                                            <label>
                                                <input type='radio' value='0' name='1_radio' $checkTeach>$langBlogPermTeacher
                                            </label>
                                        </div>
                                        <div class='radio'>
                                            <label>
                                                <input type='radio' value='1' name='1_radio' $checkStud>$langBlogPermStudents
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset>
                                <div class='form-group'>
                                    <label class='col-sm-3'>$langCommenting</label>
                                    <div class='col-sm-9'>    
                                        <div class='radio'>
                                            <label>
                                                <input type='radio' value='1' name='2_radio' $checkEn>$langCommentsEn
                                            </label>
                                        </div>
                                        <div class='radio'>
                                            <label>
                                                <input type='radio' value='0' name='2_radio' $checkDis>$langCommentsDis
                                            </label>
                                        </div>
                                    </div>
                                </div>                            
                                <div class='form-group'>
                                    <label class='col-sm-3'>$langRating:</label>
                                    <div class='col-sm-9'>
                                        <div class='radio'>
                                            <label>
                                                <input type='radio' value='1' name='3_radio' $checkEn>$langRatingEn
                                            </label>
                                        </div>
                                        <div class='radio'>
                                            <label>
                                                <input type='radio' value='0' name='3_radio' $checkDis>$langRatingDis
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class='form-group'>
                                    <label class='col-sm-3'>$langSharing:</label>
                                    <div class='col-sm-9'>
                                        <div class='radio'>
                                            <label>
                                                <input type='radio' value='1' name='4_radio' $checkEn $radio_dis>$langSharingEn
                                            </label>
                                        </div>
                                        <div class='radio'>
                                            <label>
                                                <input type='radio' value='0' name='4_radio' $checkDis $radio_dis>$langSharingDis
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class='form-group'>
                                <div class='col-sm-10 col-sm-offset-2'>
                                  <input type='submit' class='btn btn-primary' name='submitSettings' value='$langSubmit'>
                                  <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=showBlog' class='btn btn-default'>$langCancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>";
        
        
        
    }
}

//instantiate the object representing this blog
$blog = new Blog($course_id, 0);

//delete post
if ($action == "delPost") {
    $post = new BlogPost();
    if ($post->loadFromDB($pId)) {
        if ($post->permEdit($is_editor, $stud_allow_create, $uid)) {
            if($post->delete()) {
                Session::Messages($langBlogPostDelSucc, 'alert-success');
            } else {
                Session::Messages($langBlogPostDelFail);
            }
        } else {
            Session::Messages($langBlogPostNotAllowedDel);
        }
    } else {
        Session::Messages($langBlogPostNotFound);      
    }
    redirect_to_home_page("modules/blog/index.php?course=$course_code");
}

//create blog post form
if ($action == "createPost") {
    if ($blog->permCreate($is_editor, $stud_allow_create, $uid)) {
        $tool_content .= "
        <div class='form-wrapper'>
            <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=".$course_code."' onsubmit=\"return checkrequired(this, 'blogPostTitle');\">
            <fieldset>
                <div class='form-group'>
                    <label for='blogPostTitle' class='col-sm-2 control-label'>$langBlogPostTitle:</label>
                    <div class='col-sm-10'>
                        <input class='form-control' type='text' name='blogPostTitle' id='blogPostTitle' placeholder='$langBlogPostTitle'>
                    </div>
                </div>
                <div class='form-group'>
                    <label for='newContent' class='col-sm-2 control-label'>$langBlogPostBody:</label>
                    <div class='col-sm-10'>
                        ".rich_text_editor('newContent', 4, 20, '')."
                    </div>
                </div> 
                <div class='form-group'>
                    <div class='col-sm-10 col-sm-offset-2'>
                        <input class='btn btn-primary' type='submit' name='submitBlogPost' value='$langAdd'>
                        <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=showBlog' class='btn btn-default'>$langCancel</a>
                    </div>
                </div>          
                <input type='hidden' name='action' value='savePost' />
            </fieldset>
            </form>
        </div>";
    } else {
        Session::Messages($langBlogPostNotAllowedCreate);
        redirect_to_home_page("modules/blog/index.php?course=$course_code");
    }
    
}

//edit blog post form
if ($action == "editPost") {
    $post = new BlogPost();
    if ($post->loadFromDB($pId)) {
        if ($post->permEdit($is_editor, $stud_allow_create, $uid)) {
            $tool_content .= "
            <div class='form-wrapper'>
                <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=".$course_code."' onsubmit=\"return checkrequired(this, 'blogPostTitle');\">
                <fieldset>
                <div class='form-group'>
                    <label for='blogPostTitle' class='col-sm-2 control-label'>$langBlogPostTitle:</label>
                    <div class='col-sm-10'>
                        <input class='form-control' type='text' name='blogPostTitle' id='blogPostTitle' value='".q($post->getTitle())."' placeholder='$langBlogPostTitle'>
                    </div>
                </div>
                <div class='form-group'>
                    <label for='newContent' class='col-sm-2 control-label'>$langBlogPostBody:</label>
                    <div class='col-sm-10'>
                        ".rich_text_editor('newContent', 4, 20, '', $post->getContent())."
                    </div>
                </div>
                <div class='form-group'>
                    <div class='col-sm-10 col-sm-offset-2'>
                        <input class='btn btn-primary' type='submit' name='submitBlogPost' value='$langModifBlogPost'>
                        <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=showBlog' class='btn btn-default'>$langCancel</a>
                    </div>
                </div>              
                <input type='hidden' name='action' value='savePost'>
                <input type='hidden' name='pId' value='".$post->getId()."'>
                </fieldset>
            </form>
        </div>";
        } else {
            Session::Messages($langBlogPostNotAllowedEdit);
            redirect_to_home_page("modules/blog/index.php?course=$course_code");            
        }
    } else {
        Session::Messages($langBlogPostNotFound);
        redirect_to_home_page("modules/blog/index.php?course=$course_code");        
    }
}

//save blog post
if ($action == "savePost") {
    
    if (isset($_POST['submitBlogPost']) && $_POST['submitBlogPost'] == $langAdd) {
        if ($blog->permCreate($is_editor, $stud_allow_create, $uid)) {
            $post = new BlogPost();
            if ($post->create($_POST['blogPostTitle'], purify($_POST['newContent']), $uid, $course_id)) {
                Session::Messages($langBlogPostSaveSucc, 'alert-success');
            } else {
                Session::Messages($langBlogPostSaveFail);
            }
        } else {
            Session::Messages($langBlogPostNotAllowedCreate);
        }
    } elseif (isset($_POST['submitBlogPost']) && $_POST['submitBlogPost'] == $langModifBlogPost) {
        $post = new BlogPost();
        if ($post->loadFromDB($_POST['pId'])) {
            if ($post->permEdit($is_editor, $stud_allow_create, $uid)) {
                if ($post->edit($_POST['blogPostTitle'], purify($_POST['newContent']))) {
                    Session::Messages($langBlogPostSaveSucc, 'alert-success');
                } else {
                    Session::Messages($langBlogPostSaveFail);
                }
            } else {
                Session::Messages($langBlogPostNotAllowedEdit);
            }
        } else {
            Session::Messages($langBlogPostNotFound);                      
        }
    } 
    redirect_to_home_page("modules/blog/index.php?course=$course_code");      
}

if (isset($message) && $message) {
    $tool_content .= $message . "<br/>";
}

//show blog post
if ($action == "showPost") {
    $tool_content .= action_bar(array(
            array('title' => $langBack,
                    'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=showBlog",
                    'icon' => 'fa-reply',
                    'level' => 'primary-label',
                    'show' => $blog->permCreate($is_editor, $stud_allow_create, $uid))
    ));
    $post = new BlogPost();
    if ($post->loadFromDB($pId)) {
        $post->incViews();
        $sharing_content = '';
        $rating_content = '';
        if ($sharing_allowed) {
            $sharing_content = ($sharing_enabled) ? print_sharing_links($urlServer."modules/blog/index.php?course=$course_code&amp;action=showPost&amp;pId=".$post->getId(), $post->getTitle()) : '';
        }
        if ($ratings_enabled) {
            $rating = new Rating('up_down', 'blogpost', $post->getId());
            $rating_content = $rating->put($is_editor, $uid, $course_id);
        }        
        $tool_content .= "<div class='panel panel-action-btn-default'>
                            <div class='panel-heading'>
                                <div class='pull-right'>
                                    ". action_button(array(
                                        array(
                                            'title' => $langModify,
                                            'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=editPost&amp;pId=".$post->getId(),
                                            'icon' => 'fa-edit',
                                            'show' => $post->permEdit($is_editor, $stud_allow_create, $uid)
                                        ),
                                        array(
                                            'title' => $langDelete,
                                            'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=delPost&amp;pId=".$post->getId(),
                                            'icon' => 'fa-times',
                                            'class' => 'delete',
                                            'confirm' => $langSureToDelBlogPost,
                                            'show' => $post->permEdit($is_editor, $stud_allow_create, $uid)
                                        )                                        
                                    ))."
                                </div>
                                <h3 class='panel-title'>
                                    ".q($post->getTitle())."
                                </h3>
                            </div>
                            <div class='panel-body'><div class='label label-success'>" . nice_format($post->getTime(), true). "</div><small>".$langBlogPostUser.display_user($post->getAuthor(), false, false)."</small><br><br>".standard_text_escape($post->getContent())."</div>
                            <div class='panel-footer'>
                                <div class='row'>
                                    <div class='col-sm-6'>$rating_content</div>
                                    <div class='col-sm-6 text-right'>$sharing_content</div>
                                </div>
                            </div>
                        </div>";
        

        

        
        if ($comments_enabled) {
            $comm = new Commenting('blogpost', $post->getId());
            $tool_content .= $comm->put($course_code, $is_editor, $uid, true);
        }
        
    } else {
        Session::Messages($langBlogPostNotFound);
        redirect_to_home_page("modules/blog/index.php?course=$course_code");  
    }

}

//show all blog posts
if ($action == "showBlog") {
    $tool_content .= action_bar(array(
                        array('title' => $langBlogAddPost,
                              'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=createPost",
                              'icon' => 'fa-plus-circle',
                              'level' => 'primary-label',
                              'button-class' => 'btn-success',
                              'show' => $blog->permCreate($is_editor, $stud_allow_create, $uid)),
                        array('title' => $langConfig,
                              'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=settings",
                              'icon' => 'fa-gear',
                              'level' => 'primary',
                              'show' => $is_editor && $blog->permCreate($is_editor, $stud_allow_create, $uid))
                     ));
    
    $num_posts = $blog->blogPostsNumber();
    if ($num_posts == 0) {//no blog posts
        $tool_content .= "<div class='alert alert-warning'>$langBlogEmpty</div>";
    } else {//show blog posts
        //if page num was changed at the url and exceeds pages number show the first page
        if ($page > ceil($num_posts/$posts_per_page)-1) {
            $page = 0;
        }
        
        //retrieve blog posts
        $posts = $blog->getBlogPostsDB($page, $posts_per_page);
                
        /***blog posts area***/
        $tool_content .= "<div class='row'>";
        $tool_content .= "<div class='col-sm-9'>";
        foreach ($posts as $post) {
            $sharing_content = '';
            $rating_content = '';
            if ($sharing_allowed) {
                $sharing_content = ($sharing_enabled) ? print_sharing_links($urlServer."modules/blog/index.php?course=$course_code&amp;action=showPost&amp;pId=".$post->getId(), $post->getTitle()) : '';
            }            
            if ($ratings_enabled) {
                $rating = new Rating('up_down', 'blogpost', $post->getId());
                $rating_content = $rating->put($is_editor, $uid, $course_id);
            }
            if ($comments_enabled) {
                $comm = new Commenting('blogpost', $post->getId());
                $comment_content = "<a class='btn btn-primary btn-xs pull-right' href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=showPost&amp;pId=".$post->getId()."#comments_title'>$langComments (".$comm->getCommentsNum().")</a>";
            } else {
                $comment_content = "<div class=\"blog_post_empty_space\"></div>";
            }            
            $tool_content .= "<div class='panel panel-action-btn-default'>
                                <div class='panel-heading'>
                                    <div class='pull-right'>
                                        ". action_button(array(
                                            array(
                                                'title' => $langModify,
                                                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=editPost&amp;pId=".$post->getId(),
                                                'icon' => 'fa-edit',
                                                'show' => $post->permEdit($is_editor, $stud_allow_create, $uid)
                                            ),
                                            array(
                                                'title' => $langDelete,
                                                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=delPost&amp;pId=".$post->getId(),
                                                'icon' => 'fa-times',
                                                'class' => 'delete',
                                                'confirm' => $langSureToDelBlogPost,
                                                'show' => $post->permEdit($is_editor, $stud_allow_create, $uid)
                                            )                                        
                                        ))."
                                    </div>
                                    <h3 class='panel-title'>
                                        <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=showPost&amp;pId=".$post->getId()."'>".q($post->getTitle())."</a>
                                    </h3>                                    
                                </div>
                                <div class='panel-body'>
                                    <div class='label label-success'>" . nice_format($post->getTime(), true). "</div><small>".$langBlogPostUser.display_user($post->getAuthor(), false, false)."</small><br><br>".standard_text_escape(ellipsize_html($post->getContent(), $num_chars_teaser_break, "<strong>&nbsp;...<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=showPost&amp;pId=".$post->getId()."'> <span class='smaller'>[$langMore]</span></a></strong>"))."
                                    $comment_content
                                </div>
                                <div class='panel-footer'>
                                    <div class='row'>
                                        <div class='col-sm-6'>$rating_content</div>
                                        <div class='col-sm-6 text-right'>$sharing_content</div>
                                    </div>                                    
                                </div>
                             </div>";            
        }
        
        
        //display navigation links
        $tool_content .= $blog->navLinksHTML($page, $posts_per_page);
        
        $tool_content .= "</div>";
        /***end of blog posts area***/
        
        
        /***sidebar area***/
        $tool_content .= "<div class='col-sm-3'>";
        $tool_content .= $blog->popularBlogPostsHTML($num_popular);
        $tool_content .= $blog->chronologicalTreeHTML(date('n',strtotime($posts[0]->getTime())), date('Y',strtotime($posts[0]->getTime())));
        
        $tool_content .= "</div></div>";
        /***end of sidebar area***/
    }
}

draw($tool_content, 2, null, $head_content);