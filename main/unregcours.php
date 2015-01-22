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


$require_login = TRUE;
include '../include/baseTheme.php';
require_once 'include/log.php';

$pageName = $langUnregCourse;

if (isset($_GET['cid'])) {
    $cid = q($_GET['cid']);
    $_SESSION['cid_tmp'] = $cid;
}
if (!isset($_GET['cid'])) {
    $cid = $_SESSION['cid_tmp'];
}

if (!isset($_GET['doit']) or $_GET['doit'] != "yes") {
    $tool_content .= "
          <table width='100%'>
          <tbody>
          <tr>
            <td class='alert alert-danger' height='60' colspan='2'>
              <p>$langConfirmUnregCours:</p><p> <em>" . q(course_id_to_title($cid)) . "</em>;&nbsp;</p>
              <ul class='listBullet'>
              <li>$langYes:
              <a href='$_SERVER[SCRIPT_NAME]?u=$_SESSION[uid]&amp;cid=$cid&amp;doit=yes' class=mainpage>$langUnregCourse</a>
              </li>
              <li>$langNo: <a href='../index.php' class=mainpage>$langBack</a>
              </li></ul>
            </td>
          </tr>
          </tbody>
          </table>";
} else {
    if (isset($_SESSION['uid']) and $_GET['u'] == $_SESSION['uid']) {
        $q = Database::get()->query("DELETE from course_user
                                    WHERE course_id = ?d
                                    AND user_id = ?d", $cid, $_GET['u']);
        if ($q->affectedRows > 0) {
            Log::record($cid, MODULE_ID_USERS, LOG_DELETE, array('uid' => $_GET['u'],
                                                                 'right' => 0));
            $code = course_id_to_code($cid);
            // clear session access to lesson
            unset($_SESSION['dbname']);
            unset($_SESSION['cid_tmp']);
            unset($_SESSION['courses'][$code]);
            $tool_content .= "<div class='alert alert-success'>$langCoursDelSuccess</div>";
        } else {
            $tool_content .= "<div class='alert alert-danger'>$langCoursError</div>";
        }
    }
    $tool_content .= "<br><br><div align=right><a href='../index.php' class=mainpage>$langBack</a></div>";
}

if (isset($_SESSION['uid'])) {
    draw($tool_content, 1);
} else {
    draw($tool_content, 0);
}
