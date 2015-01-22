<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014 Greek Universities Network - GUnet
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

include '../../include/baseTheme.php';
require_once 'include/lib/hierarchy.class.php';

$countCallback = null;
$isInOpenCoursesMode = (defined('LISTING_MODE') && LISTING_MODE === 'COURSE_METADATA');

if ($isInOpenCoursesMode) {
    require_once 'modules/course_metadata/CourseXML.php';
    $countCallback = CourseXMLElement::getCountCallback();
    // exit if feature disabled
    if (!get_config('opencourses_enable')) {
        header("Location: {$urlServer}");
        exit();
    }
}

$tree = new Hierarchy();
$pageName = $langListCourses;
$navigation[] = array('url' => 'listfaculte.php', 'name' => $langSelectFac);

if (isset($_GET['fc'])) {
    $fc = intval($_GET['fc']);
}

// parse the faculte id in a session
// This is needed in case the user decides to switch language.
if (isset($fc)) {
    $_SESSION['fc_memo'] = $fc;
} else {
    $fc = $_SESSION['fc_memo'];
}


$fac = Database::get()->querySingle("SELECT name FROM hierarchy WHERE id = ?d", $fc)->name;
if (!($fac = $fac[0])) {
    die("ERROR: no faculty with id $fc");
}


// use the following array for the legend icons
$icons = array(
    2 => "<img src='$themeimg/lock_open.png' alt='" . $langOpenCourse . "' title='" . $langOpenCourse . "' width='16' height='16' />",
    1 => "<img src='$themeimg/lock_registration.png' alt='" . $langRegCourse . "' title='" . $langRegCourse . "' width='16' height='16' />",
    0 => "<img src='$themeimg/lock_closed.png' alt='" . $langClosedCourse . "' title='" . $langClosedCourse . "' width='16' height='16' />"
);

if (count($tree->buildRootsArray()) > 1) {
    $tool_content .= $tree->buildRootsSelectForm($fc);
}

$tool_content .= "<table class='table table-striped table-bordered table-hover'>
                    <tr>
                    <th><a name='top'></a>$langFaculty:&nbsp;<b>" . $tree->getFullPath($fc, false, $_SERVER['SCRIPT_NAME'] . '?fc=') . "</b></th>
                    </tr>
                  </table>";

$tool_content .= $tree->buildDepartmentChildrenNavigationHtml($fc, 'opencourses', $countCallback);

$queryCourseIds = '';
$queryExtraSelect = '';
$queryExtraJoin = '';
$queryExtraJoinWhere = '';
$runQuery = true;

if ($isInOpenCoursesMode) {
    // find subnode's certified opencourses
    $opencourses = array();
    Database::get()->queryFunc("SELECT course.id, course.code
                                  FROM course, course_department, course_review
                                 WHERE course.id = course_department.course
                                   AND course.id = course_review.course_id
                                   AND course_department.department = ?d
                                   AND course_review.is_certified = 1", function($course) use (&$opencourses) {
        $opencourses[$course->id] = $course->code;
    }, $fc);

    // construct comma seperated string with open courses ids
    $commaIds = "";
    $i = 0;
    foreach ($opencourses as $courseId => $courseCode) {
        if ($i != 0) {
            $commaIds .= ",";
        }
        $commaIds .= $courseId;
        $i++;
    }

    if (count($opencourses) > 0) {
        $queryCourseIds = " AND course.id IN ($commaIds) ";
        $queryExtraJoin = ", course_review ";
        $queryExtraJoinWhere = " AND course.id = course_review.course_id ";
        $queryExtraSelect = " , course_review.level level ";
    } else {
        $runQuery = false; // left the rest of the code fail safely
    }
}

$courses = array();

if ($runQuery) {
    Database::get()->queryFunc("SELECT course.code k,
                               course.public_code c,
                               course.title i,
                               course.visible visible,
                               course.prof_names t,
                               course.id id
                               $queryExtraSelect
                          FROM course, course_department $queryExtraJoin
                         WHERE course.id = course_department.course
                           $queryExtraJoinWhere
                           AND course_department.department = ?d
                           AND course.visible != ?d
                           $queryCourseIds
                      ORDER BY course.title, course.prof_names", function ($course) use (&$courses) {
        $courses[] = $course;
    }, $fc, COURSE_INACTIVE );
}

if (count($courses) > 0) {
    $tool_content .= "<table class='table table-striped table-bordered table-hover'>        
                <tr><th class='text-left'>" . q($m['lessoncode']) . "</th>";

    if ($isInOpenCoursesMode) {
        $tool_content .= "<th class='text-left' width='220'>" . q($m['professor']) . "</th>
                          <th width='30'>$langOpenCoursesLevel</th>";
    } else {
        $tool_content .= "<th class='left' width='200'>" . q($m['professor']) . "</th>
                          <th width='30'>$langType</th>";
    }

    $tool_content .= "</tr>";
    
    foreach ($courses as $mycours) {
        if ($mycours->visible == COURSE_OPEN) {
            $codelink = "<a href='../../courses/" . urlencode($mycours->k) . "/'>" . q($mycours->i) . "</a>&nbsp;<small>(" . q($mycours->c) . ")</small>";
        } else {
            $codelink = q($mycours->i) . "&nbsp;<small>(" . q($mycours->c) . ")</small>";
        }
                
        $tool_content .= "<td>" . $codelink . "</td>";
        $tool_content .= "<td>" . q($mycours->t) . "</td>";
        $tool_content .= "<td class='text-center'>";

        if ($isInOpenCoursesMode) {
            $tool_content .= CourseXMLElement::getLevel($mycours->level) . "&nbsp;";
            $tool_content .= "<a href='javascript:showMetadata(\"" . $mycours->k . "\");'><img src='${themeimg}/lom.png'/></a>";
        } else {
            // show the necessary access icon
            foreach ($icons as $visible => $image) {
                if ($visible == $mycours->visible) {
                    $tool_content .= $image;
                }
            }
        }
        $tool_content .= "</td>";
        $tool_content .= "</tr>";        
    }
    $tool_content .= "</table>";
} else {
    $subTrees = $tree->buildSubtrees(array($fc));
    if (count($subTrees) <= 1) { // is leaf
        $tool_content .= "<div class='alert alert-warning'>" . $langNoCoursesAvailable . "</div>";
    }
}

if ($isInOpenCoursesMode) {
    $head_content .= <<<EOF
<link rel="stylesheet" type="text/css" href="course_metadata.css">
<style type="text/css"></style>
<script type="text/javascript">
/* <![CDATA[ */

    var dialog;
    
    var showMetadata = function(course) {
        $('.modal-body', dialog).load('anoninfo.php', {course: course}, function(response, status, xhr) {
            if (status === "error") {
                $('.modal-body', dialog).html("Sorry but there was an error, please try again");
                //console.debug("jqxhr Request Failed, status: " + xhr.status + ", statusText: " + xhr.statusText);
            }
        });
        dialog.modal('show');
    };
        
    $(document).ready(function() {
        dialog = $('<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal-label" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">{$langCancel}</span></button><h4 class="modal-title" id="modal-label">{$langCourseMetadata}</h4></div><div class="modal-body">body</div></div></div></div>');
    });

/* ]]> */
</script>
EOF;
}

draw($tool_content, (isset($uid) and $uid) ? 1 : 0, null, $head_content);