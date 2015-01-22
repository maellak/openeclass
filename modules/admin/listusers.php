<?php

/* ========================================================================
 *   Open eClass 3.0
 *   E-learning and Course Management System
 * ========================================================================
 *  Copyright(c) 2003-2014  Greek Universities Network - GUnet
 *  A full copyright notice can be read in "/info/copyright.txt".
 *
 *  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
 * 			Yannis Exidaridis <jexi@noc.uoa.gr>
 * 			Alexandros Diamantidis <adia@noc.uoa.gr>
 * 			Tilemachos Raptis <traptis@noc.uoa.gr>
 *
 *  For a full list of contributors, see "credits.txt".
 *
 *  Open eClass is an open platform distributed in the hope that it will
 *  be useful (without any warranty), under the terms of the GNU (General
 *  Public License) as published by the Free Software Foundation.
 *  The full license can be read in "/info/license/license_gpl.txt".
 *
 *  Contact address: 	GUnet Asynchronous eLearning Group,
 *  			Network Operations Center, University of Athens,
 *  			Panepistimiopolis Ilissia, 15784, Athens, Greece
 *  			eMail: info@openeclass.org
 * ======================================================================== */
/**
 * @file listusers.php
 * @brief display list of users
 */
$require_usermanage_user = true;
require_once '../../include/baseTheme.php';
require_once 'modules/auth/auth.inc.php';
require_once 'include/lib/user.class.php';
require_once 'include/lib/hierarchy.class.php';
require_once 'hierarchy_validations.php';

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $tree = new Hierarchy();
    $user = new User();
    // get the incoming values
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $c = isset($_GET['c']) ? intval($_GET['c']) : '';
    $lname = isset($_GET['lname']) ? $_GET['lname'] : '';
    $fname = isset($_GET['fname']) ? $_GET['fname'] : '';
    $uname = isset($_GET['uname']) ? canonicalize_whitespace($_GET['uname']) : '';
    $am = isset($_GET['am']) ? $_GET['am'] : '';
    $verified_mail = isset($_GET['verified_mail']) ? intval($_GET['verified_mail']) : 3;
    $user_type = isset($_GET['user_type']) ? intval($_GET['user_type']) : '';
    $auth_type = isset($_GET['auth_type']) ? intval($_GET['auth_type']) : '';
    $email = isset($_GET['email']) ? mb_strtolower(trim($_GET['email'])) : '';
    $reg_flag = isset($_GET['reg_flag']) ? intval($_GET['reg_flag']) : '';
    $user_registered_at = isset($_GET['user_registered_at']) ? $_GET['user_registered_at'] : '';
    $mail_ver_required = get_config('email_verification_required');
    // pagination
    $limit = intval($_GET['iDisplayLength']);
    $offset = intval($_GET['iDisplayStart']);

    // 'LIKE' argument prefix/postfix - default is substring search
    $l1 = $l2 = '%';
    if (isset($_GET['search_type'])) {
        if ($_GET['search_type'] == 'exact') {
            $l1 = $l2 = '';
        } elseif ($_GET['search_type'] == 'begin') {
            $l1 = '';
        }
    }

    /*
      Criteria/Filters
     */
    $criteria = array();
    $terms = array();
    $params = array();
    // Registration date/time search
    if (!empty($user_registered_at)) {
        add_param('reg_flag');
        add_param('user_registered_at');
        // join the above with registered at search
        $criteria[] = 'registered_at ' . (($reg_flag === 1) ? '>=' : '<=') . ' ?s';
        $date_user_registered_at = DateTime::createFromFormat("d-m-Y H:i", $user_registered_at);
        $terms[] = $date_user_registered_at->format("Y-m-d H:i:s");
    }
    // surname search
    if (!empty($lname)) {
        $criteria[] = 'surname LIKE ?s';
        $terms[] = $l1 . $lname . $l2;
        add_param('lname');
    }
    // first name search
    if (!empty($fname)) {
        $criteria[] = 'givenname LIKE ?s';
        $terms[] = $l1 . $fname . $l2;
        add_param('fname');
    }
    // username search
    if (!empty($uname)) {
        $criteria[] = 'username LIKE ?s';
        $terms[] = $l1 . $uname . $l2;
        add_param('uname');
    }
    // mail verified
    if ($verified_mail === EMAIL_VERIFICATION_REQUIRED or
            $verified_mail === EMAIL_VERIFIED or
            $verified_mail === EMAIL_UNVERIFIED) {
        $criteria[] = 'verified_mail = ?d';
        $terms[] = $verified_mail;
        add_param('verified_mail');
    }
    //user am search
    if (!empty($am)) {
        $criteria[] = 'am LIKE ?s';
        $terms[] = $l1 . $am . $l2;
        add_param('am');
    }
    // user type search
    if (!empty($user_type)) {
        $criteria[] = 'status = ?d';
        $terms[] = $user_type;
        add_param('user_type');
    }
    // auth type search
    if (!empty($auth_type)) {
        if ($auth_type >= 2) {
            $criteria[] = 'password = ?s';
            $terms[] = $auth_ids[$auth_type];
        } elseif ($auth_type == 1) {
            $terms[] = $auth_ids;
            $criteria[] = 'password NOT IN (' .
                    implode(', ', array_fill(0, count($auth_ids), '?s')) .
                    ')';
        }
        add_param('auth_type');
    }
    // email search
    if (!empty($email)) {
        $criteria[] = 'email LIKE ?s';
        $terms[] = $l1 . $email . $l2;
        add_param('email');
    }
    // search for inactive users
    if ($search == 'inactive') {
        $criteria[] = 'expires_at < CURRENT_DATE()';
        add_param('search', 'inactive');
    }

    // Department search
    $depqryadd = '';
    $dep = (isset($_GET['department'])) ? intval($_GET['department']) : 0;
    if ($dep || isDepartmentAdmin()) {
        $depqryadd = ', user_department';

        $subs = array();
        if ($dep) {
            $subs = $tree->buildSubtrees(array($dep));
            add_param('department', $dep);
        } else if (isDepartmentAdmin())
            $subs = $user->getDepartmentIds($uid);

        $ids = '';
        foreach ($subs as $key => $id) {
            $ids .= $id . ',';
            validateNode($id, isDepartmentAdmin());
        }
        // remove last ',' from $ids
        $deps = substr($ids, 0, -1);

        $pref = ($c) ? 'a' : 'user';
        $criteria[] = $pref . '.id = user_department.user';
        $criteria[] = 'department IN (' . $deps . ')';
    }

    if (count($criteria)) {
        $qry_criteria = implode(' AND ', $criteria);
    } else {
        $qry_criteria = '';
    }

    // end filter/criteria
    if ($c) { // users per course
        $qry_base = "FROM user AS a LEFT JOIN course_user AS b ON a.id = b.user_id
                              $depqryadd WHERE b.course_id = ?d";
        if ($qry_criteria) {
            $qry_base .= ' AND ' . $qry_criteria;
        }
        $qry = "SELECT DISTINCT a.id, a.surname, a.givenname, a.username, a.email,
                           a.verified_mail, b.status " . $qry_base;
        add_param('c');
        array_unshift($terms, $c);
    } elseif ($search == 'no_login') { // users who have never logged in
        $qry_base = "FROM user LEFT JOIN loginout ON user.id = loginout.id_user $depqryadd
                              WHERE loginout.id_user IS NULL";
        if ($qry_criteria) {
            $qry_base .= ' AND ' . $qry_criteria;
        }
        $qry = "SELECT DISTINCT user.id, surname, givenname, username, email, verified_mail, status " .
                $qry_base;
        add_param('search', 'no_login');
    } else {
        $qry_base = ' FROM user' . $depqryadd;
        if ($qry_criteria) {
            $qry_base .= ' WHERE ' . $qry_criteria;
        }
        $qry = 'SELECT DISTINCT user.id, surname, givenname, username, email, status, verified_mail' .
                $qry_base;
    }
    $terms_base[] = $terms;

    // internal search
    if (!empty($_GET['sSearch'])) {
        if (($qry_criteria) or ( $c)) {
            $qry .= ' AND (surname LIKE ?s OR givenname LIKE ?s OR username LIKE ?s OR email LIKE ?s)';
        } else {
            $qry .= ' WHERE (surname LIKE ?s OR givenname LIKE ?s OR username LIKE ?s OR email LIKE ?s)';
        }
        $keywords = array_fill(0, 4, $l1 . $_GET['sSearch'] . $l2);
        $terms = array_merge($terms, $keywords);
    } else {
        $keywords = array_fill(0, 4, '%');
    }
    // sorting
    if (!empty($_GET['iSortCol_0'])) {
        switch ($_GET['iSortCol_0']) {
            case '0': $qry .= ' ORDER BY surname ';
                break;
            case '1': $qry .= ' ORDER BY givenname ';
                break;
            case '2': $qry .= ' ORDER BY username ';
                break;
        }
        $qry .= ($_GET['sSortDir_0'] == 'desc' ? 'DESC' : '');
    } else {
        $qry .= ' ORDER BY status, surname ' .
                ($_GET['sSortDir_0'] == 'desc' ? 'DESC' : '');
    }
    //pagination
    if ($limit > 0) {
        $qry .= " LIMIT ?d, ?d";
        $terms[] = $offset;
        $terms[] = $limit;
    }
    $sql = Database::get()->queryArray($qry, $terms);

    $all_results = Database::get()->querySingle("SELECT COUNT(*) AS total $qry_base", $terms_base)->total;
    if ($qry_criteria or $c) {
        $filtered_results = Database::get()->querySingle("SELECT COUNT(*) AS total $qry_base
                                                         AND (surname LIKE ?s
                                                             OR givenname LIKE ?s
                                                             OR username LIKE ?s
                                                             OR email LIKE ?s)", $terms_base, $keywords)->total;
    } else {
        $filtered_results = Database::get()->querySingle("SELECT COUNT(*) AS total FROM user
                                                         WHERE (surname LIKE ?s
                                                                OR givenname LIKE ?s
                                                                OR username LIKE ?s
                                                                OR email LIKE ?s)", $keywords)->total;
    }
    $data['iTotalRecords'] = $all_results;
    $data['iTotalDisplayRecords'] = $filtered_results;
    $data['aaData'] = array();

    foreach ($sql as $logs) {
        $email_icon = $logs->email;
        if ($mail_ver_required) {
            switch ($logs->verified_mail) {
                case EMAIL_VERIFICATION_REQUIRED:
                    $icon = 'fa-clock-o';
                    $tip = $langMailVerificationPendingU;
                    break;
                case EMAIL_VERIFIED:
                    $icon = 'fa-check-square-o';
                    $tip = $langMailVerificationYesU;
                    break;
                default:
                    $icon = 'fa-circle';
                    $tip = $langMailVerificationNoU;
                    break;
            }
            $email_icon .= ' ' . icon($icon, $tip);
        }


        switch ($logs->status) {
            case USER_TEACHER:
                $icon = 'fa-university';
                $tip = $langTeacher;
                break;
            case USER_STUDENT:
                $icon = 'fa-graduation-cap';
                $tip = $langStudent;
                break;
            case USER_GUEST:
                $icon = 'fa-male';
                $tip = $langVisitor;
                break;
            default:
                $icon = false;
                $tip = $langOther;
                break;
        }

        //$width = (!isDepartmentAdmin()) ? 100 : 80;
        if ($logs->id == 1) { // don't display actions for admin user
            $icon_content = "&mdash;&nbsp;";
        } else {
            /*$icon_content = action_button(array(
                        array('title' => $langEdit,
                              'url' => "edituser.php?u=$logs->id",
                              'icon' => 'fa-edit'),
                        array('title' => $langDelete,
                              'url' => "deluser.php?u=$logs->id",
                              'icon' => 'fa-times'),
                        array('title' => $langStat,
                              'url' => "userstats.php?u=$logs->id",
                              'icon' => 'fa-pie-chart'),
                        array('title' => $langActions,
                              'url' => "userlogs.php?u=$logs->id",
                              'icon' => 'fa-list-alt'),
                        array('title' => $changetip,
                              'url' => "change_user.php?username=" . urlencode($logs->username) . "",
                              'icon' => 'fa-key',
                              'show' => !isDepartmentAdmin()),
                        ));*/
            $changetip = q("$langChangeUserAs $logs->username");
            $icon_content = icon('fa-edit', $langEdit, "edituser.php?u=$logs->id") . '&nbsp;' .
                            icon('fa-times', $langDelete, "deluser.php?u=$logs->id") . '&nbsp;' .
                            icon('fa-pie-chart', $langStat, "userstats.php?u=$logs->id") . '&nbsp;' .
                            icon('fa-list-alt', $langActions, "userlogs.php?u=$logs->id");
            if (!isDepartmentAdmin()) {
                $icon_content .= '&nbsp;' . icon('fa-key', $changetip, 'change_user.php?username=' . urlencode($logs->username));
            }
        }
        $data['aaData'][] = array(
            '0' => $logs->surname,
            '1' => $logs->givenname,
            '2' => $logs->username,
            '3' => $email_icon,
            '4' => icon($icon, $tip),
            '5' => $icon_content
        );
    }
    echo json_encode($data);
    exit();
}

load_js('tools.js');
load_js('datatables');
load_js('datatables_filtering_delay');
$head_content .= "<script type='text/javascript'>
        $(document).ready(function() {
            $('#search_results_table').dataTable ({
                'bProcessing': true,
                'bServerSide': true,
                'sAjaxSource': '$_SERVER[REQUEST_URI]',
                'aLengthMenu': [
                   [10, 15, 20 , -1],
                   [10, 15, 20, '$langAllOfThem'] // change per page values here
                ],
                'sPaginationType': 'full_numbers',
                'bAutoWidth': false,
                'aoColumns': [
                    {'bSortable' : true, 'sWidth': '20%' },
                    {'bSortable' : true, 'sWidth': '20%' },
                    {'bSortable' : true, 'sWidth': '20%' },
                    {'bSortable' : false, 'sWidth': '20%' },
                    {'bSortable' : false, 'sClass': 'center' },
                    {'bSortable' : false, 'sWidth': '30%' },
                ],
                'oLanguage': {
                   'sLengthMenu':   '$langDisplay _MENU_ $langResults2',
                   'sZeroRecords':  '" . $langNoResult . "',
                   'sInfo':         '$langDisplayed _START_ $langTill _END_ $langFrom2 _TOTAL_ $langTotalResults',
                   'sInfoEmpty':    '$langDisplayed 0 $langTill 0 $langFrom2 0 $langResults2',
                   'sInfoFiltered': '',
                   'sInfoPostFix':  '',
                   'sSearch':       '" . $langSearch . "',
                   'sUrl':          '',
                   'oPaginate': {
                       'sFirst':    '&laquo;',
                       'sPrevious': '&lsaquo;',
                       'sNext':     '&rsaquo;',
                       'sLast':     '&raquo;'
                   }
               }
            }).fnSetFilteringDelay(1000);
            $('.dataTables_filter input').attr('placeholder', '$langName, $langSurname, $langUsername');
        });
        </script>";

$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
$navigation[] = array('url' => 'search_user.php', 'name' => $langSearchUser);
$toolName = $langListUsersActions;

// Display Actions Toolbar
$tool_content .= action_bar(array(
            array('title' => $langAllUsers,
                'url' => "$_SERVER[SCRIPT_NAME]",
                'icon' => 'fa-search',
                'level' => 'primary-label'),
            array('title' => $langInactiveUsers,
                'url' => "$_SERVER[SCRIPT_NAME]?search=inactive",
                'icon' => 'fa-search',
                'level' => 'primary-label',
                'show' => !(isset($_GET['search']) and $_GET['search'] == 'inactive')),
            array('title' => $langAddSixMonths,
                'url' => "updatetheinactive.php?activate=1",
                'icon' => 'fa-plus-circle',
                'level' => 'primary',
                'show' => (isset($_GET['search']) and $_GET['search'] == 'inactive')),            
            array('title' => $langBack,
                'url' => "search_user.php",
                'icon' => 'fa-reply',
                'level' => 'primary')
                ));

// display search results
$tool_content .= "<table id='search_results_table' class='display'>
            <thead>
            <tr>
              <th width='150'>$langSurname</th>
              <th width='100' class='left'>$langName</th>
              <th width='170' class='left'>$langUsername</th>
              <th>$langEmail</th>
              <th>$langProperty</th>
              <th width='130' class='centertext-center'>" . icon('fa-gears') . "</th>
            </tr></thead>";
$tool_content .= "<tbody></tbody></table>";

$tool_content .= "<div align='right' style='margin-top: 60px; margin-bottom:10px;'>";
// delete all function
$tool_content .= " <form action='multideluser.php' method='post' name='delall_user_search'>";
// redirect all request vars towards delete all action
foreach ($_REQUEST as $key => $value) {
    $tool_content .= "<input type='hidden' name='$key' value='$value' />";
}

$tool_content .= "<input class='btn btn-primary' type='submit' name='dellall_submit' value='$langDelList'></form></div>";

draw($tool_content, 3, null, $head_content);

/**
 * make links from one page to another during search results
 * @global string $params
 * @param type $name
 * @param type $value
 */
function add_param($name, $value = null) {
    global $params;
    if (!isset($value)) {
        $value = $GLOBALS[$name];
    }
    if ($value !== 0 and $value !== '') {
        $params[] = $name . '=' . urlencode($value);
    }
}
