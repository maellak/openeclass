<?php

/*
 * ========================================================================
 * Open eClass 3.0 - E-learning and Course Management System
 * ========================================================================
  Copyright(c) 2003-2013  Greek Universities Network - GUnet
  A full copyright notice can be read in "/info/copyright.txt".

  Authors:     Costas Tsibanis <k.tsibanis@noc.uoa.gr>
  Yannis Exidaridis <jexi@noc.uoa.gr>
  Alexandros Diamantidis <adia@noc.uoa.gr>

  For a full list of contributors, see "credits.txt".
 */

/**
 * @file main.lib.php
 * @brief General useful functions for eClass
 * @authors many...
 * Standard header included by all eClass files
 * Defines standard functions and validates variables
 */
define('ECLASS_VERSION', '3.0');

// better performance while downloading very large files
define('PCLZIP_TEMPORARY_FILE_RATIO', 0.2);

/* course status */
define('COURSE_OPEN', 2);
define('COURSE_REGISTRATION', 1);
define('COURSE_CLOSED', 0);
define('COURSE_INACTIVE', 3);

/* user status */
define('USER_TEACHER', 1);
define('USER_STUDENT', 5);
define('USER_GUEST', 10);

// resized user image
define('IMAGESIZE_LARGE', 256);
define('IMAGESIZE_MEDIUM', 155);
define('IMAGESIZE_SMALL', 32);

// profile info access
define('ACCESS_PRIVATE', 0);
define('ACCESS_PROFS', 1);
define('ACCESS_USERS', 2);

// user admin rights
define('ADMIN_USER', 0); // admin user can do everything
define('POWER_USER', 1); // poweruser can admin only users and courses
define('USERMANAGE_USER', 2); // usermanage user can admin only users
define('DEPARTMENTMANAGE_USER', 3); // departmentmanage user can admin departments
// user email status
define('EMAIL_VERIFICATION_REQUIRED', 0);  /* email verification required. User cannot login */
define('EMAIL_VERIFIED', 1); // email is verified. User can login.
define('EMAIL_UNVERIFIED', 2); // email is unverified. User can login but cannot receive mail.
// course modules
define('MODULE_ID_AGENDA', 1);
define('MODULE_ID_LINKS', 2);
define('MODULE_ID_DOCS', 3);
define('MODULE_ID_VIDEO', 4);
define('MODULE_ID_ASSIGN', 5);
define('MODULE_ID_ANNOUNCE', 7);
define('MODULE_ID_USERS', 8);
define('MODULE_ID_FORUM', 9);
define('MODULE_ID_EXERCISE', 10);
define('MODULE_ID_COURSEINFO', 14);
define('MODULE_ID_GROUPS', 15);
define('MODULE_ID_DROPBOX', 16);
define('MODULE_ID_GLOSSARY', 17);
define('MODULE_ID_EBOOK', 18);
define('MODULE_ID_CHAT', 19);
define('MODULE_ID_DESCRIPTION', 20);
define('MODULE_ID_QUESTIONNAIRE', 21);
define('MODULE_ID_LP', 23);
define('MODULE_ID_USAGE', 24);
define('MODULE_ID_TOOLADMIN', 25);
define('MODULE_ID_WIKI', 26);
define('MODULE_ID_UNITS', 27);
define('MODULE_ID_SEARCH', 28);
define('MODULE_ID_CONTACT', 29);
define('MODULE_ID_GRADEBOOK', 32);
define('MODULE_ID_GRADEBOOKTOTAL', 33);
define('MODULE_ID_ATTENDANCE', 30);
define('MODULE_ID_BLOG', 37);
define('MODULE_ID_COMMENTS', 38);
define('MODULE_ID_RATING', 39);
define('MODULE_ID_BBB', 34);
define('MODULE_ID_WEEKS', 41);
define('MODULE_ID_SHARING', 40);

// user modules
define('MODULE_ID_SETTINGS', 31);
define('MODULE_ID_NOTES', 35);
define('MODULE_ID_PERSONALCALENDAR',36);
define('MODULE_ID_ADMINCALENDAR',37);

// exercise answer types
define('UNIQUE_ANSWER', 1);
define('MULTIPLE_ANSWER', 2);
define('FILL_IN_BLANKS', 3);
define('MATCHING', 4);
define('TRUE_FALSE', 5);
define('FREE_TEXT', 6);

// exercise attempt types
define('ATTEMPT_COMPLETED', 1);
define('ATTEMPT_PENDING', 2);
define('ATTEMPT_PAUSED', 3);
define('ATTEMPT_CANCELED', 4);

// for fill in blanks questions
define('TEXTFIELD_FILL', 1);
define('LISTBOX_FILL', 2); //
// Subsystem types (used in documents)
define('MAIN', 0);
define('GROUP', 1);
define('EBOOK', 2);
define('COMMON', 3);

// interval in minutes for counting online users
define('MAX_IDLE_TIME', 10);

define('JQUERY_VERSION', '2.1.1');

require_once 'lib/session.class.php';

// Check if a string looks like a valid email address
function email_seems_valid($email) {
    return (preg_match('#^[0-9a-z_\.\+-]+@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2,}$#i', $email) and !preg_match('#@.*--#', $email));
}

// ----------------------------------------------------------------------
// for safety reasons use the functions below
// ---------------------------------------------------------------------

// Shortcut for htmlspecialchars()
function q($s) {
    return htmlspecialchars($s, ENT_QUOTES);
}

function unescapeSimple($str) {
    if (phpversion() < '5.4' and get_magic_quotes_gpc()) {
        return stripslashes($str);
    } else {
        return $str;
    }
}

// Escape string to use as JavaScript argument
function js_escape($s) {
    return q(str_replace("'", "\\'", $s));
}

// Include a JavaScript file from the main js directory
function load_js($file, $init='') {
    global $head_content, $urlAppend, $theme, $theme_settings, $language;
    static $loaded;

    if (isset($loaded[$file])) {
        return;
    } else {
        $loaded[$file] = true;
    }

    // Load file only if not provided by template
    if (!(isset($theme_settings['js_loaded']) and
          in_array($file, $theme_settings['js_loaded']))) {
        if ($file == 'jstree') {
            $head_content .= "<script type='text/javascript' src='{$urlAppend}js/jstree/jquery.cookie.min.js'></script>\n";
            $file = 'jstree/jquery.jstree.min.js';
        } elseif ($file == 'jstree3') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/jstree3/themes/proton/style.min.css'>";
            $file = 'jstree3/jstree.min.js';
        } elseif ($file == 'jquery-ui') {
            if ($theme == 'modern' || $theme == 'ocean') {
                $uiTheme = 'redmond';
            } else {
                $uiTheme = 'lightness';
            }
           
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/jquery-ui-css/{$uiTheme}/jquery-ui.1.11.1.min.css'>\n";
            $file = 'jquery-ui-1.11.1.custom.min.js';
           
        } elseif ($file == 'shadowbox') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/shadowbox/shadowbox.css'>";
            $file = 'shadowbox/shadowbox.js';
        } elseif ($file == 'fancybox2') {
            $head_content .= "<link rel='stylesheet' href='{$urlAppend}js/fancybox2/jquery.fancybox.css?v=2.0.3' type='text/css' media='screen'>";
            $file = 'fancybox2/jquery.fancybox.pack.js?v=2.0.3';
        } elseif ($file == 'colorbox') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/colorbox/colorbox.css'>\n";
            $file = 'colorbox/jquery.colorbox.min.js';
        } elseif ($file == 'flot') {
            $head_content .= "\n<link href=\"{$urlAppend}js/flot/flot.css\" rel=\"stylesheet\" type=\"text/css\">\n";
            $head_content .= "<!--[if lte IE 8]><script language=\"javascript\" type=\"text/javascript\" src=\"{$urlAppend}js/flot/excanvas.min.js\"></script><![endif]-->\n";
            $head_content .= "<script type='text/javascript' src='{$urlAppend}js/jquery-migrate-1.2.1.min.js'></script>\n";
            $head_content .= "<script type='text/javascript' src='{$urlAppend}js/flot/jquery.flot.min.js'></script>\n";
            $file = 'flot/jquery.flot.categories.min.js';
        } elseif ($file == 'slick') {
                $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/slick-master/slick/slick.css'>";
                $file = 'slick-master/slick/slick.min.js';
        } elseif ($file == 'datatables') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/datatables/media/css/jquery.dataTables.css' />";            
            $file = 'datatables/media/js/jquery.dataTables.min.js';     
        } elseif ($file == 'datatables_bootstrap') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/datatables/media/css/dataTables.bootstrap.css' />";            
            $file = 'datatables/media/js/dataTables.bootstrap.js';                
        } elseif ($file == 'datatables_filtering_delay') {
                $file = 'datatables/media/js/jquery.dataTables_delay.js';
        } elseif ($file == 'tagsinput') {
            $file = 'taginput/jquery.tagsinput.min.js';
        } elseif ($file == 'RateIt') {
            $file = 'jquery.rateit.min.js';
        } elseif ($file == 'select2') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/select2-3.5.1/select2.css'>";
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/select2-3.5.1/select2-bootstrap.css'>";
            $file = 'select2-3.5.1/select2.min.js';
        } elseif ($file == 'bootstrap-datetimepicker') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css'>";
            $head_content .= "<script type='text/javascript' src='{$urlAppend}js/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js'></script>\n";
            
            $file = "bootstrap-datetimepicker/js/locales/bootstrap-datetimepicker.$language.js";
        } elseif ($file == 'bootstrap-timepicker') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/bootstrap-timepicker/css/bootstrap-timepicker.min.css'>";           
            $file = "bootstrap-timepicker/js/bootstrap-timepicker.min.js";
        } elseif ($file == 'bootstrap-datepicker') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/bootstrap-datepicker/css/datepicker3.css'>";
            $head_content .= "<script type='text/javascript' src='{$urlAppend}js/bootstrap-datepicker/js/bootstrap-datepicker.js'></script>\n";
            $file = "bootstrap-datepicker/js/locales/bootstrap-datepicker.$language.js";
        } elseif ($file == 'bootstrap-slider') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/bootstrap-slider/css/bootstrap-slider.css'>\n";
            $file = "bootstrap-slider/js/bootstrap-slider.js";
        } elseif ($file == 'bootstrap-colorpicker') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css'>\n";
            $file = "bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js";
        }               
        $head_content .= "<script type='text/javascript' src='{$urlAppend}js/$file'></script>\n";
    }

    if (strlen($init) > 0) {
        $head_content .= $init;
    }
}

// Return HTML for a user - first parameter is either a user id (so that the
// user's info is fetched from the DB) or a hash with user_id, surname, givenname,
// email, or an array of user ids or user info arrays
function display_user($user, $print_email = false, $icon = true, $class = "") {
    global $langAnonymous, $urlAppend;

    if (count($user) == 0) {
        return '-';    
    } elseif (is_array($user)) {
        $begin = true;
        $html = '';
        foreach ($user as $user_data) {            
            if (!isset($user->user_id)) {
                if ($begin) {
                    $begin = false;
                } else {
                    $html .= '<br>';
                }
                $html .= display_user($user_data->user_id, $print_email);
            }
        }
        return $html;
    } elseif (!is_array($user)) {        
        $r = Database::get()->querySingle("SELECT id, surname, givenname, email, has_icon FROM user WHERE id = ?d", $user);
        if ($r) {
            $user = $r;
        } else {
            if ($icon) {
                return profile_image(0, IMAGESIZE_SMALL) . '&nbsp;' . $langAnonymous;
            } else {
                return $langAnonymous;
            }
        }
    }

    if ($print_email) {
        $email = trim($user->email);
        $print_email = $print_email && !empty($email);
    }
    if ($icon) {
        $icon = profile_image($user->id, IMAGESIZE_SMALL, true) . '&nbsp;';
    }
    
    if (!empty($class)) {
        $class_str = "class='$class'";
    } else {
        $class_str = "";
    }

    $token = token_generate($user->id, true);
    return "$icon<a $class_str href='{$urlAppend}main/profile/display_profile.php?id=$user->id&amp;token=$token'>" .
            q($user->givenname) . " " .  q($user->surname) . "</a>" .
            ($print_email ? (' (' . mailto(trim($user->email), 'e-mail address hidden') . ')') : '');
}

// Translate uid to givenname , surname, fullname or nickname
function uid_to_name($uid, $name_type = 'fullname') {
    if ($name_type == 'fullname') {
        return Database::get()->querySingle("SELECT CONCAT(surname, ' ', givenname) AS fullname FROM user WHERE id = ?d", $uid)->fullname;
    } elseif ($name_type == 'givenname') {
        return Database::get()->querySingle("SELECT givenname FROM user WHERE id = ?d", $uid)->givenname;
    } elseif ($name_type == 'surname') {
        return Database::get()->querySingle("SELECT surname FROM user WHERE id = ?d", $uid)->surname;
    } elseif ($name_type == 'username') {
        return Database::get()->querySingle("SELECT username FROM user WHERE id = ?d", $uid)->username;
    } else {
        return false;
    }
}


/**
 * @brief Translate uid to user email
 * @param type $uid
 * @return boolean
 */
function uid_to_email($uid) {
    
    $r = Database::get()->querySingle("SELECT email FROM user WHERE id = ?d", $uid);    
    if ($r) {
        return $r->email;
    } else {
        return false;
    }
}


/**
 * @brief Translate uid to AM (student number)
 * @param type $uid
 * @return boolean
 */
function uid_to_am($uid) {
    
    $r = Database::get()->querySingle("SELECT am from user WHERE id = ?d", $uid);
    if ($r) {
        return $r->am;
    } else {
        return false;
    }
}

/**
 * @brief Return the URL for a user profile image
 * @param int $uid user id
 * @param int $size optional image size in pixels (IMAGESIZE_SMALL or IMAGESIZE_LARGE)
 * @return string
 */
function user_icon($uid, $size = null) {
    global $themeimg, $urlAppend;

    if (DBHelper::fieldExists("user", "id")) {
        $user = Database::get()->querySingle("SELECT has_icon FROM user WHERE id = ?d", $uid);
        if ($user) {
            if (!$size) {
                $size = IMAGESIZE_SMALL;
            }
            if ($user->has_icon) {
                return "${urlAppend}courses/userimg/${uid}_$size.jpg";
            } else {
                return "$themeimg/default_$size.jpg";
            }
        }
    }
    return '';
}

/**
 * @brief Display links to the groups a user is member of
 * @global type $urlAppend
 * @param type $course_id
 * @param type $user_id
 * @param type $format
 * @return string
 */
function user_groups($course_id, $user_id, $format = 'html') {
    global $urlAppend;

    $groups = '';
    $q = Database::get()->queryArray("SELECT `group`.id, `group`.name FROM `group`, group_members
                       WHERE `group`.course_id = ?d AND
                             `group`.id = group_members.group_id AND
                             `group_members`.user_id = ?d
                       ORDER BY `group`.name", $course_id, $user_id);
    
    if (!$q) {
        if ($format == 'html') {
            return "<div style='padding-left: 15px'>-</div>";
        } else {
            return '-';
        }
    }
    foreach ($q as $r) {
        if ($format == 'html') {
            $groups .= ((count($q) > 1) ? '<li>' : '') .
                    "<a href='{$urlAppend}modules/group/group_space.php?group_id=$r->id' title='" .
                    q($r->name) . "'>" .
                    q(ellipsize($r->name, 20)) . "</a>" .
                    ((count($q) > 1) ? '</li>' : '');
        } else {
            $groups .= (empty($groups) ? '' : ', ') . $r->name;
        }
    }
    if ($format == 'html') {
        if (count($q) > 1) {
            return "<ol>$groups</ol>";
        } else {
            return "<div style='padding-left: 15px'>$groups</div>";
        }
    } else {
        return $groups;
    }
}

/**
 * @brief Find secret subdir of group gid
 * @param type $gid
 * @return string
 */
function group_secret($gid) {    

    $r = Database::get()->querySingle("SELECT secret_directory FROM `group` WHERE id = ?d", $gid);    
    if ($r) {
        return $r->secret;     
    } else {
        return '';        
    }
}

/**
 * displays a selection box
 * @param type $entries an array of (value => label)
 * @param type $name the name of the selection element
 * @param type $default if it matches one of the values, specifies the default entry
 * @param type $extra
 * @return string
 */
function selection($entries, $name, $default = '', $extra = '') {
    $retString = "";
    $retString .= "\n<select name='$name' $extra>\n";
    foreach ($entries as $value => $label) {
        if (isset($default) && ($value == $default)) {
            $retString .= "<option selected value='" . htmlspecialchars($value) . "'>" .
                    htmlspecialchars($label) . "</option>\n";
        } else {
            $retString .= "<option value='" . htmlspecialchars($value) . "'>" .
                    htmlspecialchars($label) . "</option>\n";
        }
    }
    $retString .= "</select>\n";
    return $retString;
}

/**
 * displays a multi-selection box.
 * @param type $entries an array of (value => label)
 * @param type $name the name of the selection element
 * @param type $defaults array() if it matches one of the values, specifies the default entry
 * @param type $extra
 * @return string
 */
function multiselection($entries, $name, $defaults = array(), $extra = '') {
    $retString = "";
    $retString .= "\n<select name='$name' $extra>\n";
    foreach ($entries as $value => $label) {
        if (is_array($defaults) && (in_array($value, $defaults))) {
            $retString .= "<option selected value='" . htmlspecialchars($value) . "'>" .
                    htmlspecialchars($label) . "</option>\n";
        } else {
            $retString .= "<option value='" . htmlspecialchars($value) . "'>" .
                    htmlspecialchars($label) . "</option>\n";
        }
    }
    $retString .= "</select>\n";
    return $retString;
}

/* * ******************************************************************
  Show a selection box. Taken from main.lib.php
  Difference: the return value and not just echo the select box

  $entries: an array of (value => label)
  $name: the name of the selection element
  $default: if it matches one of the values, specifies the default entry
 * ********************************************************************* */

function selection3($entries, $name, $default = '') {
    $select_box = "<select name='$name'>\n";
    foreach ($entries as $value => $label) {
        if ($value == $default) {
            $select_box .= "<option selected value='" . htmlspecialchars($value) . "'>" .
                    htmlspecialchars($label) . "</option>\n";
        } else {
            $select_box .= "<option value='" . htmlspecialchars($value) . "'>" .
                    htmlspecialchars($label) . "</option>\n";
        }
    }
    $select_box .= "</select>\n";

    return $select_box;
}


/**
 * @brief function to check if user is a guest user
 * @global type $uid
 * @return boolean
 */
function check_guest($id = FALSE) {
    //global $uid;

    if ($id) {
        $uid = $id;
    } else {
        $uid = $GLOBALS['uid'];
    }
    if (isset($uid) and $uid) {
        if (DBHelper::fieldExists("user", "status")) {
            $status = Database::get()->querySingle("SELECT status FROM user WHERE id = ?d", $uid);
            if ($status && $status->status == USER_GUEST) {
                return TRUE;
            }
        }
    }
    return false;
}

/**
 * @brief function to check if user is a course editor
 * @global type $uid
 * @global type $course_id
 * @return boolean
 */
function check_editor($id = NULL) {
    global $uid, $course_id;
    if(isset($id)) {
        $uid = $id;
    }
    if (isset($uid) and $uid) {
        $s = Database::get()->querySingle("SELECT editor FROM course_user
                                        WHERE user_id = ?d AND
                                        course_id = ?d", $uid, $course_id);
        if ($s and $s->editor == 1) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * function to check if user is a course opencourses reviewer
 */
function check_opencourses_reviewer() {
    global $uid, $course_id, $is_power_user;

    if (isset($uid) and $uid) {
        if ($is_power_user) {
            return TRUE;
        }
        $r = Database::get()->querySingle("SELECT reviewer FROM course_user
                                    WHERE user_id = ?d
                                    AND course_id = ?d", $uid, $course_id);
        if ($r) {
            if ($r->reviewer == 1) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
}

/**
 * @brief just make sure that the $uid variable isn't faked
 * @global type $urlServer
 * @global type $require_valid_uid
 * @global type $uid
 */
function check_uid() {

    global $urlServer, $require_valid_uid, $uid;

    if (isset($_SESSION['uid'])) {
        $uid = $_SESSION['uid'];
    } else {
        unset($uid);
    }

    if ($require_valid_uid and !isset($uid)) {
        header("Location: $urlServer");
        exit;
    }
}


/**
 * @brief Check if a user with username $login already exists
 * @param type $login
 * @return boolean
 */
function user_exists($login) {
       
    if (get_config('case_insensitive_usernames')) {
        $qry = "COLLATE utf8_general_ci = ?s";
    } else {
        $qry = "= ?s";
    }
    $username_check = Database::get()->querySingle("SELECT id FROM user WHERE username $qry", $login);
    if ($username_check) {
        return true;
    } else {
        return false;
    }    
}


/**
 * @brief Check if a user with username $login already applied for account
 * @param type $login
 * @return boolean
 */
function user_app_exists($login) {
    
    if (get_config('case_insensitive_usernames')) {
        $qry = "COLLATE utf8_general_ci = ?s";
    } else {
        $qry = "= ?s";
    }
    $username_check = Database::get()->querySingle("SELECT id FROM user_request WHERE state = 1 AND username $qry", $login);
    if ($username_check) {
        return true;
    } else {
        return false;
    }
}

/**
 * @brief Convert HTML to plain text
 * @param type $string
 * @return type
 */
function html2text($string) {
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);

    $text = preg_replace('/</', ' <', $string);
    $text = preg_replace('/>/', '> ', $string);
    $desc = html_entity_decode(strip_tags($text));
    $desc = preg_replace('/[\n\r\t]/', ' ', $desc);
    $desc = preg_replace('/  /', ' ', $desc);

    return $desc;
    //    return strtr (strip_tags($string), $trans_tbl);
}

/*
  // IMAP authentication functions                                        |
 */

function imap_auth($server, $username, $password) {
    $auth = false;
    $fp = fsockopen($server, 143, $errno, $errstr, 10);
    if ($fp) {
        fputs($fp, "A1 LOGIN " . imap_literal($username) .
                " " . imap_literal($password) . "\r\n");
        fputs($fp, "A2 LOGOUT\r\n");
        while (!feof($fp)) {
            $line = fgets($fp, 200);
            if (substr($line, 0, 5) == 'A1 OK') {
                $auth = true;
            }
        }
        fclose($fp);
    }
    return $auth;
}

function imap_literal($s) {
    return "{" . strlen($s) . "}\r\n$s";
}


/**
 * @brief returns the name of a faculty given its code or its name
 * @param type $id
 * @return boolean
 */
function find_faculty_by_id($id) {
    
    $req = Database::get()->querySingle("SELECT name FROM hierarchy WHERE id = ?d", $id);
    if ($req) {        
        $fac = $req->name;
        return $fac;
    } else {        
        $req = Database::get()->querySingle("SELECT name FROM hierarchy WHERE name = ?s" , $id);        
        if ($req) {
            $fac = $req->name;
            return $fac;
        }
    }
    return false;
}


/**
 * @brief Returns next available code for a new course in faculty with id $fac
 * @param type $fac
 * @return string
 */
function new_code($fac) {
        
    $gencode = Database::get()->querySingle("SELECT code, generator FROM hierarchy WHERE id = ?d", $fac);
    if ($gencode) {
        do {
            $code = $gencode->code . $gencode->generator;
            $gencode->generator += 1;            
            Database::get()->query("UPDATE hierarchy SET generator = ?d WHERE id = ?d", $gencode->generator, $fac);    
        } while (file_exists("courses/" . $code));    
    // Make sure the code returned isn't empty!
    } else {
        die("Course Code is empty!");
    }
    return $code;
}

// due to a bug (?) to php function basename() our implementation
// handles correct multibyte characters (e.g. greek)
function my_basename($path) {
    return preg_replace('#^.*/#', '', $path);
}

/* transform the date format from "year-month-day" to "day-month-year"
 * if argument time is defined then
 * transform date time format from "year-month-day time" to "to "day-month-year time"
 */

function greek_format($date, $time = FALSE, $dont_display_time = FALSE) {
    if ($time) {
        $datetime = explode(' ', $date);
        $new_date = implode('-', array_reverse(explode('-', $datetime[0])));
        if ($dont_display_time) {
            return $new_date;
        } else {
            return $new_date . " " . $datetime[1];
        }
    } else {
        return implode('-', array_reverse(explode('-', $date)));
    }
}

/**
 * @brief format the date according to language
 * @param type $date
 * @param type $time
 * @param type $dont_display_time
 * @return type
 */
function nice_format($date, $time = FALSE, $dont_display_time = FALSE) {
    if ($GLOBALS['language'] == 'el') {
        return greek_format($date, $time, $dont_display_time);
    } else {
        return $date;
    }
}

/**
 * @brief remove seoconds from a given datetime
 * @param type $datetime
 * @return datetime without seconds
 */
function datetime_remove_seconds($datetime) {
    return preg_replace('/:\d\d$/', '', $datetime);
}

// Returns user's previous login date, or today's date if no previous login
function last_login($uid) {
       
    $last_login = Database::get()->querySingle("SELECT DATE_FORMAT(MAX(`when`), '%Y-%m-%d') AS last_login FROM loginout
                          WHERE id_user = ?d AND action = 'LOGIN'", $uid)->last_login;
    if (!$last_login) {
        $last_login = date('Y-m-d');
    }
    return $last_login;
}

// Create a JavaScript-escaped mailto: link
function mailto($address, $alternative = '(e-mail address hidden)') {
    if (empty($address)) {
        return '&nbsp;';
    } else {
        $prog = urlenc("var a='" . urlenc(str_replace('@', '&#64;', $address)) .
                "';document.write('<a href=\"mailto:'+unescape(a)+'\">'+unescape(a)+'</a>');");
        return "<script type='text/javascript'>eval(unescape('" .
                q($prog) . "'));</script><noscript>" . q($alternative) . "</noscript>";
    }
}

function urlenc($string) {
    $out = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $out .= sprintf("%%%02x", ord(substr($string, $i, 1)));
    }
    return $out;
}

/**
 * get user data
 * @param type $user_id
 * @return object
 */
function user_get_data($user_id) {
    
    $data = Database::get()->querySingle("SELECT id, surname, givenname, username, email, phone, status
                                            FROM user WHERE id = ?d", $user_id);

    if ($data) {
        return $data;
    } else {
        return null;
    }
}

/**
 * Function for generating fixed-length strings containing random characters.
 * 
 * @param int $length
 * @return string
 */
function randomkeys($length) {
    $key = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    for ($i = 0; $i < $length; $i++) {
        $key .= $codeAlphabet[crypto_rand_secure(0, strlen($codeAlphabet) - 1)];
    }
    return $key;
}

// A helper function, when passed a number representing KB,
// and optionally the number of decimal places required,
// it returns a formated number string, with unit identifier.
function format_bytesize($kbytes, $dec_places = 2) {
    global $text;
    if ($kbytes > 1048576) {
        $result = sprintf('%.' . $dec_places . 'f', $kbytes / 1048576);
        $result .= '&nbsp;Gb';
    } elseif ($kbytes > 1024) {
        $result = sprintf('%.' . $dec_places . 'f', $kbytes / 1024);
        $result .= '&nbsp;Mb';
    } else {
        $result = sprintf('%.' . $dec_places . 'f', $kbytes);
        $result .= '&nbsp;Kb';
    }
    return $result;
}

/*
 * Checks if Javascript is enabled on the client browser
 * A cookie is set on the header by javascript code.
 * If this cookie isn't set, it means javascript isn't enabled.
 *
 * return boolean enabling state of javascript
 * author Hugues Peeters <hugues.peeters@claroline.net>
 */

function is_javascript_enabled() {
    return isset($_COOKIE['javascriptEnabled']) and $_COOKIE['javascriptEnabled'];
}

function add_check_if_javascript_enabled_js() {
    return '<script type="text/javascript">document.cookie="javascriptEnabled=true";</script>';
}

/*
 * to create missing directory in a gived path
 *
 * @returns a resource identifier or false if the query was not executed correctly.
 * @author KilerCris@Mail.com original function from  php manual
 * @author Christophe Gesche gesche@ipm.ucl.ac.be Claroline Team
 * @since  28-Aug-2001 09:12
 * @param sting         $path           wanted path
 */

function mkpath($path) {
    $path = str_replace("/", "\\", $path);
    $dirs = explode("\\", $path);
    $path = $dirs[0];
    for ($i = 1; $i < count($dirs); $i++) {
        $path .= "/" . $dirs[$i];
        if (file_exists($path)) {
            if (!is_dir($path)) {
                return false;
            }
        } elseif (!mkdir($path, 0755)) {
            return false;
        }
    }
    return true;
}

// check if we can display activationlink (e.g. module_id is one of our modules)
function display_activation_link($module_id) {
    global $modules;

    if (!defined('STATIC_MODULE') and $module_id && array_key_exists($module_id, $modules)) {
        return true;
    } else {
        return false;
    }
}

/**
 * @brief checks if a module is visible
 * @global type $course_id
 * @param type $module_id
 * @return boolean
 */
function visible_module($module_id) {
    global $course_id;
   
    $v = Database::get()->querySingle("SELECT visible FROM course_module
                                WHERE module_id = ?d AND
                                course_id = ?d", $module_id, $course_id)->visible;
    if ($v == 1) {
        return true;
    } else {
        return false;
    }
}

// Find the current module id from the script URL
function current_module_id() {
    global $modules, $urlAppend, $static_module_paths;
    static $module_id;

    if (isset($module_id)) {
        return $module_id;
    }

    $module_path = str_replace($urlAppend . 'modules/', '', $_SERVER['SCRIPT_NAME']);
    $link = preg_replace('|/.*$|', '', $module_path);
    if (isset($static_module_paths[$link])) {
        $module_id = $static_module_paths[$link];
        define('STATIC_MODULE', true);
        return false;
    }

    foreach ($modules as $mid => $info) {
        if ($info['link'] == $link) {
            $module_id = $mid;
            return $mid;
        }
    }
    return false;
}

// Returns true if a string is invalid UTF-8
function invalid_utf8($s) {
    return !mb_detect_encoding($s, 'UTF-8', true);
}

function utf8_to_cp1253($s) {
    // First try with iconv() directly
    $cp1253 = @iconv('UTF-8', 'Windows-1253', $s);
    if ($cp1253 === false) {
        // ... if it fails, fall back to indirect conversion
        $cp1253 = str_replace("\xB6", "\xA2", @iconv('UTF-8', 'ISO-8859-7', $s));
    }
    return $cp1253;
}

// Converts a string from Code Page 737 (DOS Greek) to UTF-8
function cp737_to_utf8($s) {
    // First try with iconv()...
    $cp737 = @iconv('CP737', 'UTF-8', $s);
    if ($cp737 !== false) {
        return $cp737;
    } else {
        // ... if it fails, fall back to manual conversion
        return strtr($s, array("\x80" => 'Α', "\x81" => 'Β', "\x82" => 'Γ', "\x83" => 'Δ',
                               "\x84" => 'Ε', "\x85" => 'Ζ', "\x86" => 'Η', "\x87" => 'Θ',
                               "\x88" => 'Ι', "\x89" => 'Κ', "\x8a" => 'Λ', "\x8b" => 'Μ',
                               "\x8c" => 'Ν', "\x8d" => 'Ξ', "\x8e" => 'Ο', "\x8f" => 'Π',
                               "\x90" => 'Ρ', "\x91" => 'Σ', "\x92" => 'Τ', "\x93" => 'Υ',
                               "\x94" => 'Φ', "\x95" => 'Χ', "\x96" => 'Ψ', "\x97" => 'Ω',
                               "\x98" => 'α', "\x99" => 'β', "\x9a" => 'γ', "\x9b" => 'δ',
                               "\x9c" => 'ε', "\x9d" => 'ζ', "\x9e" => 'η', "\x9f" => 'θ',
                               "\xa0" => 'ι', "\xa1" => 'κ', "\xa2" => 'λ', "\xa3" => 'μ',
                               "\xa4" => 'ν', "\xa5" => 'ξ', "\xa6" => 'ο', "\xa7" => 'π',
                               "\xa8" => 'ρ', "\xa9" => 'σ', "\xaa" => 'ς', "\xab" => 'τ',
                               "\xac" => 'υ', "\xad" => 'φ', "\xae" => 'χ', "\xaf" => 'ψ',
                               "\xb0" => '░', "\xb1" => '▒', "\xb2" => '▓', "\xb3" => '│',
                               "\xb4" => '┤', "\xb5" => '╡', "\xb6" => '╢', "\xb7" => '╖',
                               "\xb8" => '╕', "\xb9" => '╣', "\xba" => '║', "\xbb" => '╗',
                               "\xbc" => '╝', "\xbd" => '╜', "\xbe" => '╛', "\xbf" => '┐',
                               "\xc0" => '└', "\xc1" => '┴', "\xc2" => '┬', "\xc3" => '├',
                               "\xc4" => '─', "\xc5" => '┼', "\xc6" => '╞', "\xc7" => '╟',
                               "\xc8" => '╚', "\xc9" => '╔', "\xca" => '╩', "\xcb" => '╦',
                               "\xcc" => '╠', "\xcd" => '═', "\xce" => '╬', "\xcf" => '╧',
                               "\xd0" => '╨', "\xd1" => '╤', "\xd2" => '╥', "\xd3" => '╙',
                               "\xd4" => '╘', "\xd5" => '╒', "\xd6" => '╓', "\xd7" => '╫',
                               "\xd8" => '╪', "\xd9" => '┘', "\xda" => '┌', "\xdb" => '█',
                               "\xdc" => '▄', "\xdd" => '▌', "\xde" => '▐', "\xdf" => '▀',
                               "\xe0" => 'ω', "\xe1" => 'ά', "\xe2" => 'έ', "\xe3" => 'ή',
                               "\xe4" => 'ϊ', "\xe5" => 'ί', "\xe6" => 'ό', "\xe7" => 'ύ',
                               "\xe8" => 'ϋ', "\xe9" => 'ώ', "\xea" => 'Ά', "\xeb" => 'Έ',
                               "\xec" => 'Ή', "\xed" => 'Ί', "\xee" => 'Ό', "\xef" => 'Ύ',
                               "\xf0" => 'Ώ', "\xf1" => '±', "\xf2" => '≥', "\xf3" => '≤',
                               "\xf4" => 'Ϊ', "\xf5" => 'Ϋ', "\xf6" => '÷', "\xf7" => '≈',
                               "\xf8" => '°', "\xf9" => '∙', "\xfa" => '·', "\xfb" => '√',
                               "\xfc" => 'ⁿ', "\xfd" => '²', "\xfe" => '■', "\xff" => ' '));
    }
}

/**
 * Return a new random filename, with the given extension
 * @param type $extension
 * @return string
 */
function safe_filename($extension = '') {
    $prefix = sprintf('%08x', time()) . randomkeys(4);
    if (empty($extension)) {
        return $prefix;
    } else {
        return $prefix . '.' . $extension;
    }
}

function get_file_extension($filename) {
    $matches = array();
    if (preg_match('/\.(tar\.(z|gz|bz|bz2))$/i', $filename, $matches)) {
        return strtolower($matches[1]);
    } elseif (preg_match('/\.([a-zA-Z0-9_-]{1,8})$/i', $filename, $matches)) {
        return strtolower($matches[1]);
    } else {
        return '';
    }
}

// Remove whitespace from start and end of string, convert
// sequences of whitespace characters to single spaces
// and remove non-printable characters, while preserving new lines
function canonicalize_whitespace($s) {
    return str_replace(array(" \1 ", " \1", "\1 ", "\1"), "\n", preg_replace('/[\t ]+/', ' ', str_replace(array("\r\n", "\n", "\r"), "\1", trim(preg_replace('/[\x00-\x08\x0C\x0E-\x1F\x7F]/', '', $s)))));
}

// Remove characters which can't appear in filenames
function remove_filename_unsafe_chars($s) {
    return preg_replace('/[<>:"\/\\\\|?*]/', '', canonicalize_whitespace($s));
}

/**
 * @brief check recourse accessibility
 * @global type $course_code
 * @param type $public
 * @return boolean
 */
function resource_access($visible, $public) {
    global $course_code;
    if ($visible) {
        if ($public) {
            return TRUE;
        } else {
            if (isset($_SESSION['uid']) and (isset($_SESSION['courses'][$course_code]) and $_SESSION['courses'][$course_code])) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    } else {
        return FALSE;
    }
}

# Only languages defined below are available for selection in the UI
# If you add any new languages, make sure they are defined in the
# next array as well
$native_language_names_init = array(
    'el' => 'Ελληνικά',
    'en' => 'English',
    'es' => 'Español',
    'cs' => 'Česky',
    'sq' => 'Shqip',
    'bg' => 'Български',
    'ca' => 'Català',
    'da' => 'Dansk',
    'nl' => 'Nederlands',
    'fi' => 'Suomi',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'is' => 'Íslenska',
    'it' => 'Italiano',
    'jp' => '日本語',
    'pl' => 'Polski',
    'ru' => 'Русский',
    'tr' => 'Türkçe',
    'sv' => 'Svenska',
    'xx' => 'Variable Names',
);

$language_codes = array(
    'el' => 'greek',
    'en' => 'english',
    'es' => 'spanish',
    'cs' => 'czech',
    'sq' => 'albanian',
    'bg' => 'bulgarian',
    'ca' => 'catalan',
    'da' => 'danish',
    'nl' => 'dutch',
    'fi' => 'finnish',
    'fr' => 'french',
    'de' => 'german',
    'is' => 'icelandic',
    'it' => 'italian',
    'jp' => 'japanese',
    'pl' => 'polish',
    'ru' => 'russian',
    'tr' => 'turkish',
    'sv' => 'swedish',
    'xx' => 'variables',
);

// Convert language code to language name in English lowercase (for message files etc.)
// Returns 'english' if code is not in array
function langcode_to_name($langcode) {
    global $language_codes;
    if (isset($language_codes[$langcode])) {
        return $language_codes[$langcode];
    } else {
        return 'english';
    }
}

// Convert language name to language code
function langname_to_code($langname) {
    global $language_codes;
    $langcode = array_search($langname, $language_codes);
    if ($langcode) {
        return $langcode;
    } else {
        return 'en';
    }
}

function append_units($amount, $singular, $plural) {
    if ($amount == 1) {
        return $amount . ' ' . $singular;
    } else {
        return $amount . ' ' . $plural;
    }
}

// Convert $sec to days, hours, minutes, seconds;
function format_time_duration($sec) {
    global $langsecond, $langseconds, $langminute, $langminutes, $langhour, $langhours, $langDay, $langDays;

    if ($sec < 60) {
        return append_units($sec, $langsecond, $langseconds);
    }
    $min = floor($sec / 60);
    $sec = $sec % 60;
    if ($min < 2) {
        return append_units($min, $langminute, $langminutes) .
                (($sec == 0) ? '' : (' ' . append_units($sec, $langsecond, $langseconds)));
    }
    if ($min < 60) {
        return append_units($min, $langminute, $langminutes);
    }
    $hour = floor($min / 60);
    $min = $min % 60;
    if ($hour < 24) {
        append_units($hour, $langhour, $langhours) .
                (($min == 0) ? '' : (' ' . append_units($min, $langminute, $langminutes)));
    }
    $day = floor($hour / 24);
    $hour = $hour % 24;
    return (($day == 0) ? '' : (' ' . append_units($day, $langDay, $langDays))) .
            (($hour == 0) ? '' : (' ' . append_units($hour, $langhour, $langhours))) .
            (($min == 0) ? '' : (' ' . append_units($min, $langminute, $langminutes)));
}

// Move entry $id in $table to $direction 'up' or 'down', where
// order is in field $order_field and id in $id_field
// Use $condition as extra SQL to limit the operation
function move_order($table, $id_field, $id, $order_field, $direction, $condition = '') {
    if ($condition) {
        $condition = ' AND ' . $condition;
    }
    if ($direction == 'down') {
        $op = '>';
        $desc = '';
    } else {
        $op = '<';
        $desc = 'DESC';
    }
    
    $sql = Database::get()->querySingle("SELECT `$order_field` FROM `$table`
                         WHERE `$id_field` = ?d", $id);
    if (!$sql) {
        return false;
    }
    $current = $sql->$order_field;
    $sql = Database::get()->querySingle("SELECT `$id_field`, `$order_field` FROM `$table`
                        WHERE `$order_field` $op '$current' $condition
                        ORDER BY `$order_field` $desc LIMIT 1");
    if ($sql) {
        $next_id = $sql->$id_field;
        $next = $sql->$order_field;        
        Database::get()->query("UPDATE `$table` SET `$order_field` = $next
                          WHERE `$id_field` = $id");        
        Database::get()->query("UPDATE `$table` SET `$order_field` = $current
                          WHERE `$id_field` = $next_id");        
        return true;
    }
    return false;
}

// Add a link to the appropriate course unit if the page was requested
// with a unit=ID parametere. This happens if the user got to the module
// page from a unit resource link. If entry_page == true this is the initial page of module
// and is assumed that you're exiting the current unit unless $_GET['unit'] is set
function add_units_navigation($entry_page = false) {
    global $navigation, $course_id, $is_editor, $course_code;
    
    if ($entry_page and !isset($_GET['unit'])) {
        unset($_SESSION['unit']);
        return false;
    } elseif (isset($_GET['unit']) or isset($_SESSION['unit'])) {
        if ($is_editor) {
            $visibility_check = '';
        } else {
            $visibility_check = "AND visible = 1";
        }
        if (isset($_GET['unit'])) {
            $unit_id = intval($_GET['unit']);
        } elseif (isset($_SESSION['unit'])) {
            $unit_id = intval($_SESSION['unit']);
        }
        
        $q = Database::get()->querySingle("SELECT title FROM course_units
                       WHERE id = $unit_id AND course_id = ?d $visibility_check", $course_id);
        if ($q) {
            $unit_name = $q->title;            
            $navigation[] = array("url" => "../units/index.php?course=$course_code&amp;id=$unit_id", "name" => htmlspecialchars($unit_name));
        }
        return true;
    } else {
        return false;
    }
}

// Cut a string to be no more than $maxlen characters long, appending
// the $postfix (default: ellipsis "...") if so
function ellipsize($string, $maxlen, $postfix = '...') {
    if (mb_strlen($string, 'UTF-8') > $maxlen) {
        return (mb_substr($string, 0, $maxlen, 'UTF-8')) . $postfix;
    } else {
        return $string;
    }
}

/*
 * Cut a string to be no more than $maxlen characters long, appending
 * the $postfix (default: ellipsis "...") if so respecting html tags
 */

function ellipsize_html($string, $maxlen, $postfix = '&hellip;') {
    $output = new HtmlCutString($string, $maxlen, $postfix);
    return $output->cut();
}

/**
 * @brief Find the title of a course from its code
 * @param type $code
 * @return boolean
 */
function course_code_to_title($code) {
    $r = Database::get()->querySingle("SELECT title FROM course WHERE code = ?s", $code);
    if ($r) {                
        return $r->title;
    } else {
        return false;
    }
}

/**
 * @brief Find the course id of a course from its code
 * @param type $code
 * @return boolean
 */
function course_code_to_id($code) {    
    $r = Database::get()->querySingle("SELECT id FROM course WHERE code = ?s", $code);
    if ($r) {
           return $r->id;
    } else {
        return false;
    }
}


/**
 * @brief Find the title of a course from its id
 * @param type $cid
 * @return boolean
 */
function course_id_to_title($cid) {    
    $r = Database::get()->querySingle("SELECT title FROM course WHERE id = ?d", $cid);
    if ($r) {        
        return $r->title;
    } else {
        return false;
    }
}

/**
 * @brief Find the course code from its id
 * @param type $cid
 * @return boolean
 */
function course_id_to_code($cid) {   
    $r = Database::get()->querySingle("SELECT code FROM course WHERE id = ?d", $cid );
    if ($r) {        
        return $r->code;
    } else {
        return false;
    }
}


/**
 * @brief Find the public course code from its id
 * @param type $cid
 * @return boolean
 */
function course_id_to_public_code($cid) {    
    $r = Database::get()->querySingle("SELECT public_code FROM course WHERE id = ?d", $cid);
    if ($r) {        
        return $r->public_code;
    } else {
        return false;
    }
}

/**
 * @global type $webDir
 * @param type $cid
 * @brief Delete course with id = $cid
 */
function delete_course($cid) {
    global $webDir;

    $course_code = course_id_to_code($cid);

    Database::get()->query("DELETE FROM announcement WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM document WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM ebook_subsection WHERE section_id IN
                         (SELECT ebook_section.id FROM ebook_section, ebook
                                 WHERE ebook_section.ebook_id = ebook.id AND
                                       ebook.course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM ebook_section WHERE id IN
                         (SELECT id FROM ebook WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM ebook WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM forum_notify WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM glossary WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM group_members WHERE group_id IN
                         (SELECT id FROM `group` WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM `group` WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM group_properties WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM link WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM link_category WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM agenda WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM course_review WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM unit_resources WHERE unit_id IN
                         (SELECT id FROM course_units WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM course_units WHERE course_id = ?d", $cid);
    // check if we have guest account. If yes delete him.
    $guest_user = Database::get()->querySingle("SELECT user_id FROM course_user WHERE course_id = ?d AND status = ?d", $cid, USER_GUEST);
    if ($guest_user) {
        deleteUser($guest_user->user_id, true);
    }
    Database::get()->query("DELETE FROM course_user WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM course_department WHERE course = ?d", $cid);
    Database::get()->query("DELETE FROM course WHERE id = ?d", $cid);
    Database::get()->query("DELETE FROM video WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM videolink WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM dropbox_attachment WHERE msg_id IN (SELECT id FROM dropbox_msg WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM dropbox_index WHERE msg_id IN (SELECT id FROM dropbox_msg WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM dropbox_msg WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM lp_asset WHERE module_id IN (SELECT module_id FROM lp_module WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM lp_rel_learnPath_module WHERE learnPath_id IN (SELECT learnPath_id FROM lp_learnPath WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM lp_user_module_progress WHERE learnPath_id IN (SELECT learnPath_id FROM lp_learnPath WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM lp_module WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM lp_learnPath WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM wiki_pages_content WHERE pid IN (SELECT id FROM wiki_pages WHERE wiki_id IN (SELECT id FROM wiki_properties WHERE course_id = ?d))", $cid);
    Database::get()->query("DELETE FROM wiki_pages WHERE wiki_id IN (SELECT id FROM wiki_properties WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM wiki_acls WHERE wiki_id IN (SELECT id FROM wiki_properties WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM wiki_properties WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM poll_question_answer WHERE pqid IN (SELECT pqid FROM poll_question WHERE pid IN (SELECT pid FROM poll WHERE course_id = ?d))", $cid);
    Database::get()->query("DELETE FROM poll_answer_record WHERE pid IN (SELECT pid FROM poll WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM poll_question WHERE pid IN (SELECT pid FROM poll WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM poll WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM assignment_submit WHERE assignment_id IN (SELECT id FROM assignment WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM assignment_to_specific WHERE assignment_id IN (SELECT id FROM assignment WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM assignment WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM exercise_with_questions WHERE question_id IN (SELECT id FROM exercise_question WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM exercise_with_questions WHERE exercise_id IN (SELECT id FROM exercise WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM exercise_answer WHERE question_id IN (SELECT id FROM exercise_question WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM exercise_question WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM exercise_question_cats WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM exercise_answer_record WHERE eurid IN (SELECT a.eurid FROM exercise_user_record a, exercise b WHERE a.eid = b.id AND b.course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM exercise_user_record WHERE eid IN (SELECT id FROM exercise WHERE course_id = ?d)", $cid);
    Database::get()->query("DELETE FROM exercise WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM course_module WHERE course_id = ?d", $cid);
    Database::get()->query("DELETE FROM course_settings WHERE course_id = ?d", $cid);

    $garbage = "$webDir/courses/garbage";
    if (!is_dir($garbage)) {
        mkdir($garbage, 0775);
    }
    rename("$webDir/courses/$course_code", "$garbage/$course_code");
    removeDir("$webDir/video/$course_code");
    // refresh index
    require_once 'modules/search/indexer.class.php';
    Indexer::queueAsync(Indexer::REQUEST_REMOVEALLBYCOURSE, Indexer::RESOURCE_IDX, $cid);
    
    Database::get()->query("UPDATE oai_record SET deleted = 1, datestamp = ?t WHERE course_id = ?d", gmdate('Y-m-d H:i:s'), $cid);
}

/**
 * Delete a user and all his dependencies.
 * 
 * @param  integer $id - the id of the user.
 * @return boolean     - returns true if deletion was successful, false otherwise.
 */
function deleteUser($id, $log) {

    $u = intval($id);

    if ($u == 1) {
        return false;
    } else {
        // validate if this is an existing user
        if (Database::get()->querySingle("SELECT * FROM user WHERE id = ?d", $u)) {
            // delete everything
            Database::get()->query("DELETE FROM actions_daily WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM admin WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM assignment_submit WHERE uid = ?d", $u);
            Database::get()->query("DELETE FROM course_user WHERE user_id = ?d", $u);
            Database::get()->query("DELETE dropbox_attachment FROM dropbox_attachment INNER JOIN dropbox_msg ON dropbox_attachment.msg_id = dropbox_msg.id 
                                    WHERE dropbox_msg.author_id = ?d", $u);
            Database::get()->query("DELETE dropbox_index FROM dropbox_index INNER JOIN dropbox_msg ON dropbox_index.msg_id = dropbox_msg.id 
                                    WHERE dropbox_msg.author_id = ?d", $u);
            Database::get()->query("DELETE FROM dropbox_index WHERE recipient_id = ?d", $u);
            Database::get()->query("DELETE FROM dropbox_msg WHERE author_id = ?d", $u);
            Database::get()->query("DELETE FROM exercise_user_record WHERE uid = ?d", $u);
            Database::get()->query("DELETE FROM forum_notify WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM forum_post WHERE poster_id = ?d", $u);
            Database::get()->query("DELETE FROM forum_topic WHERE poster_id = ?d", $u);
            Database::get()->query("DELETE FROM forum_user_stats WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM group_members WHERE user_id = ?d", $u);
            if ($log) {
                Database::get()->query("DELETE FROM log WHERE user_id = ?d", $u);
            }
            Database::get()->query("DELETE FROM loginout WHERE id_user = ?d", $u);
            Database::get()->query("DELETE FROM logins WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM lp_user_module_progress WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM poll WHERE creator_id = ?d", $u);
            Database::get()->query("DELETE FROM poll_answer_record WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM user_department WHERE user = ?d", $u);
            Database::get()->query("DELETE FROM wiki_pages WHERE owner_id = ?d", $u);
            Database::get()->query("DELETE FROM wiki_pages_content WHERE editor_id = ?d", $u);
            Database::get()->query("DELETE FROM comments WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM blog_post WHERE user_id = ?d", $u);
            Database::get()->query("DELETE FROM user WHERE id = ?d", $u);
            Database::get()->query("DELETE FROM note WHERE user_id = ?d" , $u);
            Database::get()->query("DELETE FROM personal_calendar WHERE user_id = ?d" , $u);
            Database::get()->query("DELETE FROM personal_calendar_settings WHERE user_id = ?d" , $u);
            return true;
        } else {
            return false;
        }
    }
}

function csv_escape($string, $force = false) {
    global $charset;

    if ($charset != 'UTF-8') {
        if ($charset == 'Windows-1253') {
            $string = utf8_to_cp1253($string);
        } else {
            $string = iconv('UTF-8', $charset, $string);
        }
    }
    $string = preg_replace('/[\r\n]+/', ' ', $string);
    if (!preg_match("/[ ,!;\"'\\\\]/", $string) and !$force) {
        return $string;
    } else {
        return '"' . str_replace('"', '""', $string) . '"';
    }
}

/**
 * @brief Return the value of a key from the config table, or a default value (or null) if not found
 * @param type $key
 * @param type $default
 * @return type
 */
function get_config($key, $default = null) {
       
    $r = Database::get()->querySingle("SELECT `value` FROM config WHERE `key` = ?s", $key);
    if ($r) {
        $row = $r->value;
        return $row;
    } else {
        return $default;
    }
}

/**
 * @brief Set the value of a key in the config table
 * @param type $key
 * @param type $value
 */
function set_config($key, $value) {
   
    Database::get()->query("REPLACE INTO config (`key`, `value`) VALUES (?s, ?s)", $key, $value);
}

// Copy variables from $_POST[] to $GLOBALS[], trimming and canonicalizing whitespace
// $var_array = array('var1' => true, 'var2' => false, [varname] => required...)
// Returns true if all vars with required=true are set, false if not (by default)
// If $what = 'any' returns true if any variable is set
function register_posted_variables($var_array, $what = 'all', $callback = null) {
    global $missing_posted_variables;

    if (!isset($missing_posted_variables)) {
        $missing_posted_variables = array();
    }

    $all_set = true;
    $any_set = false;
    foreach ($var_array as $varname => $required) {
        if (isset($_POST[$varname])) {
            $GLOBALS[$varname] = canonicalize_whitespace($_POST[$varname]);
            if ($required and empty($GLOBALS[$varname])) {
                $missing_posted_variables[$varname] = true;
                $all_set = false;
            }
            if (!empty($GLOBALS[$varname])) {
                $any_set = true;
            }
        } else {
            $GLOBALS[$varname] = '';
            if ($required) {
                $missing_posted_variables[$varname] = true;
                $all_set = false;
            }
        }
        if (is_callable($callback)) {
            $GLOBALS[$varname] = $callback($GLOBALS[$varname]);
        }
    }
    if ($what == 'any') {
        return $any_set;
    } else {
        return $all_set;
    }
}

/**
 * Display a textarea with name $name using the rich text editor
 * Apply automatically various fixes for the text to be edited
 * @global type $head_content
 * @global type $language
 * @global type $purifier
 * @global type $urlAppend
 * @global type $course_code
 * @global type $langPopUp
 * @global type $langPopUpFrame
 * @global type $is_editor
 * @global type $is_admin
 * @param type $name
 * @param type $rows
 * @param type $cols
 * @param type $text
 * @param type $extra
 * @return type
 */
function rich_text_editor($name, $rows, $cols, $text, $onFocus = false) {
    global $head_content, $language, $urlAppend, $course_code, $langPopUp, $langPopUpFrame, $is_editor, $is_admin;
    static $init_done = false;
    if (!$init_done) {
        $init_done = true;
        $filebrowser = $url = '';
        $activemodule = 'document/index.php';
        if (isset($course_code) && !empty($course_code)) {
            $filebrowser = "file_browser_callback : openDocsPicker,";
            if (!$is_editor) {
                $cid = course_code_to_id($course_code);
                $module = Database::get()->querySingle("SELECT * FROM course_module
                            WHERE course_id = ?d
                              AND (module_id =" . MODULE_ID_DOCS . " OR module_id =" . MODULE_ID_VIDEO . " OR module_id =" . MODULE_ID_LINKS . ")
                              AND VISIBLE = 1 ORDER BY module_id", $cid);
                if ($module === false) {
                    $filebrowser = '';
                } else {
                    switch ($module->module_id) {
                    case MODULE_ID_LINKS:
                        $activemodule = 'link/index.php';
                        break;
                    case MODULE_ID_DOCS:
                        $activemodule = 'document/index.php';
                        break;
                    case MODULE_ID_VIDEO:
                        $activemodule = 'video/index.php';
                        break;
                    default:
                        $filebrowser = '';
                        break;
                    }
                }
            }
            $url = $urlAppend . "modules/$activemodule?course=$course_code&embedtype=tinymce&docsfilter=";
        } elseif ($is_admin) { /* special case for admin announcements */
            $filebrowser = "file_browser_callback : openDocsPicker,";
            $url = $urlAppend . "modules/admin/commondocs.php?embedtype=tinymce&docsfilter=";
        }
        if ($onFocus) {
            $focus_init = ",
                menubar: false,
                statusbar: false,   
                setup: function (theEditor) {
                    theEditor.on('focus', function () {
                        $(this.contentAreaContainer.parentElement).find('div.mce-toolbar-grp').show();
                    });
                    theEditor.on('blur', function () {
                        $(this.contentAreaContainer.parentElement).find('div.mce-toolbar-grp').hide();
                    });
                    theEditor.on('init', function() {
                        $(this.contentAreaContainer.parentElement).find('div.mce-toolbar-grp').hide();
                    });
                }";
        } else {
            $focus_init ='';
        }
        load_js('tinymce/tinymce.gzip.js');
        $head_content .= "
<script type='text/javascript'>

function openDocsPicker(field_name, url, type, win) {
    tinymce.activeEditor.windowManager.open({
        file: '$url' + type,
        title: 'Resources Browser',
        width: 800,
        height: 600,
        resizable: 'yes',
        inline: 'yes',
        close_previous: 'no',
        popup_css: false
    }, {
        window: win,
        input: field_name
    });
    return false;
}

tinymce.init({
    // General options
    selector: 'textarea.mceEditor',
    language: '$language',
    theme: 'modern',
    image_class_list: [
        {title: 'Responsive', value: 'img-responsive'},
        {title: 'None', value: ''}
    ],
    plugins: 'pagebreak,save,image,link,media,eclmedia,print,contextmenu,paste,noneditable,visualchars,nonbreaking,template,wordcount,advlist,emoticons,preview,searchreplace,table,insertdatetime,code',
    entity_encoding: 'raw',
    relative_urls: false,
    image_advtab: true,
    link_class_list: [
        {title: 'None', value: ''},
        {title: '$langPopUp', value: 'colorbox'},
        {title: '$langPopUpFrame', value: 'colorboxframe'}
    ],
    $filebrowser

    // Toolbar options
    toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image eclmedia code',
    // Replace values for the template plugin
    template_replace_values: {
            username : 'Open eClass',
            staffid : '991234'
    }
    $focus_init
});
</script>";
    }

    /* $text = str_replace(array('<m>', '</m>', '<M>', '</M>'),
      array('[m]', '[/m]', '[m]', '[/m]'),
      $text); */

    return "<textarea class='mceEditor' name='$name' rows='$rows' cols='$cols'>" .
            q(str_replace('{', '&#123;', $text)) .
            "</textarea>\n";
}

// Display a simple textarea with name $name
// Apply automatically various fixes for the text to be edited
function text_area($name, $rows, $cols, $text, $extra = '') {

    global $purifier;

    $text = str_replace(array('<m>', '</m>', '<M>', '</M>'), array('[m]', '[/m]', '[m]', '[/m]'), $text);
    if (strpos($extra, 'class=') === false) {
        $extra .= ' class="form-control mceNoEditor"';
    }
    return "<textarea name='$name' rows='$rows' cols='$cols' $extra>" .
            q(str_replace('{', '&#123;', $text)) .
            "</textarea>\n";
}

/**
 * 
 * @param type $unit_id
 * @return int
 */
function add_unit_resource_max_order($unit_id) {
    
    $q = Database::get()->querySingle("SELECT MAX(`order`) AS maxorder FROM unit_resources WHERE unit_id = ?d", $unit_id);   
    if ($q) {
        $order = $q->maxorder;
        return max(0, $order) + 1;
    } else {
        return 1;
    }
}

/**
 * 
 * @param type $unit_id
 * @return type
 */
function new_description_res_id($unit_id) {
    
    $q = Database::get()->querySingle("SELECT MAX(res_id) AS maxresid FROM unit_resources WHERE unit_id = ?d", $unit_id);    
    $max_res_id = $q->maxresid;
    return 1 + max(count($GLOBALS['titreBloc']), $max_res_id);
}

/**
 * @brief add resource to course units
 * @param type $unit_id
 * @param type $type
 * @param type $res_id
 * @param type $title
 * @param type $content
 * @param type $visibility
 * @param type $date
 * @return type
 */
function add_unit_resource($unit_id, $type, $res_id, $title, $content, $visibility = 0, $date = false) {
    
    if (!$date) {
        $date = "NOW()";
    }
    if ($res_id === false) {
        $res_id = new_description_res_id($unit_id);
        $order = add_unit_resource_max_order($unit_id);
    } elseif ($res_id < 0) {
        $order = $res_id;
    } else {
        $order = add_unit_resource_max_order($unit_id);
    }
    $q = Database::get()->querySingle("SELECT id FROM unit_resources WHERE
                                `unit_id` = ?d AND
                                `type` = ?s AND
                                `res_id` = ?d", $unit_id, $type, $res_id);    
    if ($q) {
        $id = $q->id;
        Database::get()->query("UPDATE unit_resources SET
                                        `title` = ?s,
                                        `comments` = ?s,
                                        `date` = $date
                                 WHERE id = ?d", $title, $content, $id);
        return;
    }
    Database::get()->query("INSERT INTO unit_resources SET
                                `unit_id` = ?d,
                                `title` = ?s,
                                `comments` = ?s,
                                `date` = $date,
                                `type` = ?s,
                                `visible` = ?d,
                                `res_id` = ?d,
                                `order` = ?d", $unit_id, $title, $content, $type, $visibility, $res_id, $order);
    return;
}

/**
 * 
 * @global null $maxorder
 * @global type $course_id
 */
function units_set_maxorder() {
    
    global $maxorder, $course_id;
    
    $q = Database::get()->querySingle("SELECT MAX(`order`) as max_order FROM course_units WHERE course_id = ?d", $course_id);
    
    $maxorder = $q->max_order;
    
    if ($maxorder <= 0) {
        $maxorder = null;
    }    
}

/**
 * 
 * @global type $langCourseUnitModified
 * @global type $langCourseUnitAdded
 * @global null $maxorder
 * @global type $course_id
 * @global type $course_code
 * @global type $webDir
 * @return type
 */
function handle_unit_info_edit() {
    
    global $langCourseUnitModified, $langCourseUnitAdded, $maxorder, $course_id, $course_code, $webDir;
    
    $title = $_REQUEST['unittitle'];
    $descr = $_REQUEST['unitdescr'];
    if (isset($_REQUEST['unit_id'])) { // update course unit
        $unit_id = $_REQUEST['unit_id'];
        Database::get()->query("UPDATE course_units SET
                                        title = ?s,
                                        comments = ?s
                                    WHERE id = ?d AND course_id = ?d", $title, $descr, $unit_id, $course_id);        
        $successmsg = $langCourseUnitModified;
    } else { // add new course unit
        $order = $maxorder + 1;        
        $q = Database::get()->query("INSERT INTO course_units SET
                                  title = ?s, comments = ?s, visible = 1,
                                 `order` = ?d, course_id = ?d", $title, $descr, $order, $course_id);
        $successmsg = $langCourseUnitAdded;
        $unit_id = $q->lastInsertID;
    }
    // update index    
    require_once 'modules/search/indexer.class.php';
    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_UNIT, $unit_id);
    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_COURSE, $course_id);
    // refresh course metadata
    require_once 'modules/course_metadata/CourseXML.php';
    CourseXMLElement::refreshCourse($course_id, $course_code);

    return "<div class='alert alert-success'>$successmsg</div>";
}

function math_unescape($matches) {
    return html_entity_decode($matches[0]);
}

// Standard function to prepare some HTML text, possibly with math escapes, for display
function standard_text_escape($text, $mathimg = '../../courses/mathimg/') {
    global $purifier;

    $text = preg_replace_callback('/\[m\].*?\[\/m\]/s', 'math_unescape', $text);
    $html = $purifier->purify(mathfilter($text, 12, $mathimg));

    if (!isset($_SESSION['glossary_terms_regexp'])) {
        return $html;
    }

    $dom = new DOMDocument();
    // workaround because DOM doesn't handle utf8 encoding correctly.
    @$dom->loadHTML('<div>' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>');

    $xpath = new DOMXpath($dom);
    $textNodes = $xpath->query('//text()');
    foreach ($textNodes as $textNode) {
        if (!empty($textNode->data)) {
            $new_contents = glossary_expand($textNode->data);
            if ($new_contents != $textNode->data) {
                $newdoc = new DOMDocument();
                $newdoc->loadXML('<span>' . $new_contents . '</span>', LIBXML_NONET|LIBXML_DTDLOAD|LIBXML_DTDATTR);
                $newnode = $dom->importNode($newdoc->getElementsByTagName('span')->item(0), true);
                $textNode->parentNode->replaceChild($newnode, $textNode);
                unset($newdoc);
                unset($newnode);
            }
        }
    }
    $base_node = $dom->getElementsByTagName('div')->item(0);
    // iframe hack
    return preg_replace(array('|^<div>(.*)</div>$|s',
        '#(<iframe [^>]+)/>#'), array('\\1', '\\1></iframe>'), dom_save_html($dom, $base_node));
}

// Workaround for $dom->saveHTML($node) not working for PHP < 5.3.6
function dom_save_html($dom, $node) {
    if (version_compare(PHP_VERSION, '5.3.6') >= 0) {
        return $dom->saveHTML($node);
    } else {
        return $dom->saveXML($node);
    }
}

function purify($text) {
    global $purifier;
    return $purifier->purify($text);
}

// Expand glossary terms to HTML for tooltips with the definition
function glossary_expand($text) {
    return preg_replace_callback($_SESSION['glossary_terms_regexp'], 'glossary_expand_callback', $text);
}

function glossary_expand_callback($matches) {
    static $glossary_seen_terms;

    $term = mb_strtolower($matches[0], 'UTF-8');
    if (isset($glossary_seen_terms[$term])) {
        return $matches[0];
    }
    $glossary_seen_terms[$term] = true;
    if (!empty($_SESSION['glossary'][$term])) {
        $definition = ' title="' . q($_SESSION['glossary'][$term]) . '"';
    } else {
        $definition = '';
    }
    if (isset($_SESSION['glossary_url'][$term])) {
        return '<a href="' . q($_SESSION['glossary_url'][$term]) .
                '" target="_blank" class="glossary"' .
                $definition . '>' . $matches[0] . '</a>';
    } else {
        return '<span class="glossary"' .
                $definition . '>' . $matches[0] . '</span>';
    }
}

function get_glossary_terms($course_id) {
        
    $expand = Database::get()->querySingle("SELECT glossary_expand FROM course
                                                         WHERE id = ?d", $course_id)->glossary_expand;    
    if (!$expand) {
        return false;
    }

    $q = Database::get()->queryArray("SELECT term, definition, url FROM glossary
                              WHERE course_id = $course_id GROUP BY term");
    
    if (count($q) > intval(get_config('max_glossary_terms'))) {
        return false;
    }

    $_SESSION['glossary'] = array();
    $_SESSION['glossary_url'] = array();
    foreach ($q as $row) {
        $term = mb_strtolower($row->term, 'UTF-8');
        $_SESSION['glossary'][$term] = $row->definition;
        if (!empty($row->url)) {
            $_SESSION['glossary_url'][$term] = $row->url;
        }
    }
    $_SESSION['glossary_course_id'] = $course_id;
    return true;
}

function set_glossary_cache() {
    global $course_id;

    if (!isset($course_id)) {
        unset($_SESSION['glossary_terms_regexp']);
    } elseif (!isset($_SESSION['glossary']) or
            $_SESSION['glossary_course_id'] != $course_id) {
        if (get_glossary_terms($course_id) and count($_SESSION['glossary']) > 0) {
            // Test whether \b works correctly, workaround if not
            if (preg_match('/α\b/u', 'α')) {
                $spre = $spost = '\b';
            } else {
                $spre = '(?<=[\x01-\x40\x5B-\x60\x7B-\x7F]|^)';
                $spost = '(?=[\x01-\x40\x5B-\x60\x7B-\x7F]|$)';
            }
            $_SESSION['glossary_terms_regexp'] = chr(1) . $spre . '(';
            $begin = true;
            foreach (array_keys($_SESSION['glossary']) as $term) {
                $_SESSION['glossary_terms_regexp'] .= ($begin ? '' : '|') .
                        preg_quote($term);
                if ($begin) {
                    $begin = false;
                }
            }
            $_SESSION['glossary_terms_regexp'] .= ')' . $spost . chr(1) . 'ui';
        } else {
            unset($_SESSION['glossary_terms_regexp']);
        }
    }
}

function invalidate_glossary_cache() {
    unset($_SESSION['glossary']);
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function redirect_to_home_page($path='', $absolute=false) {
    global $urlServer;
    
    if (!$absolute) {
        $path = preg_replace('+^/+', '', $path);
        $path = $urlServer . $path;
    }
    header("Location: $path");
    exit;
}

function odd_even($k, $extra = '') {
    if (!empty($extra)) {
        $extra = ' ' . $extra;
    }
    if ($k % 2 == 0) {
        return " class='even$extra'";
    } else {
        return " class='odd$extra'";
    }
}

// Translate Greek characters to Latin
function greek_to_latin($string) {
    return str_replace(
            array(
        'α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η', 'θ', 'ι', 'κ', 'λ', 'μ', 'ν', 'ξ', 'ο', 'π',
        'ρ', 'σ', 'τ', 'υ', 'φ', 'χ', 'ψ', 'ω', 'Α', 'Β', 'Γ', 'Δ', 'Ε', 'Ζ', 'Η', 'Θ',
        'Ι', 'Κ', 'Λ', 'Μ', 'Ν', 'Ξ', 'Ο', 'Π', 'Ρ', 'Σ', 'Τ', 'Υ', 'Φ', 'Χ', 'Ψ', 'Ω',
        'ς', 'ά', 'έ', 'ή', 'ί', 'ύ', 'ό', 'ώ', 'Ά', 'Έ', 'Ή', 'Ί', 'Ύ', 'Ό', 'Ώ', 'ϊ',
        'ΐ', 'ϋ', 'ΰ', '�', 'Ϋ', '–'), array(
        'a', 'b', 'g', 'd', 'e', 'z', 'i', 'th', 'i', 'k', 'l', 'm', 'n', 'x', 'o', 'p',
        'r', 's', 't', 'y', 'f', 'x', 'ps', 'o', 'A', 'B', 'G', 'D', 'E', 'Z', 'H', 'Th',
        'I', 'K', 'L', 'M', 'N', 'X', 'O', 'P', 'R', 'S', 'T', 'Y', 'F', 'X', 'Ps', 'O',
        's', 'a', 'e', 'i', 'i', 'y', 'o', 'o', 'A', 'E', 'H', 'I', 'Y', 'O', 'O', 'i',
        'i', 'y', 'y', 'I', 'Y', '-'), $string);
}

// Convert to uppercase and remove accent marks
// Limited coverage for now
function remove_accents($string) {
    return strtr(mb_strtoupper($string, 'UTF-8'), array('Ά' => 'Α', 'Έ' => 'Ε', 'Ί' => 'Ι', 'Ή' => 'Η', 'Ύ' => 'Υ',
        'Ό' => 'Ο', 'Ώ' => 'Ω', '�' => 'Ι', 'Ϋ' => 'Υ',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
        'Ç' => 'C', 'Ñ' => 'N', 'Ý' => 'Y',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U'));
}

// resize an image ($source_file) of type $type to a new size ($maxheight and $maxwidth) and copies it to path $target_file
function copy_resized_image($source_file, $type, $maxwidth, $maxheight, $target_file) {
    if ($type == 'image/jpeg') {
        $image = @imagecreatefromjpeg($source_file);
    } elseif ($type == 'image/png') {
        $image = @imagecreatefrompng($source_file);
    } elseif ($type == 'image/gif') {
        $image = @imagecreatefromgif($source_file);
    } elseif ($type == 'image/bmp') {
        $image = @imagecreatefromwbmp($source_file);
    }
    if (!isset($image) or !$image) {
        return false;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    if ($width > $maxwidth or $height > $maxheight) {
        $xscale = $maxwidth / $width;
        $yscale = $maxheight / $height;
        if ($yscale < $xscale) {
            $newwidth = round($width * $yscale);
            $newheight = round($height * $yscale);
        } else {
            $newwidth = round($width * $xscale);
            $newheight = round($height * $xscale);
        }
        $resized = imagecreatetruecolor($newwidth, $newheight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        return imagejpeg($resized, $target_file);
    } elseif ($type != 'image/jpeg') {
        return imagejpeg($image, $target_file);
    } else {
        return copy($source_file, $target_file);
    }
}

// Produce HTML source for an icon
function icon($name, $title = null, $link = null, $link_attrs = '', $with_title = false) {
    global $themeimg;

    if (isset($title)) {
        $title = q($title);
        $extra = "title='$title'";
    } else {
        $extra = '';
    }

    $img = (isset($title) && $with_title) ? "<i class='fa $name' $extra></i> $title" : "<i class='fa $name' $extra></i>";
    if (isset($link)) {
        return "<a href='$link'$link_attrs>$img</a>";
    } else {
        return $img;
    }
}

function icon_old_style($name, $title = null, $link = null, $attrs = null, $format = 'png', $link_attrs = '') {
    global $themeimg;

    if (isset($title)) {
        $title = q($title);
        $extra = "alt='$title' title='$title'";
    } else {
        $extra = "alt=''";
    }

    if (isset($attrs)) {
        $extra .= ' ' . $attrs;
    }

    $img = "<img src='$themeimg/$name.$format' $extra>";
    if (isset($link)) {
        return "<a href='$link'$link_attrs>$img</a>";
    } else {
        return $img;
    }
}

/**
 * Link for displaying user profile
 * @param type $uid
 * @param type $size
 * @param type $class
 * @return type
 */
function profile_image($uid, $size, $class=null) {
    global $urlServer, $themeimg;
    
    // makes $class argument optional
    $class_attr = ($class == null)?'':"class='".q($class)."'";
    
    if ($uid > 0 and file_exists("courses/userimg/${uid}_$size.jpg")) {
        return "<img src='${urlServer}courses/userimg/${uid}_$size.jpg' $class_attr title='" . q(uid_to_name($uid)) . "'>";
    } else {
        $name = ($uid > 0) ? q(uid_to_name($uid)) : '';
        return "<img src='$themeimg/default_$size.jpg' $class_attr title='$name' alt='$name'>";
    }
}

function canonicalize_url($url) {
    if (!preg_match('/^[a-zA-Z0-9_-]+:/', $url)) {
        return 'http://' . $url;
    } else {
        return $url;
    }
}

function is_url_accepted($url,$protocols=""){
    if ($url === 'http://' || empty($url) || !filter_var($url, FILTER_VALIDATE_URL) || preg_match('/^javascript/i', preg_replace('/\s+/', '', $url)) || ($protocols!=="" && !preg_match('/^'.$protocols.'/i', preg_replace('/\s+/', '', $url)))) {
        return 0;
    }
    else{
        return 1;
    }
}

function stop_output_buffering() {
    while (@ob_end_flush());
}

// Seed mt_rand
function make_seed() {
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000);
}

// Generate a $len length random base64 encoded alphanumeric string
// try first /dev/urandom but if not available generate pseudo-random string
function generate_secret_key($len) {
    if (($key = read_urandom($len)) == NULL) {
        // poor man's choice
        $key = poor_rand_string($len);
    }
    return base64_encode($key);
}

// Generate a $len length pseudo random base64 encoded alphanumeric string from ASCII table
function poor_rand_string($len) {
    mt_srand(make_seed());

    $c = "";
    for ($i = 0; $i < $len; $i++) {
        $c .= chr(mt_rand(0, 127));
    }

    return $c;
}

// Read $len length random string from /dev/urandom if it's available
function read_urandom($len) {
    if (@is_readable('/dev/urandom')) {
        $f = fopen('/dev/urandom', 'r');
        $urandom = fread($f, $len);
        fclose($f);
        return $urandom;
    } else {
        return NULL;
    }
}

/**
 * @brief Get user admin rights from table `admin`
 * @param type $user_id
 * @return type
 */
function get_admin_rights($user_id) {
    
    $r = Database::get()->querySingle("SELECT privilege FROM admin WHERE user_id = ?d", $user_id);    
    if ($r) {
        return $r->privilege;
    } else {
        return -1;
    }
}

/**
 * @brief query course status
 * @param type $course_id
 * @return course status
 */
function course_status($course_id) {
    
    $status = Database::get()->querySingle("SELECT visible FROM course WHERE id = ?d", $course_id)->visible;
      
    return $status;
}

/**
 * @brief get user email verification status
 * @param type $uid
 * @return verified mail or no
 */
function get_mail_ver_status($uid) {
    
    $q = Database::get()->querySingle("SELECT verified_mail FROM user WHERE id = ?d", $uid)->verified_mail;
    
    return $q;
}

// check if username match for both case sensitive/insensitive
function check_username_sensitivity($posted, $dbuser) {
    if (get_config('case_insensitive_usernames')) {
        if (mb_strtolower($posted) == mb_strtolower($dbuser)) {
            return true;
        } else {
            return false;
        }
    } else {
        if ($posted == $dbuser) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

/**
 * @brief checks if user is notified via email from a given course 
 * @param type $user_id
 * @param type $course_id
 * @return boolean
 */
function get_user_email_notification($user_id, $course_id = null) {

    // checks if a course is active or not
    if (isset($course_id)) {
        if (course_status($course_id) == COURSE_INACTIVE) {
            return false;
        }
    }
    // checks if user has verified his email address
    if (get_config('email_verification_required') && get_config('dont_mail_unverified_mails')) {
        $verified_mail = get_mail_ver_status($user_id);
        if ($verified_mail == EMAIL_VERIFICATION_REQUIRED or $verified_mail == EMAIL_UNVERIFIED) {
            return false;
        }
    }
    // checks if user has choosen not to be notified by email from all courses
    if (!get_user_email_notification_from_courses($user_id)) {
        return false;
    }
    if (isset($course_id)) {
        // finally checks if user has choosen not to be notified from a specific course
        $r = Database::get()->querySingle("SELECT receive_mail FROM course_user
                                            WHERE user_id = ?d
                                            AND course_id = ?d", $user_id, $course_id);
        if ($r) {
            $row = $r->receive_mail;
            return $row;
        } else {
            return false;
        }
    }
    return true;
}

/**
 * @brief checks if user is notified via email from courses
 * @param type $user_id
 * @return boolean
 */
function get_user_email_notification_from_courses($user_id) {    
    $result = Database::get()->querySingle("SELECT receive_mail FROM user WHERE id = ?d", $user_id);
    if ($result && $result->receive_mail)
        return true;
    return false;
}


// Return a list of all subdirectories of $base which contain a file named $filename
function active_subdirs($base, $filename) {
    $dir = opendir($base);
    $out = array();
    while (($f = readdir($dir)) !== false) {
        if (is_dir($base . '/' . $f) and
                $f != '.' and $f != '..' and
                file_exists($base . '/' . $f . '/' . $filename)) {
            $out[] = $f;
        }
    }
    closedir($dir);
    return $out;
}

/*
 * Delete a directory and its whole content
 *
 * @author - Hugues Peeters
 * @param  - $dirPath (String) - the path of the directory to delete
 * @return - boolean - true if the delete succeed, false otherwise.
 */

function removeDir($dirPath) {
    /* Try to remove the directory. If it can not manage to remove it,
     * it's probable the directory contains some files or other directories,
     * and that we must first delete them to remove the original directory.
     */
    if (@rmdir($dirPath)) {
        return true;
    } else { // if directory couldn't be removed...
        $ok = true;
        $cwd = getcwd();
        chdir($dirPath);
        $handle = opendir($dirPath);

        while ($element = readdir($handle)) {
            if ($element == '.' or $element == '..') {
                continue; // skip current and parent directories
            } elseif (is_file($element)) {
                $ok = @unlink($element) && $ok;
            } elseif (is_dir($element)) {
                $dirToRemove[] = $dirPath . '/' . $element;
            }
        }

        closedir($handle);
        chdir($cwd);

        if (isset($dirToRemove) and count($dirToRemove)) {
            foreach ($dirToRemove as $j) {
                $ok = removeDir($j) && $ok;
            }
        }

        return @rmdir($dirPath) && $ok;
    }
}

/**
 * @brief update attendance about user activities
 * @param type $id
 * @param type $activity
 * @return type
 */
function update_attendance_book($id, $activity) {
    
    global $uid;
    
    if ($activity == 'assignment') {
        $type = 1;
    } elseif ($activity == 'exercise') {
        $type = 2;
    }
    $q = Database::get()->querySingle("SELECT id, attendance_id FROM attendance_activities WHERE module_auto_type = ?d
                            AND module_auto_id = ?d 
                            AND auto = 1", $type, $id);
    if ($q) {
        $u = Database::get()->querySingle("SELECT id FROM attendance_users WHERE uid = ?d
                                AND attendance_id = ?d", $uid, $q->attendance_id);
        if($u){
            Database::get()->query("INSERT INTO attendance_book SET attendance_activity_id = $q->id, uid = ?d, attend = 1", $uid);
        }
    }
    return;
}

/**
 * @brief update gradebook about user grade
 * @param type $uid
 * @param type $id
 * @param type $grade
 * @param type $activity
 */
function update_gradebook_book($uid, $id, $grade, $activity) 
{
    global $course_id;
    
    if ($activity == 'assignment') {
        $type = 1;
    } elseif ($activity == 'exercise') {
        $type = 2;
    }

    $q = Database::get()->querySingle("SELECT id, gradebook_id FROM gradebook_activities WHERE module_auto_type = ?d
                            AND module_auto_id = ?d
                            AND auto = 1", $type, $id);
    if ($q) {
        
        $u = Database::get()->querySingle("SELECT id FROM gradebook_users WHERE uid = ?d
                                AND gradebook_id = ?d", $uid, $q->gradebook_id);
        if($u){
            if ($type == 2) { // exercises
                $sql = Database::get()->querySingle("SELECT MAX(total_score) AS total_score, total_weighting FROM exercise_user_record 
                                                        WHERE uid = ?d AND eid = ?d", $uid, $id); 
                if ($sql) {
                   $range = Database::get()->querySingle("SELECT `range` FROM gradebook WHERE id = $q->gradebook_id AND course_id = ?d", $course_id)->range;
                   $score = $sql->total_score;
                   $scoreMax = $sql->total_weighting;
                    if($scoreMax) {
                       $grade = round(($range * $score) / $scoreMax, 2);    
                    } else {
                        $grade = $score;
                    }
                }
            }

            $q2 = Database::get()->querySingle("SELECT grade FROM gradebook_book WHERE gradebook_activity_id = $q->id AND uid = ?d", $uid);        
            if ($q2) { // update grade if exists            
                Database::get()->query("UPDATE gradebook_book SET grade = ?d WHERE gradebook_activity_id = $q->id AND uid = ?d", $grade, $uid);
            } else {
                if ($grade == '') {
                    $grade = 0;
                }
                Database::get()->query("INSERT INTO gradebook_book SET gradebook_activity_id = $q->id, uid = ?d, grade = ?d", $uid, $grade);
            }
        }
    }
    return;    
}

/**
 * Generate a token verifying some info
 *
 * @param  string  $info           - The info that will be verified by the token
 * @param  boolean $need_timestamp - Whether the token will include a timestamp
 * @return string  $ret            - The new token
 */
function token_generate($info, $need_timestamp = false) {
    if ($need_timestamp) {
        $ts = sprintf('%x-', time());
    } else {
        $ts = '';
    }
    $code_key = get_config('code_key');
    return $ts . hash_hmac('ripemd160', $ts . $info, $code_key);
}

/**
 * Validate a token verifying some info
 *
 * @param  string  $info           - The info that will be verified by the token
 * @param  string  $token          - The token to verify
 * @param  int     $ts_valid_time  - Period of validity of token in seconds, if token includes a timestamp
 * @return boolean $ret            - True if the token is valid, false otherwise
 */
function token_validate($info, $token, $ts_valid_time = 0) {
    $data = explode('-', $token);
    if (count($data) > 1) {
        $timediff = time() - hexdec($data[0]);
        if ($timediff > $ts_valid_time) {
            return false;
        }
        $token = $data[1];
        $ts = $data[0] . '-';
    } else {
        $ts = '';
    }
    $code_key = get_config('code_key');
    return $token == hash_hmac('ripemd160', $ts . $info, $code_key);
}

/**
 * This is a class for cutting a string to be no more than $maxlen characters long, respecting the html tags
 * Based on code provided by prajwala 
 * http://code.google.com/p/cut-html-string/	
 */
class HtmlCutString {

    function __construct($string, $limit, $postfix) {
        // create dom element using the html string
        $this->tempDiv = new DomDocument;
        $this->tempDiv->loadXML('<div>' . $string . '</div>', LIBXML_NONET|LIBXML_DTDLOAD|LIBXML_DTDATTR);
        // keep the characters count till now
        $this->charCount = 0;
        // put the postfix at the end
        $this->postfix = FALSE;
        $this->postfix_text = $postfix;
        $this->encoding = 'UTF-8';
        // character limit need to check
        $this->limit = $limit;
    }

    function cut() {
        // create empty document to store new html
        $this->newDiv = new DomDocument;
        // cut the string by parsing through each element
        $this->searchEnd($this->tempDiv->documentElement, $this->newDiv);
        $newhtml = $this->newDiv->saveHTML();
        if ($this->postfix)
            return $newhtml . $this->postfix_text;
        else
            return $newhtml;
    }

    function deleteChildren($node) {
        while (isset($node->firstChild)) {
            $this->deleteChildren($node->firstChild);
            $node->removeChild($node->firstChild);
        }
    }

    function searchEnd($parseDiv, $newParent) {
        foreach ($parseDiv->childNodes as $ele) {
            // not text node
            if ($ele->nodeType != 3) {
                $newEle = $this->newDiv->importNode($ele, true);
                if (count($ele->childNodes) === 0) {
                    $newParent->appendChild($newEle);
                    continue;
                }
                $this->deleteChildren($newEle);
                $newParent->appendChild($newEle);
                $res = $this->searchEnd($ele, $newEle);
                if ($res)
                    return $res;
                else {
                    continue;
                }
            }

            // the limit of the char count reached
            if (mb_strlen($ele->nodeValue, $this->encoding) + $this->charCount >= $this->limit) {
                $newEle = $this->newDiv->importNode($ele);
                $newEle->nodeValue = mb_substr($newEle->nodeValue, 0, $this->limit - $this->charCount, $this->encoding);
                $newParent->appendChild($newEle);
                $this->postfix = TRUE;
                return true;
            }
            $newEle = $this->newDiv->importNode($ele);
            $newParent->appendChild($newEle);
            $this->charCount += mb_strlen($newEle->nodeValue, $this->encoding);
        }
        return false;
    }

}

/**
 * @brief count online users (depending on sessions)
 * @return int
 */
function getOnlineUsers() {

    $count = 0;
    if ($directory_handle = @opendir(session_save_path())) {
        while (false !== ($file = readdir($directory_handle))) {
            if ($file != '.' and $file != '..') {
                if (time() - fileatime(session_save_path() . '/' . $file) < MAX_IDLE_TIME * 60) {
                    $count++;
                }
            }
        }
    }
    @closedir($directory_handle);
    return $count;
}

/**
 * Initialize copyright/license global arrays
 */
function copyright_info($cid, $noImg=1) {

    global $language, $license, $themeimg;

    $lang = langname_to_code($language);
    
    $lic = Database::get()->querySingle("SELECT course_license FROM course WHERE id = ?d", $cid)->course_license;
    if (($lic == 0) or ($lic >= 10)) {
        $link_suffix = '';
    } else {
        if ($language != 'en') {
            $link_suffix = 'deed.' . $lang;
        } else {
            $link_suffix = '';
        }
    }
    if($noImg==1){
    $link = "<a href='" . $license[$lic]['link'] . "$link_suffix'>
            <img src='$themeimg/" . $license[$lic]['image'] . ".png' title='" . $license[$lic]['title'] . "' alt='" . $license[$lic]['title'] . "' /></a><br>";
    }else if($noImg==0){
        $link = "";
    }

    return $link . q($license[$lic]['title']);
}

/**
 * Drop in replacement for rand() or mt_rand().
 * 
 * @param int $min [optional]
 * @param int $max [optional]
 * @return int
 */
function crypto_rand_secure($min = null, $max = null) {
    require_once('lib/srand.php');
    // default values for optional min/max
    if ($min === null)
        $min = 0;
    if ($max === null)
        $max = getrandmax();
    else
        $max += 1; // for being inclusive

    $range = $max - $min;
    if ($range <= 0)
        return $min; // not so random...
    $log = log($range, 2);
    $bytes = (int) ($log / 8) + 1; // length in bytes
    $bits = (int) $log + 1; // length in bits
    $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
    do {
        $rnd = hexdec(bin2hex(secure_random_bytes($bytes)));
        $rnd = $rnd & $filter; // discard irrelevant bits
    } while ($rnd >= $range);
    return $min + $rnd;
}

/**
 * @brief returns HTTP 403 status code 
 * @param type $path
 */
function forbidden($path) {
    header("HTTP/1.0 403 Forbidden");
    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head>',
    '<title>403 Forbidden</title></head><body>',
    '<h1>Forbidden</h1><p>You don\'t have permission to acces the requested path "',
    htmlspecialchars($path),
    '".</p></body></html>';
    exit;
}


/**
 * @brief returns HTML for an action bar
 * @param array $options options for each entry in bar
 * 
 * Each item in array is another array of the form:
 * array('title' => 'Create', 'url' => '/create.php', 'icon' => 'create', 'level' => 'primary')
 * level is optional and can be 'primary' for primary entries or unset
 */
function action_bar($options) {
    global $langConfirmDelete, $langCancel, $langDelete, $pageName;
    
    $out_primary = $out_secondary = array();
    $i=0;
    $page_title = "";
    if (isset($pageName)) {
        $page_title = "<div class='pull-left' style='padding-top:31px;'><h4>".q($pageName)."</h4></div>";
    }    
    foreach (array_reverse($options) as $option) {
        // skip items with show=false
        if (isset($option['show']) and !$option['show']) {
            continue;
        }
        if (isset($option['class'])) {
            $class = " class='$option[class]'";
        } else {
            $class = '';
        }
        $title = q($option['title']);
        $level = isset($option['level'])? $option['level']: 'secondary';
        if (isset($option['confirm'])) {
            $title_conf = isset($option['confirm_title']) ? $option['confirm_title'] : $langConfirmDelete;
            $accept_conf = isset($option['confirm_button']) ? $option['confirm_button'] : $langDelete;
            $confirm_extra = " data-title='$title_conf' data-message='" .
                q($option['confirm']) . "' data-cancel-txt='$langCancel' data-action-txt='$accept_conf' data-action-class='btn-danger'";
            $confirm_modal_class = ' confirmAction';
            $form_begin = "<form method=post action='$option[url]' style='display:inline-block;'>";
            $form_end = '</form>';
            $href = '';
        } else {
            $confirm_extra = $confirm_modal_class = $form_begin = $form_end = '';
            $href = " href='" . $option['url'] . "'";
        }
        if (!isset($option['button-class'])) {
            $button_class = 'btn-default';
        } else {
            $button_class = $option['button-class'];
        }
        if (isset($option['link-attrs'])) {
            $link_attrs = " ".$option['link-attrs'];
        } else {
            $link_attrs = "";
        }        
        if ($level == 'primary-label') {
            array_unshift($out_primary,
                "$form_begin<a$confirm_extra class='btn $button_class$confirm_modal_class'" . $href .
                " data-placement='bottom' data-toggle='tooltip' rel='tooltip'" .
                " title='$title'$link_attrs>" .
                "<i class='fa $option[icon] space-after-icon'></i>" .
                "<span class='hidden-xs'>$title</span></a>$form_end");
        } elseif ($level == 'primary') {
            array_unshift($out_primary,
                "$form_begin<a$confirm_extra class='btn $button_class$confirm_modal_class'" . $href .
                " data-placement='bottom' data-toggle='tooltip' rel='tooltip'" .
                " title='$title'$link_attrs>" .
                "<i class='fa $option[icon]'></i></a>$form_end");
        } else {
            array_unshift($out_secondary,
                "<li$class>$form_begin<a$confirm_extra  class='$confirm_modal_class'" . $href .
                " title='$title'$link_attrs>" .
                "<i class='fa $option[icon]'></i> $title</a>$form_end</li>");
        }
        $i++;
    }
    $out = '';                
    if (count($out_primary)) {
        $out .= implode('', $out_primary);
    }

    $action_button = "";
    if (count($out_secondary)) {
        //$action_list = q("<div class='list-group'>".implode('', $out_secondary)."</div>");
        $action_button .= "<button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown' aria-expanded='false'><i class='fa fa-gears'></i> <span class='caret'></span></button>";
        $action_button .= "  <ul class='dropdown-menu dropdown-menu-right' role='menu'>
                     ".implode('', $out_secondary)."
                  </ul>";
    }
    if ($out && $i!=0) {
        return "<div class='row'>
                    <div class='col-sm-12 clearfix'>
                        $page_title
                        <div class='margin-top-thin margin-bottom-fat pull-right'>
                            <div class='btn-group'>
                            $out
                            $action_button
                            </div>                         
                        </div>
                    </div>
                </div>";
    } else {
        return '';
    }
}

/**
 * @brief returns HTML for an action button
 * @param array $options options for each entry in the button
 * 
 * Each item in array is another array of the form:
 * array('title' => 'Create', 'url' => '/create.php', 'icon' => 'create', 'class' => 'primary danger')
 * 
 */
function action_button($options) {
    global $langConfirmDelete, $langCancel, $langDelete;
    $out_primary = $out_secondary = array();
    foreach (array_reverse($options) as $option) {
        $level = isset($option['level'])? $option['level']: 'secondary';
        // skip items with show=false
        if (isset($option['show']) and !$option['show']) {
            continue;
        }
        if (isset($option['class'])) {
            $class = ' ' . $option['class'];
        } else {
            $class = '';
        }
        if (isset($option['btn_class'])) {
            $btn_class = ' '.$option['btn_class'];
        } else {
            $btn_class = ' btn-default';
        }
        $disabled = isset($option['disabled']) && $option['disabled'] ? ' disabled' : '';
        $icon_class = "class='list-group-item $class";
        if (isset($option['icon-class'])) {
            $icon_class .= " " . $option['icon-class'];
        }
        if (isset($option['confirm'])) {
            $title = isset($option['confirm_title']) ? $option['confirm_title'] : $langConfirmDelete;
            $accept = isset($option['confirm_button']) ? $option['confirm_button'] : $langDelete;
            $icon_class .= " confirmAction' data-title='$title' data-message='" .
                q($option['confirm']) . "' data-cancel-txt='$langCancel' data-action-txt='$accept' data-action-class='btn-danger'";
            $form_begin = "<form method=post action='$option[url]'>";
            $form_end = '</form>';
            $url = '#';
        } else {
            $icon_class .= "'";
            $confirm_extra = $form_begin = $form_end = '';
            $url = isset($option['url'])? $option['url']: '#';
        }       
        if (isset($option['icon-extra'])) {
            $icon_class .= ' ' . $option['icon-extra'];
        }        
        
        if ($level == 'primary-label') {
            array_unshift($out_primary, "<a href='$url' class='btn $btn_class$disabled'><i class='fa $option[icon] space-after-icon'></i>$option[title]</a>");
        } elseif ($level == 'primary') {
            array_unshift($out_primary, "<a href='$url' class='btn $btn_class$disabled'><i class='fa $option[icon]'></i></a>");
        } else {
            array_unshift($out_secondary, $form_begin . icon($option['icon'], $option['title'], $url, $icon_class, true) . $form_end);
        }        
    }
    $primary_buttons = "";
    if (count($out_primary)) {
        $primary_buttons = implode('', $out_primary);
    }       
    $action_button = "";
    if (count($out_secondary)) {
        $action_list = q("<div class='list-group'>".implode('', $out_secondary)."</div>");
        $action_button = "
                <a tabindex='1' class='btn btn-default' data-container='body' data-toggle='popover' data-trigger='manual' data-html='true' data-placement='bottom' data-content='$action_list'>
                    <i class='fa fa-gear'></i>  <span class='caret'></span>
                </a>";
    }    
    
    return "<div class='btn-group btn-group-sm' role='group' aria-label='...'>
                $primary_buttons
                $action_button
          </div>";
}
/**
 * Removes spcific get variable from Query String
 *
 */
function removeGetVar($url, $varname) {
    list($urlpart, $qspart) = array_pad(explode('?', $url), 2, '');
    parse_str($qspart, $qsvars);
    unset($qsvars[$varname]);
    $newqs = http_build_query($qsvars);
    return $urlpart . '?' . $newqs;
}
