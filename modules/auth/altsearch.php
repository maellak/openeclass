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
 * @file: altsearch.php
 * @authors list: Karatzidis Stratos <kstratos@uom.gr>
 *                 Vagelis Pitsioygas <vagpits@uom.gr>
 * @description: This script/file tries to authenticate the user, using
 * his user/pass pair and the authentication method defined by the admin
 */
require_once '../../include/baseTheme.php';
require_once 'include/sendMail.inc.php';
require_once 'include/CAS/CAS.php';
require_once 'auth.inc.php';
require_once 'include/lib/user.class.php';
require_once 'include/lib/hierarchy.class.php';

$tree = new Hierarchy();
$userObj = new User();

load_js('jstree');

$user_registration = get_config('user_registration');
$alt_auth_stud_reg = get_config('alt_auth_stud_reg'); //user registration via alternative auth methods
$alt_auth_prof_reg = get_config('alt_auth_prof_reg'); // prof registration via alternative auth methods

if (!$user_registration) {
    $tool_content .= "<div class='alert alert-info'>$langCannotRegister</div>";
    draw($tool_content, 0);
    exit;
}

if (isset($_POST['auth'])) {
    $auth = intval($_POST['auth']);
    $_SESSION['u_tmp'] = $auth;
} else {
    $auth = isset($_SESSION['u_tmp']) ? $_SESSION['u_tmp'] : 0;
}

if (isset($_SESSION['u_prof'])) {
    $prof = intval($_SESSION['u_prof']);
}
if (!$_SESSION['u_prof'] and !$alt_auth_stud_reg) {
    $tool_content .= "<div class='alert alert-danger'>$langForbidden</div>";
    draw($tool_content, 0);
    exit;
}

if ($_SESSION['u_prof'] and !$alt_auth_prof_reg) {
    $tool_content .= "<div class='alert alert-danger'>$langForbidden</div>";
    draw($tool_content, 0);
    exit;
}

$phone_required = $prof;

if (!$prof and $alt_auth_stud_reg == 2) {
    $autoregister = TRUE;
} else {
    $autoregister = FALSE;
}
$comment_required = !$autoregister;
$email_required = !$autoregister || get_config('email_required');
$am_required = !$prof && get_config('am_required');

$pageName = ($prof ? $langReqRegProf : $langUserData) . ' (' . (get_auth_info($auth)) . ')';
$email_message = $langEmailNotice;
$navigation[] = array('url' => 'registration.php', 'name' => $langNewUser);

register_posted_variables(array('uname' => true, 'passwd' => true,
    'is_submit' => true, 'submit' => true));
$lastpage = 'altnewuser.php?' . ($prof ? 'p=1&amp;' : '') .
        "auth=$auth&amp;uname=" . urlencode($uname);
$navigation[] = array('url' => $lastpage, 'name' => $langConfirmUser);

$errormessage = "<br/><p>$ldapback <a href='$lastpage'>$ldaplastpage</a></p>";
$init_auth = $is_valid = false;

if (!isset($_SESSION['was_validated']) or
        $_SESSION['was_validated']['auth'] != $auth or
        $_SESSION['was_validated']['uname'] != $uname) {
    $init_auth = true;
    // If user wasn't authenticated in the previous step, try
    // an authentication step now:
    // First check for Shibboleth
    if (isset($_SESSION['shib_auth']) and $_SESSION['shib_auth'] == true) {
        $r = Database::get()->querySingle("SELECT auth_settings FROM auth WHERE auth_id = 6");
        if ($r) {
            $shibsettings = $r->auth_settings;
            if ($shibsettings != 'shibboleth' and $shibsettings != '') {
                $shibseparator = $shibsettings;
            }
            if (strpos($_SESSION['shib_surname'], $shibseparator)) {
                $temp = explode($shibseparator, $_SESSION['shib_surname']);
                $auth_user_info['firstname'] = $temp[0];
                $auth_user_info['lastname'] = $temp[1];
            }
        }
        $auth_user_info['email'] = $_SESSION['shib_email'];
        $uname = $_SESSION['shib_uname'];
        $is_valid = true;
    } elseif ($is_submit or ($auth == 7 and !$submit)) {
        unset($_SESSION['was_validated']);
        if ($auth != 7 and $auth != 6 and
                ($uname === '' or $passwd === '')) {
            $tool_content .= "<div class='alert alert-danger'>$ldapempty $errormessage</div>";
            draw($tool_content, 0);
            exit();
        } else {
            // try to authenticate user
            $auth_method_settings = get_auth_settings($auth);
            if ($auth == 6) {
                redirect_to_home_page('secure/index_reg.php' . ($prof ? '?p=1' : ''));
            }
            $is_valid = auth_user_login($auth, $uname, $passwd, $auth_method_settings);
        }

        if ($auth == 7) {
            if (phpCAS::checkAuthentication()) {
                $uname = phpCAS::getUser();
                $cas = get_auth_settings($auth);
                // store CAS released attributes in $GLOBALS['auth_user_info']
                get_cas_attrs(phpCAS::getAttributes(), $cas);
                if (!empty($uname)) {
                    $is_valid = true;
                }
            }
        }
    }

    if ($is_valid) { // connection successful
        $_SESSION['was_validated'] = array('auth' => $auth,
            'uname' => $uname,
            'uname_exists' => user_exists($uname));
        if (isset($GLOBALS['auth_user_info'])) {
            $_SESSION['was_validated']['auth_user_info'] = $GLOBALS['auth_user_info'];
        }
    } else {
        $tool_content .= "<div class='alert alert-danger'>$langConnNo<br>$langAuthNoValidUser</div>" .
                "<p>&laquo; <a href='$lastpage'>$langBack</a></p>";
    }
} else {
    $is_valid = true;
    if (isset($_SESSION['was_validated']['auth_user_info'])) {
        $auth_user_info = $_SESSION['was_validated']['auth_user_info'];
    }
}

// -----------------------------------------
// registration
// -----------------------------------------
if ($is_valid) {
    $ext_info = !isset($auth_user_info);
    $ext_mail = !(isset($auth_user_info['email']) && $auth_user_info['email']);
    if (isset($_POST['p']) and $_POST['p'] == 1) {
        $ok = register_posted_variables(array('submit' => false, 'uname' => true,
            'email' => $email_required &&
            $ext_mail,
            'surname_form' => $ext_info,
            'givenname_form' => $ext_info,
            'am' => $am_required,
            'department' => true,
            'usercomment' => $comment_required,
            'userphone' => $phone_required), 'all');
    } else {
        $ok = register_posted_variables(array('submit' => false,
            'email' => $email_required &&
            $ext_mail,
            'surname_form' => $ext_info,
            'givenname_form' => $ext_info,
            'am' => $am_required,
            'department' => true,
            'userphone' => $phone_required), 'all');
    }

    if (!$ok and $submit) {
        $tool_content .= "<div class='alert alert-danger'>$langFieldsMissing</div>";
    }
    $depid = intval($department);
    if (isset($auth_user_info)) {
        $givenname_form = $auth_user_info['firstname'];
        $surname_form = $auth_user_info['lastname'];
        if (!$email and !empty($auth_user_info['email'])) {
            $email = $auth_user_info['email'];
        }
    }
    if (!empty($email) and !email_seems_valid($email)) {
        $ok = NULL;
        $tool_content .= "<div class='alert alert-danger'>$langEmailWrong</div>";
    } else {
        $email = mb_strtolower(trim($email));
    }

    $tool_content .= $init_auth ? ("<div class='alert alert-success'>$langTheUser $ldapfound.</div>") : '';
    if (@(!empty($_SESSION['was_validated']['uname_exists']) and $_POST['p'] != 1)) {
        $tool_content .= "<div class='alert alert-danger'>$langUserFree<br />
                                <br />$click <a href='$urlServer' class='mainpage'>$langHere</a> $langBackPage</div>";
        draw($tool_content, 0, null, $head_content);
        exit();
    }
    if (!$ok) {
        user_info_form();
        draw($tool_content, 0, null, $head_content);
        exit();
    }
    if ($auth != 1) {
        $password = isset($auth_ids[$auth]) ? $auth_ids[$auth] : '';
    }

    $status = $prof ? USER_TEACHER : USER_STUDENT;
    $greeting = $prof ? $langDearProf : $langDearUser;

    $uname = canonicalize_whitespace($uname);
    // user already exists
    if (user_exists($uname)) {
        $_SESSION['uname_exists'] = 1;
    } elseif (isset($_SESSION['uname_exists'])) {
        unset($_SESSION['uname_exists']);
    }
    // user allready applied for account
    if (user_app_exists($uname)) {
        $_SESSION['uname_app_exists'] = 1;
    } elseif (isset($_SESSION['uname_app_exists'])) {
        unset($_SESSION['uname_app_exists']);
    }

    // register user
    if ($autoregister and empty($_SESSION['uname_exists']) and empty($_SESSION['uname_app_exists'])) {
        if (get_config('email_verification_required') && !empty($email)) {
            $verified_mail = 0;
            $vmail = TRUE;
        } else {
            $verified_mail = 2;
            $vmail = FALSE;
        }
        
        $authmethods = array('2', '3', '4', '5');        
        $q1 = Database::get()->query("INSERT INTO user
                      SET surname = ?s,
                          givenname = ?s,
                          username = ?s,
                          password = ?s,
                          email = ?s,
                          status = " . USER_STUDENT . ",
                          am = ?s,
                          registered_at = " . DBHelper::timeAfter() . ",
                          expires_at = " . DBHelper::timeAfter(get_config('account_duration')) . ",
                          lang = ?s,
                          verified_mail = ?d,
                          whitelist='',
                          description = ''", $surname_form, $givenname_form, $uname, $password, $email, $am, $language, $verified_mail);
        $last_id = $q1->lastInsertID;
        $userObj->refresh($last_id, array(intval($depid)));

        if ($vmail and !empty($email)) {
            $hmac = token_generate($uname . $email . $last_id);
        }

        // Register a new user
        $password = $auth_ids[$auth];
        $telephone = get_config('phone');
        $administratorName = get_config('admin_name');
        $emailhelpdesk = get_config('email_helpdesk');
        $emailAdministrator = get_config('email_sender');
        $emailsubject = "$langYourReg $siteName";
        $emailbody = "$langDestination $givenname_form $surname_form\n" .
                "$langYouAreReg $siteName $langSettings $uname\n" .
                "$langPassSameAuth\n$langAddress $siteName: " .
                "$urlServer\n" .
                ($vmail ? "\n$langMailVerificationSuccess.\n$langMailVerificationClick\n$urlServer" . "modules/auth/mail_verify.php?ver=" . $hmac . "&id=" . $last_id . "\n" : "") .
                "$langProblem\n$langFormula" .
                "$administratorName\n" .
                "$langManager $siteName \n$langTel $telephone \n" .
                "$langEmail: $emailhelpdesk";

        if (!empty($email)) {
            send_mail($siteName, $emailAdministrator, '', $email, $emailsubject, $emailbody, $charset, "Reply-To: $emailhelpdesk");
        }
        
        $myrow = Database::get()->querySingle("SELECT id, surname, givenname FROM user WHERE id = ?d", $last_id);
        if ($myrow) {        
            $uid = $myrow->id;
            $surname = $myrow->surname;
            $givenname = $myrow->givenname;
        }

        if (!$vmail) {
            Database::get()->query("INSERT INTO loginout SET id_user = $uid, ip = '$_SERVER[REMOTE_ADDR]',`when` = NOW(), action = 'LOGIN'");
            $_SESSION['uid'] = $uid;
            $_SESSION['status'] = USER_STUDENT;
            $_SESSION['givenname'] = $givenname;
            $_SESSION['surname'] = $surname;
            $_SESSION['uname'] = canonicalize_whitespace($username);            

            $tool_content .= "<div class='alert alert-success'><p>$greeting,</p><p>";
            $tool_content .=!empty($email) ? $langPersonalSettings : $langPersonalSettingsLess;
            $tool_content .= "</p></div>
                                                <br /><br />
                                                <p>$langPersonalSettingsMore</p>";
        } else {
            $tool_content .= "<div class='alert alert-success'>" .
                    ($prof ? $langDearProf : $langDearUser) .
                    "!<br />$langMailVerificationSuccess: <strong>$email</strong></div>
                                                <p>$langMailVerificationSuccess4.<br /><br />$click <a href='$urlServer' class='mainpage'>$langHere</a> $langBackPage</p>";
        }
    } elseif (empty($_SESSION['uname_app_exists'])) {
        $email_verification_required = get_config('email_verification_required');
        if (!$email_verification_required) {
            $verified_mail = 2;
        } else {
            $verified_mail = 0;
        }

        // check if mail address is valid
        if (!empty($email) and !email_seems_valid($email)) {
            $tool_content .= "<div class='alert alert-danger'>$langEmailWrong</div>";
            user_info_form();
            draw($tool_content, 0, null, $head_content);
            exit();
        } else {
            $email = mb_strtolower(trim($email));
        }

        // Record user request        
        $q1 = Database::get()->query("INSERT INTO user_request SET
                                        givenname = ?s, surname = ?s, username = ?s, password = '$password',
                                        email = ?s, faculty_id = ?d, phone = ?s,
                                        am = ?s, state = 1, status = ?d, verified_mail = ?d,
                                        date_open = " . DBHelper::timeAfter() . ", comment = ?s, lang = ?s,
                                        request_ip = ?s", 
                            $givenname_form, $surname_form, $uname, $email, $depid, $userphone, 
                            $am, $status, $verified_mail, $usercomment, $language, $_SERVER['REMOTE_ADDR']);
        $request_id = $q1->lastInsertID;
        // email does not need verification -> mail helpdesk
        if (!$email_verification_required) {
            $emailAdministrator = get_config('email_sender');
            // send email
            $MailMessage = $mailbody1 . $mailbody2 . "$givenname_form $surname_form\n\n" . $mailbody3
                    . $mailbody4 . $mailbody5 . "$mailbody6\n\n" . "$langFaculty: " . $tree->getFullPath($depid) . "
        \n$langComments: $usercomment\n"
                    . "$langProfUname : $uname\n$langProfEmail : $email\n" . "$contactphone : $userphone\n\n\n$logo\n\n";

            if (!send_mail($siteName, $emailAdministrator, $gunet, $emailhelpdesk, $mailsubject, $MailMessage, $charset, "Reply-To: $email")) {
                $tool_content .= "<div class='alert alert-warning'>$langMailErrorMessage &nbsp; <a href='mailto:$emailhelpdesk'>$emailhelpdesk</a></div>";
                draw($tool_content, 0);
                exit();
            }

            $tool_content .= "<div class='alert alert-success'>$greeting,<br />$success<br /></div><p>$infoprof</p><br />
                          <p>&laquo; <a href='$urlServer'>$langBack</a></p>";
        } else {
            // email needs verification -> mail user
            $hmac = token_generate($uname . $email . $request_id);
            $emailhelpdesk = get_config('email_helpdesk');
            $emailAdministrator = get_config('email_sender');
            $subject = $langMailVerificationSubject;
            $MailMessage = sprintf($mailbody1 . $langMailVerificationBody1, $urlServer . 'modules/auth/mail_verify.php?ver=' . $hmac . '&rid=' . $request_id);
            if (!send_mail($siteName, $emailAdministrator, '', $email, $subject, $MailMessage, $charset, "Reply-To: $emailhelpdesk")) {
                $mail_ver_error = sprintf("<div class='alert alert-warning'>" . $langMailVerificationError, $email, $urlServer . "modules/auth/registration.php", "<a href='mailto:$emailhelpdesk' class='mainpage'>$emailhelpdesk</a>.</div>");
                $tool_content .= $mail_ver_error;
                draw($tool_content, 0);
                exit();
            }
            // User Message
            $tool_content .= "<div class='alert alert-success'>" .
                    ($prof ? $langDearProf : $langDearUser) .
                    "!<br />$langMailVerificationSuccess: <strong>$email</strong></div>
                                        <p>$langMailVerificationSuccess4.<br /><br />$click <a href='$urlServer'
                                        class='mainpage'>$langHere</a> $langBackPage</p>";
        }
    } elseif (!empty($_SESSION['uname_app_exists'])) {
        $tool_content .= "<div class='alert alert-danger'>$langUserFree3<br><br>$click <a href='$urlServer' class='mainpage'>$langHere</a> $langBackPage</div>";
    }
}
draw($tool_content, 0);

/**
 * set variables
 * @param type $name
 * @return string
 */
function set($name) {
    if (isset($GLOBALS[$name]) and
            $GLOBALS[$name] !== '') {
        return " value='" . q($GLOBALS[$name]) . "'";
    } else {
        return '';
    }
}

/**
 * @brief display form
 * 
 * @global type $tool_content  
 * @global type $langName
 * @global type $langSurname
 * @global type $langEmail
 * @global type $langCompulsory
 * @global type $langOptional
 * @global type $langPhone
 * @global type $langComments
 * @global type $langFaculty
 * @global type $langRegistration
 * @global type $langLanguage  
 * @global type $langAm
 * @global type $profreason
 * @global type $auth_user_info
 * @global type $auth
 * @global type $prof
 * @global string $usercomment
 * @global int $depid
 * @global type $email_required
 * @global type $phone_required 
 * @global type $comment_required
 * @global type $langEmailNotice
 * @global Hierarchy $tree
 * @global type $head_content
 */
function user_info_form() {
    global $tool_content, $langName, $langSurname, $langEmail, $langCompulsory, $langOptional,
    $langPhone, $langComments, $langFaculty, $langRegistration, $langLanguage,
    $langAm, $profreason,
    $auth_user_info, $auth, $prof, $usercomment, $depid, $email_required,
    $phone_required, $comment_required, $langEmailNotice, $tree, $head_content;

    if (!isset($usercomment)) {
        $usercomment = '';
    }
    if (!isset($depid)) {
        $depid = 0;
    }
    if (!get_config("email_required")) {
        $mail_message = $langEmailNotice;
    } else {
        $mail_message = "";
    }
    
    $tool_content .= "<div class='form-wrapper'>
        <form role='form' class='form-horizontal' action='$_SERVER[SCRIPT_NAME]' method='post'>
        <fieldset>                
        <div class='form-group'>
            <label for='Name' class='col-sm-2 control-label'>$langName:</label>
            <div class='col-sm-10'> ". (isset($auth_user_info) ?
                    $auth_user_info['firstname'] :
              '<input type="text" name="givenname_form" size="30" maxlength="30"' . set('givenname_form') . '> ') . "
            </div>
        </div>
        <div class='form-group'>
                <label for='SurName' class='col-sm-2 control-label'>$langSurname:</label>
                <div class='col-sm-10'> ". (isset($auth_user_info) ?
                    $auth_user_info['lastname'] :
                '<input type="text" name="surname_form" size="30" maxlength="30"' . set('surname_form') . '> ') . "
                </div>
        </div>          
        <div class='form-group'>
            <label for='UserEmail' class='col-sm-2 control-label'>$langEmail:</label>
            <div class='col-sm-10'>
                <input type='text' name='email' size='30' maxlength='30'" . set('email') . "'>
                 " .($email_required ? "&nbsp;" : "<span class='help-block'><small>$mail_message</small></span>") . "
            </div>
        </div>";
    if (!$prof) {        
        if (get_config('am_required')) {
            $am_message = $langCompulsory;
        } else {
            $am_message = $langOptional;
        }
        $tool_content .= "<div class='form-group'>
                <label for='UserAm' class='col-sm-2 control-label'>$langAm:</label>
                <div class='col-sm-10'>
                    <input type='text' name='am' size='20' maxlength='20'" . set('am') . "' placeholder='$am_message'>
                </div>
            </div>";        
    }
    if (isset($phone_required)) {
        $phone_message = $langCompulsory;
    } else {
        $phone_message = $langOptional;
    }
    $tool_content .= "<div class='form-group'>
                <label for='UserPhone' class='col-sm-2 control-label'>$langPhone:</label>
                <div class='col-sm-10'>
                    <input type='text' name='userphone' size='20' maxlength='20'" . set('userphone') . "' placeholder = '$phone_message'>
                </div>
            </div>";                
    if ($comment_required) {
        $tool_content .= "<div class='form-group'>
          <label for='UserComment' class='col-sm-2 control-label'>$langComments:</label>
            <div class='col-sm-10'>
             <textarea name='usercomment' cols='32' rows='4'>" . q($usercomment) . "</textarea>&nbsp;&nbsp;(*) $profreason</div>
          </div>";
    }    
    $tool_content .= "<div class='form-group'>
              <label for='UserFac' class='col-sm-2 control-label'>$langFaculty:</label>
                <div class='col-sm-10'>";
    list($js, $html) = $tree->buildNodePicker(array('params' => 'name="department"', 'defaults' => $depid, 'tree' => null, 'useKey' => "id", 'where' => "AND node.allow_user = true", 'multiple' => false));
    $head_content .= $js;
    $tool_content .= $html;
    $tool_content .= "</div>
    </div>";                
    $tool_content .= "<div class='form-group'>
              <label for='UserLang' class='col-sm-2 control-label'>$langLanguage:</label>
              <div class='col-sm-10'>";
            $tool_content .= lang_select_options('localize', "class='form-control'");
            $tool_content .= "</div>
            </div>";
    $tool_content .= "<div class='col-sm-offset-2 col-sm-10'>
                        <input class='btn btn-primary' type='submit' name='submit' value='" . q($langRegistration) . "'>
                    </div>
        <input type='hidden' name='p' value='$prof'>";
                        
    if (isset($_SESSION['shib_uname'])) {
        $tool_content .= "<input type='hidden' name='uname' value='" . q($_SESSION['shib_uname']) . "'>";
    } else {
        $tool_content .= "<input type='hidden' name='uname' value='" . q($_SESSION['was_validated']['uname']) . "'>";
    }
    $tool_content .= "<input type='hidden' name='auth' value='$auth'>";
    $tool_content .= "</fieldset>
    </form>
    </div>";            
}
