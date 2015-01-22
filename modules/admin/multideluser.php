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


$require_usermanage_user = TRUE;
include '../../include/baseTheme.php';
require_once 'include/lib/user.class.php';
require_once 'include/lib/hierarchy.class.php';
require_once 'hierarchy_validations.php';

$tree = new Hierarchy();
$user = new User();

$toolName = $langMultiDelUser;
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
load_js('tools.js');


if (isset($_POST['submit'])) {

    $line = strtok($_POST['user_names'], "\n");

    while ($line !== false) {
        // strip comments
        $line = preg_replace('/#.*/', '', trim($line));

        if (!empty($line)) {
            // fetch uid
            $u = usernameToUid($line);

            // for real uids not equal to admin
            if ($u !== false && $u > 1) {
                // full deletion
                $success = deleteUser($u, true);
                // progress report
                if ($success === true) {
                    Session::Messages("$langUserWithId $line $langWasDeleted", 'alert-success');
                    redirect_to_home_page('modules/admin/multideluser.php');
                } else {
                    Session::Messages("$langErrorDelete: $line", 'alert-danger');
                    redirect_to_home_page('modules/admin/multideluser.php');
                }
            }
        }
    }
    redirect_to_home_page('modules/admin/multideluser.php');
} else {

    $usernames = '';

    if (isset($_POST['dellall_submit'])) {
        // get the incoming values
        $search = isset($_POST['search']) ? $_POST['search'] : '';
        $c = isset($_POST['c']) ? intval($_POST['c']) : '';
        $lname = isset($_POST['lname']) ? $_POST['lname'] : '';
        $fname = isset($_POST['fname']) ? $_POST['fname'] : '';
        $uname = isset($_POST['uname']) ? canonicalize_whitespace($_POST['uname']) : '';
        $am = isset($_POST['am']) ? $_POST['am'] : '';
        $verified_mail = isset($_POST['verified_mail']) ? intval($_POST['verified_mail']) : 3;
        $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : '';
        $auth_type = isset($_POST['auth_type']) ? $_POST['auth_type'] : '';
        $email = isset($_POST['email']) ? mb_strtolower(trim($_POST['email'])) : '';
        $reg_flag = isset($_POST['reg_flag']) ? intval($_POST['reg_flag']) : '';
        $hour = isset($_POST['hour']) ? $_POST['hour'] : 0;
        $minute = isset($_POST['minute']) ? $_POST['minute'] : 0;

        // Criteria/Filters
        $criteria = array();
        $terms = array();

        if (isset($_POST['date']) or $hour or $minute) {
            $date = explode('-', $_POST['date']);
            if (count($date) == 3) {
                $day = intval($date[0]);
                $month = intval($date[1]);
                $year = intval($date[2]);
                $user_registered_at = mktime($hour, $minute, 0, $month, $day, $year);
            } else {
                $user_registered_at = mktime($hour, $minute, 0, 0, 0, 0);
            }
            $criteria[] = 'registered_at ' . (($reg_flag === 1) ? '>=' : '<=') . ' ' . $user_registered_at;
        }

        if (!empty($lname)) {
            $criteria[] = 'surname LIKE ?s';
            $terms[] = '%' . $lname . '%';
        }

        if (!empty($fname)) {
            $criteria[] = 'givenname LIKE ?s';
            $terms[] = '%' . $fname . '%';
        }

        if (!empty($uname)) {
            $criteria[] = 'username LIKE ?s';
            $terms[] = '%' . $uname . '%';
        }

        if ($verified_mail === EMAIL_VERIFICATION_REQUIRED or $verified_mail === EMAIL_VERIFIED or $verified_mail === EMAIL_UNVERIFIED)
            $criteria[] = 'verified_mail = ?d';
        $terms[] = $verified_mail;
    }

    if (!empty($am)) {
        $criteria[] = 'am LIKE ?d';
        $terms[] = '%' . $am . '%';
    }

    if (!empty($user_type)) {
        $criteria[] = 'status = ?d';
        $terms[] = $user_type;
    }

    if (!empty($auth_type)) {
        if ($auth_type >= 2) {
            $criteria[] = 'password = ?s';
            $terms[] = $auth_ids[$auth_type];
        } elseif ($auth_type == 1) {
            $criteria[] = 'password NOT IN (' . implode(', ', array_fill(0, count($auth_ids), '?s')) . ')';
            $terms = array_merge($terms, $auth_ids);
        }
    }

    if (!empty($email)) {
        $criteria[] = 'email LIKE ?s';
        $terms[] = '%' . $email . '%';

        if ($search == 'inactive') {
            $criteria[] = 'expires_at < ' . DBHelper::timeAfter();
        }

        // Department search
        $depqryadd = '';
        $dep = (isset($_POST['department'])) ? intval($_POST['department']) : 0;
        if ($dep || isDepartmentAdmin()) {
            $depqryadd = ', user_department';

            $subs = array();
            if ($dep) {
                $subs = $tree->buildSubtrees(array($dep));
            } else if (isDepartmentAdmin()) {
                $subs = $user->getDepartmentIds($uid);
            }

            $count = 0;
            foreach ($subs as $key => $id) {
                $terms[] = $id;
                validateNode($id, isDepartmentAdmin());
                $count++;
            }

            $pref = ($c) ? 'a' : 'user';
            $criteria[] = $pref . '.user.id = user_department.user';
            $criteria[] = 'department IN (' . array_fill(0, $count, '?s') . ')';
        }

        $qry_criteria = (count($criteria)) ? implode(' AND ', $criteria) : '';
        // end filter/criteria

        if (!empty($c)) {
            $qry_base = " FROM user AS a LEFT JOIN course_user AS b ON a.id = b.user_id $depqryadd WHERE b.course_id = ?d ";
            array_unshift($terms, $c);
            if ($qry_criteria) {
                $qry_base .= ' AND ' . $qry_criteria;
            }
            $qry = "SELECT DISTINCT a.username " . $qry_base . " ORDER BY a.username ASC";
        } elseif ($search == 'no_login') {
            $qry_base = " FROM user LEFT JOIN loginout ON user.id = loginout.id_user $depqryadd WHERE loginout.id_user IS NULL ";
            if ($qry_criteria) {
                $qry_base .= ' AND ' . $qry_criteria;
            }
            $qry = "SELECT DISTINCT username " . $qry_base . ' ORDER BY username ASC';
        } else {
            $qry_base = ' FROM user' . $depqryadd;
            if ($qry_criteria) {
                $qry_base .= ' WHERE ' . $qry_criteria;
            }
            $qry = 'SELECT DISTINCT username ' . $qry_base . ' ORDER BY username ASC';
        }

        Database::get()->queryFunc($qry
                , function($users) use(&$usernames) {
            $usernames .= $users->username . "\n";
        }, $terms);
    }

$tool_content .= action_bar(array(
    array('title' => $langBack,
        'url' => "index.php",
        'icon' => 'fa-reply',
        'level' => 'primary-label')));

    $tool_content .= "
    <div class='alert alert-info'>$langMultiDelUserInfo</div>
        <div class='form-wrapper'>
        <form role='form' class='form-horizontal' method='post' action='" . $_SERVER['SCRIPT_NAME'] . "'>
            <fieldset>
                <div class='form-group'>
                    <label class='col-sm-2 control-label'>$langMultiDelUserData:</label>
                    <div class='col-sm-9'>
                        <textarea class='auth_input form-control' name='user_names' rows='30'>$usernames</textarea>
                    </div>
                </div>
                <div class='form-group'>
                    <div class='col-sm-10 col-sm-offset-2'>            
                        <input class='btn btn-primary' type='submit' name='submit' value='" . $langSubmit . "' onclick='return confirmation(\"" . $langMultiDelUserConfirm . "\");' />
                        <a href='index.php' class='btn btn-default'>$langCancel</a>
                    </div>
                </div>        
            </fieldset>
        </form>
    </div>";
}

draw($tool_content, 3, 'admin', $head_content);

// Translate username to uid
function usernameToUid($uname) {
    $r = Database::get()->querySingle("SELECT id FROM user WHERE username = ?s", $uname);
    if ($r)
        return $r->id;
    else
        return false;
}
