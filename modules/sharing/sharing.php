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
 * 
 * @param string $url
 * @param string $title
 * @param string $themimg the theme img dir
 * @return string html list with social sharing icons
 */
function print_sharing_links ($url, $text) {
    global $langShare, $themeimg, $head_content, $urlServer;
    
    $head_content .= '<link rel="stylesheet" type="text/css" href="'.$urlServer.'modules/sharing/style.css">';
    
    $out = "<div class='sharingcontainer'><span class='hidden-xs'>$langShare:</span> ";
    $out .= "<ul class='sharinglist'>";

    //facebook
    $sharer = "https://www.facebook.com/sharer/sharer.php?u=".urlencode($url);
    $out .= "<li><a href='".$sharer."' target='_blank'><img src='".$urlServer."/modules/sharing/icons/facebook.png' alt='Facebook' /></a></li>";
    //twitter
    $sharer = "https://twitter.com/intent/tweet?url=".urlencode($url)."&amp;text=".urlencode($text);
    $out .= "<li><a href='".$sharer."' target='_blank'><img src='".$urlServer."/modules/sharing/icons/twitter.png' alt='Twitter' /></a></li>";
    //google+
    $sharer = "https://plus.google.com/share?url=".urlencode($url);
    $out .= "<li><a href='".$sharer."' target='_blank'><img src='".$urlServer."/modules/sharing/icons/google+.png' alt='Google+' /></a></li>";
    //linkedin
    $sharer = "http://www.linkedin.com/shareArticle?mini=true&amp;url=".urlencode($url)."&amp;title=".urlencode($text);
    $out .= "<li><a href='".$sharer."' target='_blank'><img src='".$urlServer."/modules/sharing/icons/linkedin.png' alt='LinkedIn' /></a></li>";
    //email
    $sharer = "mailto:?subject=".urlencode($text)."&amp;body=".urlencode($url);
    $out .= "<li><a href='".$sharer."' target='_blank'><img src='".$urlServer."/modules/sharing/icons/mail.png' alt='Email' /></a></li>";
    
    $out .= "</ul>";
    $out .= "</div>";

    return $out;
}

/**
 * functions that check if sharing is allowed for a course
 * @param int $course_id
 * @return boolean
 */
function is_sharing_allowed($course_id) {
    if (get_config('enable_social_sharing_links') && course_status($course_id) == COURSE_OPEN) { //sharing is allowed only for open courses
        return true;
    }
    else {
        return false;
    }
}
