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

define('UPGRADE', true);

require '../include/baseTheme.php';
require_once 'include/lib/fileUploadLib.inc.php';
require_once 'include/lib/forcedownload.php';
require_once 'include/phpass/PasswordHash.php';
require_once 'upgradeHelper.php';

stop_output_buffering();

// set default storage engine
Database::get()->query("SET storage_engine = InnoDB");

require_once 'upgrade/functions.php';

set_time_limit(0);

if (php_sapi_name() == 'cli' and ! isset($_SERVER['REMOTE_ADDR'])) {
    $command_line = true;
} else {
    $command_line = false;
}

load_global_messages();

if ($urlAppend[strlen($urlAppend) - 1] != '/') {
    $urlAppend .= '/';
}

// include_messages
require "lang/$language/common.inc.php";
$extra_messages = "config/{$language_codes[$language]}.inc.php";
if (file_exists($extra_messages)) {
    include $extra_messages;
} else {
    $extra_messages = false;
}
require "lang/$language/messages.inc.php";
if ($extra_messages) {
    include $extra_messages;
}

$pageName = $langUpgrade;

$auth_methods = array('imap', 'pop3', 'ldap', 'db');
$OK = "[<font color='green'> $langSuccessOk </font>]";
$BAD = "[<font color='red'> $langSuccessBad </font>]";

$charset_spec = 'DEFAULT CHARACTER SET=utf8';

// Coming from the admin tool or stand-alone upgrade?
$fromadmin = !isset($_POST['submit_upgrade']);

if (!isset($_POST['submit2']) and ! $command_line) {
    if (!is_admin($_POST['login'], $_POST['password'])) {
        $tool_content .= "<div class='alert alert-warning'>$langUpgAdminError</div>
            <center><a href='index.php'>$langBack</a></center>";
        draw($tool_content, 0);
        exit;
    }
}

if (!DBHelper::tableExists('config')) {
    $tool_content .= "<div class='alert alert-warning'>$langUpgTooOld</div>";
    draw($tool_content, 0);
    exit;
}

// Upgrade user table first if needed
if (!DBHelper::fieldExists('user', 'id')) {
    // check for mulitple usernames
    fix_multiple_usernames();

    Database::get()->query("ALTER TABLE user
                        CHANGE registered_at ts_registered_at int(10) NOT NULL DEFAULT 0,
                        CHANGE expires_at ts_expires_at INT(10) NOT NULL DEFAULT 0,
                        ADD registered_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                        ADD expires_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
    Database::get()->query("UPDATE user
                        SET registered_at = FROM_UNIXTIME(ts_registered_at),
                            expires_at = FROM_UNIXTIME(ts_expires_at)");
    Database::get()->query("ALTER TABLE assignment
                        ADD auto_judge TINYINT(1),
                        ADD auto_judge_scenarios VARCHAR(2048),
                        ADD lang VARCHAR(10)");
    Database::get()->query("ALTER TABLE user
                        CHANGE user_id id INT(11) NOT NULL AUTO_INCREMENT,
                        CHANGE nom surname VARCHAR(100) NOT NULL DEFAULT '',
                        CHANGE prenom givenname VARCHAR(100) NOT NULL DEFAULT '',
                        CHANGE username username VARCHAR(100) NOT NULL UNIQUE KEY COLLATE utf8_bin,
                        CHANGE password password VARCHAR(60) NOT NULL DEFAULT 'empty',
                        CHANGE email email VARCHAR(100) NOT NULL DEFAULT '',
                        CHANGE statut status TINYINT(4) NOT NULL DEFAULT " . USER_STUDENT . ",
                        CHANGE phone phone VARCHAR(20) DEFAULT '',
                        CHANGE am am VARCHAR(20) DEFAULT '',
                        DROP ts_registered_at,
                        DROP ts_expires_at,
                        DROP perso,
                        CHANGE description description TEXT,
                        CHANGE whitelist whitelist TEXT,
                        DROP forum_flag,
                        DROP announce_flag,
                        DROP doc_flag,
                        DROP KEY user_username");
    Database::get()->query("ALTER TABLE admin
                        CHANGE idUser user_id INT(11) NOT NULL PRIMARY KEY");
}

// Make sure 'video' subdirectory exists and is writable
$videoDir = $webDir . '/video';
if (!file_exists($videoDir)) {
    if (!mkdir($videoDir)) {
        die($langUpgNoVideoDir);
    }
} elseif (!is_dir($videoDir)) {
    die($langUpgNoVideoDir2);
} elseif (!is_writable($videoDir)) {
    die($langUpgNoVideoDir3);
}

mkdir_or_error('courses/temp');
touch_or_error('courses/temp/index.php');
mkdir_or_error('courses/userimg');
touch_or_error('courses/userimg/index.php');
touch_or_error($webDir . '/video/index.php');

// ********************************************
// upgrade config.php
// *******************************************

$default_student_upload_whitelist = 'pdf, ps, eps, tex, latex, dvi, texinfo, texi, zip, rar, tar, bz2, gz, 7z, xz, lha, lzh, z, Z, doc, docx, odt, ott, sxw, stw, fodt, txt, rtf, dot, mcw, wps, xls, xlsx, xlt, ods, ots, sxc, stc, fods, uos, csv, ppt, pps, pot, pptx, ppsx, odp, otp, sxi, sti, fodp, uop, potm, odg, otg, sxd, std, fodg, odb, mdb, ttf, otf, jpg, jpeg, png, gif, bmp, tif, tiff, psd, dia, svg, ppm, xbm, xpm, ico, avi, asf, asx, wm, wmv, wma, dv, mov, moov, movie, mp4, mpg, mpeg, 3gp, 3g2, m2v, aac, m4a, flv, f4v, m4v, mp3, swf, webm, ogv, ogg, mid, midi, aif, rm, rpm, ram, wav, mp2, m3u, qt, vsd, vss, vst';
$default_teacher_upload_whitelist = 'html, js, css, xml, xsl, cpp, c, java, m, h, tcl, py, sgml, sgm, ini, ds_store';

if (!isset($_POST['submit2']) and isset($_SESSION['is_admin']) and ( $_SESSION['is_admin'] == true) and ! $command_line) {
    if (ini_get('register_globals')) { // check if register globals is Off
        $tool_content .= "<div class='alert alert-danger'>$langWarningInstall1</div>";
    }
    if (ini_get('short_open_tag')) { // check if short_open_tag is Off
        $tool_content .= "<div class='alert alert-danger'>$langWarningInstall2</div>";
    }
    $tool_content .= "<div class='alert alert-info'>$langConfigFound<br>$langConfigMod</div>";
    // get old contact values
    $tool_content .= "<div class='form-wrapper'>
            <form class='form-horizontal' role='form' action='$_SERVER[SCRIPT_NAME]' method='post'>
            <fieldset>
            <div class='form-group'><label class='col-sm-offset-4 col-sm-8'>$langUpgContact</label></div>
            <div class='form-group'>
                <label class='col-sm-2 control-label'>$langInstituteShortName:</label>
                <div class='col-sm-10'>
                    <input class=auth_input_admin type='text' size='40' name='Institution' value='" . @$Institution . "'>
                </div>
            </div>
            <div class='form-group'>
                <label class='col-sm-2 control-label'>$langUpgAddress</label>
                <div class='col-sm-10'>
                    <textarea rows='3' cols='40' class=auth_input_admin name='postaddress'>" . @$postaddress . "</textarea>
                </div>
            </div>
            <div class='form-group'>
                <label class='col-sm-2 control-label'>$langUpgTel</label>
                <div class='col-sm-10'>
                    <input class=auth_input_admin type='text' name='telephone' value='" . @$telephone . "'>
                </div>
            </div>
            <div class='form-group'>
                <label class='col-sm-2 control-label'>Fax:</label>
                <div class='col-sm-10'>
                    <input class=auth_input_admin type='text' name='fax' value='" . @$fax . "'>
                </div>
            </div>
            </fieldset>
            <p class='pull-right'><input class='btn btn-primary' name='submit2' value='$langCont &raquo;' type='submit'></p>
            </form>
            </div>";
    draw($tool_content, 0);
} else {
    // Main part of upgrade starts here
    if ($command_line) {
        $_POST['Institution'] = @$Institution;
        $_POST['postaddress'] = @$postaddress;
        $_POST['telephone'] = @$telephone;
        $_POST['fax'] = @$fax;
    }

    $tool_content .= getInfoAreas();
    draw($tool_content, 0);
    updateInfo(0.01, $langUpgradeStart . " : " . $langUpgradeConfig);
    Debug::setOutput(function ($message, $level) use (&$debug_output, &$debug_error) {
        $debug_output .= $message;
        if ($level > Debug::WARNING)
            $debug_error = true;
    });
    Debug::setLevel(Debug::WARNING);

    if (isset($telephone)) {
        // Upgrade to 3.x-style config
        if (!copy('config/config.php', 'config/config_backup.php')) {
            die($langConfigError1);
        }

        if (!isset($durationAccount)) {
            $durationAccount = 4 * 30 * 24 * 60 * 60; // 4 years
        }

        set_config('site_name', $siteName);
        set_config('account_duration', $durationAccount);
        set_config('institution', $_POST['Institution']);
        set_config('institution_url', $InstitutionUrl);
        set_config('phone', $_POST['telephone']);
        set_config('postaddress', $_POST['postaddress']);
        set_config('fax', $_POST['fax']);
        set_config('email_sender', $emailAdministrator);
        set_config('admin_name', $administratorName . ' ' . $administratorSurname);
        set_config('email_helpdesk', $emailhelpdesk);
        if (isset($emailAnnounce) and $emailAnnounce) {
            set_config('email_announce', $emailAnnounce);
        }
        set_config('base_url', $urlServer);
        set_config('default_language', $language);
        set_config('active_ui_languages', implode(' ', $active_ui_languages));
        if ($urlSecure != $urlServer) {
            set_config('secure_url', $urlSecure);
        }
        set_config('phpMyAdminURL', $phpMyAdminURL);
        set_config('phpSysInfoURL', $phpSysInfoURL);

        $new_conf = '<?php
/* ========================================================
 * Open eClass 3.0 configuration file
 * Created by upgrade on ' . date('Y-m-d H:i') . '
 * ======================================================== */

$mysqlServer = ' . quote($mysqlServer) . ';
$mysqlUser = ' . quote($mysqlUser) . ';
$mysqlPassword = ' . quote($mysqlPassword) . ';
$mysqlMainDb = ' . quote($mysqlMainDb) . ';
';
        $fp = @fopen('config/config.php', 'w');
        if (!$fp) {
            die($langConfigError3);
        }
        fwrite($fp, $new_conf);
        fclose($fp);
    }
    // ****************************************************
    // 		upgrade eclass main database
    // ****************************************************

    updateInfo(-1, $langUpgradeBase . " " . $mysqlMainDb);

    // Create or upgrade config table
    if (DBHelper::fieldExists('config', 'id')) {
        Database::get()->query("RENAME TABLE config TO old_config");
        Database::get()->query("CREATE TABLE `config` (
                         `key` VARCHAR(32) NOT NULL,
                         `value` VARCHAR(255) NOT NULL,
                         PRIMARY KEY (`key`)) $charset_spec");
        Database::get()->query("INSERT INTO config
                         SELECT `key`, `value` FROM old_config
                         GROUP BY `key`");
        Database::get()->query("DROP TABLE old_config");
    }
    $oldversion = get_config('version');
    Database::get()->query("INSERT IGNORE INTO `config` (`key`, `value`) VALUES
                    ('dont_display_login_form', '0'),
                    ('email_required', '0'),
                    ('email_from', '1'),
                    ('am_required', '0'),
                    ('dropbox_allow_student_to_student', '0'),
                    ('dropbox_allow_personal_messages', '0'),
                    ('enable_social_sharing_links', '0'),
                    ('block_username_change', '0'),
                    ('enable_mobileapi', '0'),
                    ('display_captcha', '0'),
                    ('insert_xml_metadata', '0'),
                    ('doc_quota', '200'),
                    ('dropbox_quota', '100'),
                    ('video_quota', '100'),
                    ('group_quota', '100'),
                    ('course_multidep', '0'),
                    ('user_multidep', '0'),
                    ('restrict_owndep', '0'),
                    ('restrict_teacher_owndep', '0')");

    // upgrade from versions < 2.1.3 is not possible
    if (version_compare($oldversion, '2.1.3', '<') or ( !isset($oldversion))) {
        updateInfo(1, $langUpgTooOld);
        exit;
    }
    // upgrade from version 2.x to 3.0
    if (version_compare($oldversion, '2.2', '<')) {
        // course units
        Database::get()->query("CREATE TABLE IF NOT EXISTS `course_units` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `title` VARCHAR(255) NOT NULL DEFAULT '',
                `comments` MEDIUMTEXT,
                `visibility` CHAR(1) NOT NULL DEFAULT 'v',
                `order` INT(11) NOT NULL DEFAULT 0,
                `course_id` INT(11) NOT NULL) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `unit_resources` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `unit_id` INT(11) NOT NULL ,
                `title` VARCHAR(255) NOT NULL DEFAULT '',
                `comments` MEDIUMTEXT,
                `res_id` INT(11) NOT NULL,
                `type` VARCHAR(255) NOT NULL DEFAULT '',
                `visibility` CHAR(1) NOT NULL DEFAULT 'v',
                `order` INT(11) NOT NULL DEFAULT 0,
                `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00') $charset_spec");
    }

    if (version_compare($oldversion, '2.2.1', '<')) {
        Database::get()->query("ALTER TABLE `cours` CHANGE `doc_quota` `doc_quota` FLOAT NOT NULL DEFAULT '104857600'");
        Database::get()->query("ALTER TABLE `cours` CHANGE `video_quota` `video_quota` FLOAT NOT NULL DEFAULT '104857600'");
        Database::get()->query("ALTER TABLE `cours` CHANGE `group_quota` `group_quota` FLOAT NOT NULL DEFAULT '104857600'");
        Database::get()->query("ALTER TABLE `cours` CHANGE `dropbox_quota` `dropbox_quota` FLOAT NOT NULL DEFAULT '104857600'");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `forum_notify` (
                        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        `user_id` INT NOT NULL DEFAULT '0',
                        `cat_id` INT NULL ,
                        `forum_id` INT NULL ,
                        `topic_id` INT NULL ,
                        `notify_sent` BOOL NOT NULL DEFAULT '0',
                        `course_id` INT NOT NULL DEFAULT '0') $charset_spec");

        if (!DBHelper::fieldExists('cours_user', 'course_id')) {
            Database::get()->query('ALTER TABLE cours_user ADD course_id int(11) DEFAULT 0 NOT NULL FIRST');
            Database::get()->query('UPDATE cours_user SET course_id =
                                        (SELECT course_id FROM cours WHERE code = cours_user.code_cours)
                             WHERE course_id = 0');
            Database::get()->query('ALTER TABLE cours_user DROP PRIMARY KEY, ADD PRIMARY KEY (course_id, user_id)');
            Database::get()->query('CREATE INDEX course_user_id ON cours_user (user_id, course_id)');
            Database::get()->query('ALTER TABLE cours_user DROP code_cours');
        }

        if (!DBHelper::fieldExists('annonces', 'course_id')) {
            Database::get()->query('ALTER TABLE annonces ADD course_id int(11) DEFAULT 0 NOT NULL AFTER code_cours');
            Database::get()->query('UPDATE annonces SET course_id =
                                        (SELECT course_id FROM cours WHERE code = annonces.code_cours)
                             WHERE course_id = 0');
            Database::get()->query('ALTER TABLE annonces DROP code_cours');
        }
    }
    if (version_compare($oldversion, '2.3.1', '<')) {
        if (!DBHelper::fieldExists('prof_request', 'am')) {
            Database::get()->query('ALTER TABLE `prof_request` ADD `am` VARCHAR(20) NULL AFTER profcomm');
        }
    }

    Database::get()->query("INSERT IGNORE INTO `auth` VALUES (7, 'cas', '', '', 0)");
    DBHelper::fieldExists('user', 'email_public') or
            Database::get()->query("ALTER TABLE `user`
                        ADD `email_public` TINYINT(1) NOT NULL DEFAULT 0,
                        ADD `phone_public` TINYINT(1) NOT NULL DEFAULT 0,
                        ADD `am_public` TINYINT(1) NOT NULL DEFAULT 0");

    if (version_compare($oldversion, '2.4', '<')) {
        if (DBHelper::fieldExists('cours', 'faculte')) {
            delete_field('cours', 'faculte');
            updateInfo(-1, $langDeleteField);
        }

        Database::get()->query("ALTER TABLE user CHANGE lang lang VARCHAR(16) NOT NULL DEFAULT 'el'");
        DBHelper::fieldExists('annonces', 'visibility') or
                Database::get()->query("ALTER TABLE `annonces` ADD `visibility` CHAR(1) NOT NULL DEFAULT 'v'");
        DBHelper::fieldExists('user', 'description') or
                Database::get()->query("ALTER TABLE `user` ADD description TEXT,
                                         ADD has_icon BOOL NOT NULL DEFAULT 0");
        DBHelper::fieldExists('user', 'verified_mail') or
                Database::get()->query("ALTER TABLE `user` ADD verified_mail BOOL NOT NULL DEFAULT " . EMAIL_UNVERIFIED . ",
                                         ADD receive_mail BOOL NOT NULL DEFAULT 1");
        DBHelper::fieldExists('course_user', 'receive_mail') or
                Database::get()->query("ALTER TABLE `course_user` ADD receive_mail BOOL NOT NULL DEFAULT 1");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `document` (
                        `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        `course_id` INT(11) NOT NULL,
                        `subsystem` TINYINT(4) NOT NULL,
                        `subsystem_id` INT(11) DEFAULT NULL,
                        `path` VARCHAR(255) NOT NULL,
                        `filename` VARCHAR(255) NOT NULL,
                        `visibility` CHAR(1) NOT NULL DEFAULT 'v',
                        `comment` TEXT,
                        `category` TINYINT(4) NOT NULL DEFAULT 0,
                        `title` TEXT,
                        `creator` TEXT,
                        `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                        `date_modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                        `subject` TEXT,
                        `description` TEXT,
                        `author` VARCHAR(255) NOT NULL DEFAULT '',
                        `format` VARCHAR(32) NOT NULL DEFAULT '',
                        `language` VARCHAR(16) NOT NULL DEFAULT '',
                        `copyrighted` TINYINT(4) NOT NULL DEFAULT 0,
                        FULLTEXT KEY `document`
                            (`filename`, `comment`, `title`, `creator`,
                            `subject`, `description`, `author`, `language`)) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `group_properties` (
                        `course_id` INT(11) NOT NULL PRIMARY KEY ,
                        `self_registration` TINYINT(4) NOT NULL DEFAULT 1,
                        `multiple_registration` TINYINT(4) NOT NULL DEFAULT 0,
                        `allow_unregister` TINYINT(4) NOT NULL DEFAULT 0,
                        `forum` TINYINT(4) NOT NULL DEFAULT 1,
                        `private_forum` TINYINT(4) NOT NULL DEFAULT 0,
                        `documents` TINYINT(4) NOT NULL DEFAULT 1,
                        `wiki` TINYINT(4) NOT NULL DEFAULT 0,
                        `agenda` TINYINT(4) NOT NULL DEFAULT 0) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `group` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        `course_id` INT(11) NOT NULL DEFAULT 0,
                        `name` varchar(100) NOT NULL DEFAULT '',
                        `description` TEXT,
                        `forum_id` INT(11) NULL,
                        `max_members` INT(11) NOT NULL DEFAULT 0,
                        `secret_directory` varchar(30) NOT NULL DEFAULT '0') $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `group_members` (
                        `group_id` INT(11) NOT NULL,
                        `user_id` INT(11) NOT NULL,
                        `is_tutor` INT(11) NOT NULL DEFAULT 0,
                        `description` TEXT,
                        PRIMARY KEY (`group_id`, `user_id`)) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `glossary` (
                       `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                       `term` VARCHAR(255) NOT NULL,
                       `definition` text NOT NULL,
                       `url` text,
                       `order` INT(11) NOT NULL DEFAULT 0,
                       `datestamp` DATETIME NOT NULL,
                       `course_id` INT(11) NOT NULL) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `link` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT,
                            `course_id` INT(11) NOT NULL,
                            `url` VARCHAR(255),
                            `title` VARCHAR(255),
                            `description` TEXT NOT NULL,
                            `category` INT(6) DEFAULT 0 NOT NULL,
                            `order` INT(6) DEFAULT 0 NOT NULL,
                            `hits` INT(6) DEFAULT 0 NOT NULL,
                            PRIMARY KEY (`id`, `course_id`)) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `link_category` (
                            `id` INT(6) NOT NULL AUTO_INCREMENT,
                            `course_id` INT(11) NOT NULL,
                            `name` VARCHAR(255) NOT NULL,
                            `description` TEXT,
                            `order` INT(6) NOT NULL DEFAULT 0,
                            PRIMARY KEY (`id`, `course_id`)) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS ebook (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `order` INT(11) NOT NULL,
                            `title` TEXT) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS ebook_section (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `ebook_id` INT(11) NOT NULL,
                            `public_id` VARCHAR(11) NOT NULL,
                            `file` VARCHAR(128),
                            `title` TEXT) $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS ebook_subsection (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `section_id` VARCHAR(11) NOT NULL,
                            `public_id` VARCHAR(11) NOT NULL,
                            `file_id` INT(11) NOT NULL,
                            `title` TEXT) $charset_spec");

        if (DBHelper::tableExists('prof_request')) {
            Database::get()->query("RENAME TABLE prof_request TO user_request");
            Database::get()->query("ALTER TABLE user_request
                                    CHANGE rid id INT(11) NOT NULL auto_increment,
                                    CHANGE profname name VARCHAR(100) NOT NULL DEFAULT '',
                                    CHANGE profsurname surname VARCHAR(100) NOT NULL DEFAULT '',
                                    CHANGE profuname uname VARCHAR(100) NOT NULL DEFAULT '',
                                    CHANGE profpassword password VARCHAR(255) NOT NULL DEFAULT '',
                                    CHANGE profemail email varchar(255) NOT NULL DEFAULT '',
                                    CHANGE proftmima faculty_id INT(11) NOT NULL DEFAULT 0,
                                    CHANGE profcomm phone VARCHAR(20) NOT NULL DEFAULT '',
                                    CHANGE lang lang VARCHAR(16) NOT NULL DEFAULT 'el',
                                    ADD request_ip varchar(45) NOT NULL DEFAULT ''");
        }

        // Upgrade table admin_announcements if needed
        if (DBHelper::fieldExists('admin_announcements', 'gr_body')) {
            Database::get()->query("RENAME TABLE `admin_announcements` TO `admin_announcements_old`");
            Database::get()->query("CREATE TABLE IF NOT EXISTS `admin_announcements` (
                                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                                    `title` VARCHAR(255) DEFAULT NULL,
                                    `body` TEXT,
                                    `date` DATETIME NOT NULL,
                                    `begin` DATETIME DEFAULT NULL,
                                    `end` DATETIME DEFAULT NULL,
                                    `visible` ENUM('V','I') NOT NULL,
                                    `lang` VARCHAR(10) NOT NULL DEFAULT 'el',
                                    `ordre` MEDIUMINT(11) NOT NULL DEFAULT 0,
                                    PRIMARY KEY (`id`))");

            Database::get()->query("INSERT INTO admin_announcements (title, body, `date`, visible, lang)
                                    SELECT gr_title AS title, CONCAT_WS('  ', gr_body, gr_comment) AS body, `date`, visible, 'el'
                                    FROM admin_announcements_old WHERE gr_title <> '' OR gr_body <> ''");
            Database::get()->query("INSERT INTO admin_announcements (title, body, `date`, visible, lang)
                                     SELECT en_title AS title, CONCAT_WS('  ', en_body, en_comment) AS body, `date`, visible, 'en'
                                     FROM admin_announcements_old WHERE en_title <> '' OR en_body <> ''");
            Database::get()->query("DROP TABLE admin_announcements_old");
        }
        DBHelper::fieldExists('admin_announcements', 'ordre') or
                Database::get()->query("ALTER TABLE `admin_announcements` ADD `ordre` MEDIUMINT(11) NOT NULL DEFAULT 0 AFTER `lang`");
        // not needed anymore
        if (DBHelper::tableExists('cours_faculte')) {
            Database::get()->query("DROP TABLE cours_faculte");
        }
    }

    if (version_compare($oldversion, '2.5', '<')) {
        Database::get()->query("INSERT IGNORE INTO `config` (`key`, `value`) VALUES
                    ('disable_eclass_stud_reg', '0'),
                    ('disable_eclass_prof_reg', '0'),
                    ('email_verification_required', '1'),
                    ('dont_mail_unverified_mails', '1'),
                    ('close_user_registration', '0'),
                    ('max_glossary_terms', '250'),
                    ('code_key', '" . generate_secret_key(32) . "')");

        // old users have their email verified
        if (DBHelper::fieldExists('user', 'verified_mail')) {
            Database::get()->query('ALTER TABLE `user` MODIFY `verified_mail` TINYINT(1) NOT NULL DEFAULT ' . EMAIL_UNVERIFIED);
            Database::get()->query('UPDATE `user` SET `verified_mail`= ' . EMAIL_VERIFIED);
        }
        DBHelper::fieldExists('user_request', 'verified_mail') or
                Database::get()->query("ALTER TABLE `user_request` ADD `verified_mail` TINYINT(1) NOT NULL DEFAULT " . EMAIL_UNVERIFIED . " AFTER `email`");

        Database::get()->query("UPDATE `user` SET `email`=LOWER(TRIM(`email`))");
        Database::get()->query("UPDATE `user` SET `username`=TRIM(`username`)");
    }

    if (version_compare($oldversion, '2.5.2', '<')) {
        Database::get()->query("ALTER TABLE `user` MODIFY `password` VARCHAR(60) DEFAULT 'empty'");
        Database::get()->query("DROP TABLE IF EXISTS passwd_reset");
    }

    if (version_compare($oldversion, '2.6', '<')) {
        Database::get()->query("ALTER TABLE `config` CHANGE `value` `value` TEXT NOT NULL");
        $old_close_user_registration = Database::get()->querySingle("SELECT `value` FROM config WHERE `key` = 'close_user_registration'")->value;
        if ($old_close_user_registration == 0) {
            $eclass_stud_reg = 2;
        } else {
            $eclass_stud_reg = 1;
        }
        Database::get()->query("UPDATE `config`
                              SET `key` = 'eclass_stud_reg',
                                  `value`= $eclass_stud_reg
                              WHERE `key` = 'close_user_registration'");

        $old_disable_eclass_prof_reg = !Database::get()->querySingle("SELECT `value` FROM config WHERE `key` = 'disable_eclass_prof_reg'")->value;
        Database::get()->query("UPDATE `config` SET `key` = 'eclass_prof_reg',
                                           `value` = $old_disable_eclass_prof_reg
                                      WHERE `key` = 'disable_eclass_prof_reg'");
        Database::get()->query("DELETE FROM `config` WHERE `key` = 'disable_eclass_stud_reg'");
        Database::get()->query("DELETE FROM `config` WHERE `key` = 'alt_auth_student_req'");
        $old_alt_auth_stud_req = Database::get()->querySingle("SELECT `value` FROM config WHERE `key` = 'alt_auth_student_req'")->value;
        if ($old_alt_auth_stud_req == 1) {
            $alt_auth_stud_req = 1;
        } else {
            $alt_auth_stud_req = 2;
        }
        Database::get()->query("INSERT IGNORE INTO `config`(`key`, `value`) VALUES
                                        ('user_registration', 1),
                                        ('alt_auth_prof_reg', 1),
                                        ('alt_auth_stud_reg', $alt_auth_stud_req)");

        Database::get()->query("DELETE FROM `config` WHERE `key` = 'alt_auth_student_req'");

        if (!DBHelper::fieldExists('user', 'whitelist')) {
            Database::get()->query("ALTER TABLE `user` ADD `whitelist` TEXT AFTER `am_public`");
            Database::get()->query("UPDATE `user` SET `whitelist` = '*,,' WHERE user_id = 1");
        }
        Database::get()->query("INSERT IGNORE INTO `config` (`key`, `value`) VALUES
                            ('student_upload_whitelist', ?s),
                            ('teacher_upload_whitelist', ?s)", $default_student_upload_whitelist, $teacher_upload_whitelist);
        if (!DBHelper::fieldExists('user', 'last_passreminder')) {
            Database::get()->query("ALTER TABLE `user` ADD `last_passreminder` DATETIME DEFAULT NULL AFTER `whitelist`");
        }
        Database::get()->query("CREATE TABLE IF NOT EXISTS login_failure (
                id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                ip varchar(45) NOT NULL,
                count tinyint(4) unsigned NOT NULL default '0',
                last_fail datetime NOT NULL,
                UNIQUE KEY ip (ip)) $charset_spec");
    }

    if (version_compare($oldversion, '2.6.1', '<')) {
        Database::get()->query("INSERT IGNORE INTO `config`(`key`, `value`) VALUES
                                        ('login_fail_check', 1),
                                        ('login_fail_threshold', 15),
                                        ('login_fail_deny_interval', 5),
                                        ('login_fail_forgive_interval', 24)");
    }

    if (version_compare($oldversion, '2.7', '<')) {
        DBHelper::fieldExists('document', 'extra_path') or
                Database::get()->query("ALTER TABLE `document` ADD `extra_path` VARCHAR(255) NOT NULL DEFAULT '' AFTER `path`");
        DBHelper::fieldExists('user', 'parent_email') or
                Database::get()->query("ALTER TABLE `user` ADD `parent_email` VARCHAR(100) NOT NULL DEFAULT '' AFTER `email`");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `parents_announcements` (
                        `id` mediumint(9) NOT NULL auto_increment,
                        `title` varchar(255) default NULL,
                        `content` text,
                        `date` datetime default NULL,
                        `sender_id` int(11) NOT NULL,
                        `recipient_id` int(11) NOT NULL,
                        `course_id` int(11) NOT NULL,
                         PRIMARY KEY (`id`)) $charset_spec");
    }

    if (version_compare($oldversion, '2.8', '<')) {
        Database::get()->query("INSERT IGNORE INTO `config`(`key`, `value`) VALUES
                                        ('course_metadata', 0),
                                        ('opencourses_enable', 0)");

        DBHelper::fieldExists('document', 'public') or
                Database::get()->query("ALTER TABLE `document` ADD `public` TINYINT(4) NOT NULL DEFAULT 1 AFTER `visibility`");
        DBHelper::fieldExists('cours_user', 'reviewer') or
                Database::get()->query("ALTER TABLE `cours_user` ADD `reviewer` INT(11) NOT NULL DEFAULT '0' AFTER `editor`");
        DBHelper::fieldExists('cours', 'course_license') or
                Database::get()->query("ALTER TABLE `cours` ADD COLUMN `course_license` TINYINT(4) NOT NULL DEFAULT '20' AFTER `course_addon`");

        DBHelper::fieldExists("cours_user", "reviewer") or
                Database::get()->query("ALTER TABLE `cours_user` ADD `reviewer` INT(11) NOT NULL DEFAULT '0' AFTER `editor`");

        // prevent dir list under video storage
        if ($handle = opendir($webDir . '/video/')) {
            while (false !== ($entry = readdir($handle))) {
                if (is_dir($webDir . '/video/' . $entry) && $entry != "." && $entry != "..") {
                    touch_or_error($webDir . '/video/' . $entry . '/index.php');
                }
            }
            closedir($handle);
        }
    }

    if (version_compare($oldversion, '2.8.3', '<')) {
        Database::get()->query("CREATE TABLE course_review (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `course_id` INT(11) NOT NULL,
                `is_certified` BOOL NOT NULL DEFAULT 0,
                `level` TINYINT(4) NOT NULL DEFAULT 0,
                `last_review` DATETIME NOT NULL,
                `last_reviewer` INT(11) NOT NULL,
                PRIMARY KEY (id)) $charset_spec");

        require_once 'modules/course_metadata/CourseXML.php';
        Database::get()->queryFunc("SELECT cours_id, code FROM cours", function ($course) {
            $xml = CourseXMLElement::initFromFile($course->code);
            if ($xml !== false) {
                $xmlData = $xml->asFlatArray();

                $is_certified = 0;
                if ((isset($xmlData['course_confirmAMinusLevel']) && $xmlData['course_confirmAMinusLevel'] == 'true') ||
                        (isset($xmlData['course_confirmALevel']) && $xmlData['course_confirmALevel'] == 'true') ||
                        (isset($xmlData['course_confirmAPlusLevel']) && $xmlData['course_confirmAPlusLevel'] == 'true')) {
                    $is_certified = 1;
                }

                $level = CourseXMLElement::NO_LEVEL;
                if (isset($xmlData['course_confirmAMinusLevel']) && $xmlData['course_confirmAMinusLevel'] == 'true')
                    $level = CourseXMLElement::A_MINUS_LEVEL;
                if (isset($xmlData['course_confirmALevel']) && $xmlData['course_confirmALevel'] == 'true')
                    $level = CourseXMLElement::A_LEVEL;
                if (isset($xmlData['course_confirmAPlusLevel']) && $xmlData['course_confirmAPlusLevel'] == 'true')
                    $level = CourseXMLElement::A_PLUS_LEVEL;

                $last_review = date('Y-m-d H:i:s');
                if (isset($xmlData['course_lastLevelConfirmation']) &&
                        strlen($xmlData['course_lastLevelConfirmation']) > 0 &&
                        ($ts = strtotime($xmlData['course_lastLevelConfirmation'])) > 0) {
                    $last_review = date('Y-m-d H:i:s', $ts);
                }

                Database::get()->query("INSERT INTO course_review (course_id, is_certified, level, last_review, last_reviewer)
                                VALUES (" . $course->cours_id . ", $is_certified, $level, '$last_review', $uid)");
            }
        });
    }

    if (version_compare($oldversion, '2.10', '<')) {
        DBHelper::fieldExists('course_units', 'public') or
                Database::get()->query("ALTER TABLE `course_units` ADD `public` TINYINT(4) NOT NULL DEFAULT '1' AFTER `visibility`");

        if (!DBHelper::tableExists('course_description_type')) {
            Database::get()->query("CREATE TABLE `course_description_type` (
                            `id` smallint(6) NOT NULL AUTO_INCREMENT,
                            `title` mediumtext,
                            `syllabus` tinyint(1) DEFAULT 0,
                            `objectives` tinyint(1) DEFAULT 0,
                            `bibliography` tinyint(1) DEFAULT 0,
                            `teaching_method` tinyint(1) DEFAULT 0,
                            `assessment_method` tinyint(1) DEFAULT 0,
                            `prerequisites` tinyint(1) DEFAULT 0,
                            `featured_books` tinyint(1) DEFAULT 0,
                            `instructors` tinyint(1) DEFAULT 0,
                            `target_group` tinyint(1) DEFAULT 0,
                            `active` tinyint(1) DEFAULT 1,
                            `order` int(11) NOT NULL,
                            `icon` varchar(255) NOT NULL,
                            PRIMARY KEY (`id`)) $charset_spec");

            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `syllabus`, `order`, `icon`) VALUES (1, 'a:2:{s:2:\"el\";s:41:\"Περιεχόμενο μαθήματος\";s:2:\"en\";s:15:\"Course Syllabus\";}', 1, 1, '0.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `objectives`, `order`, `icon`) VALUES (2, 'a:2:{s:2:\"el\";s:33:\"Μαθησιακοί στόχοι\";s:2:\"en\";s:23:\"Course Objectives/Goals\";}', 1, 2, '1.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `bibliography`, `order`, `icon`) VALUES (3, 'a:2:{s:2:\"el\";s:24:\"Βιβλιογραφία\";s:2:\"en\";s:12:\"Bibliography\";}', 1, 3, '2.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `teaching_method`, `order`, `icon`) VALUES (4, 'a:2:{s:2:\"el\";s:37:\"Μέθοδοι διδασκαλίας\";s:2:\"en\";s:21:\"Instructional Methods\";}', 1, 4, '3.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `assessment_method`, `order`, `icon`) VALUES (5, 'a:2:{s:2:\"el\";s:37:\"Μέθοδοι αξιολόγησης\";s:2:\"en\";s:18:\"Assessment Methods\";}', 1, 5, '4.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `prerequisites`, `order`, `icon`) VALUES (6, 'a:2:{s:2:\"el\";s:28:\"Προαπαιτούμενα\";s:2:\"en\";s:29:\"Prerequisites/Prior Knowledge\";}', 1, 6, '5.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `instructors`, `order`, `icon`) VALUES (7, 'a:2:{s:2:\"el\";s:22:\"Διδάσκοντες\";s:2:\"en\";s:11:\"Instructors\";}', 1, 7, '6.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `target_group`, `order`, `icon`) VALUES (8, 'a:2:{s:2:\"el\";s:23:\"Ομάδα στόχος\";s:2:\"en\";s:12:\"Target Group\";}', 1, 8, '7.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `featured_books`, `order`, `icon`) VALUES (9, 'a:2:{s:2:\"el\";s:47:\"Προτεινόμενα συγγράμματα\";s:2:\"en\";s:9:\"Textbooks\";}', 1, 9, '8.png')");
            Database::get()->query("INSERT INTO `course_description_type` (`id`, `title`, `order`, `icon`) VALUES (10, 'a:2:{s:2:\"el\";s:22:\"Περισσότερα\";s:2:\"en\";s:15:\"Additional info\";}', 11, 'default.png')");
        }

        if (!DBHelper::tableExists('course_description')) {
            Database::get()->query("CREATE TABLE IF NOT EXISTS `course_description` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `course_id` int(11) NOT NULL,
                            `title` varchar(255) NOT NULL,
                            `comments` mediumtext,
                            `type` smallint(6),
                            `visible` tinyint(4) DEFAULT 0,
                            `order` int(11) NOT NULL,
                            `update_dt` datetime NOT NULL,
                            PRIMARY KEY (`id`)) $charset_spec");

            Database::get()->query('CREATE INDEX `cid` ON course_description (course_id)');
            Database::get()->query('CREATE INDEX `cd_type_index` ON course_description (type)');
            Database::get()->query('CREATE INDEX `cd_cid_type_index` ON course_description (course_id, type)');

            Database::get()->queryFunc("SELECT ur.id, ur.res_id, ur.title, ur.comments, ur.order, ur.visibility, ur.date, cu.course_id
                                FROM unit_resources ur LEFT JOIN course_units cu ON (cu.id = ur.unit_id) WHERE cu.order = -1 AND ur.res_id <> -1", function($ures) {
                $newvis = ($ures->visibility == 'i') ? 0 : 1;
                Database::get()->query("INSERT INTO course_description SET
                                course_id = ?d, title = ?s, comments = ?s,
                                visible = ?d, `order` = ?d, update_dt = ?t", intval($ures->course_id), $ures->title, $ures->comments, intval($newvis), intval($ures->order), $ures->date);
                Database::get()->query("DELETE FROM unit_resources WHERE id = ?d", intval($ures->id));
            });
        }

        if (!DBHelper::tableExists('oai_record')) {
            Database::get()->query("CREATE TABLE `oai_record` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `course_id` int(11) NOT NULL UNIQUE,
                            `oai_identifier` varchar(255) DEFAULT NULL,
                            `oai_metadataprefix` varchar(255) DEFAULT 'oai_dc',
                            `oai_set` varchar(255) DEFAULT 'class:course',
                            `datestamp` datetime DEFAULT NULL,
                            `deleted` tinyint(1) NOT NULL DEFAULT 0,
                            `dc_title` text DEFAULT NULL,
                            `dc_description` text DEFAULT NULL,
                            `dc_syllabus` text DEFAULT NULL,
                            `dc_subject` text DEFAULT NULL,
                            `dc_subsubject` text DEFAULT NULL,
                            `dc_objectives` text DEFAULT NULL,
                            `dc_level` text DEFAULT NULL,
                            `dc_prerequisites` text DEFAULT NULL,
                            `dc_instructor` text DEFAULT NULL,
                            `dc_department` text DEFAULT NULL,
                            `dc_institution` text DEFAULT NULL,
                            `dc_coursephoto` text DEFAULT NULL,
                            `dc_coursephotomime` text DEFAULT NULL,
                            `dc_instructorphoto` text DEFAULT NULL,
                            `dc_instructorphotomime` text DEFAULT NULL,
                            `dc_url` text DEFAULT NULL,
                            `dc_identifier` text DEFAULT NULL,
                            `dc_language` text DEFAULT NULL,
                            `dc_date` datetime DEFAULT NULL,
                            `dc_format` text DEFAULT NULL,
                            `dc_rights` text DEFAULT NULL,
                            `dc_videolectures` text DEFAULT NULL,
                            `dc_code` text DEFAULT NULL,
                            `dc_keywords` text DEFAULT NULL,
                            `dc_contentdevelopment` text DEFAULT NULL,
                            `dc_formattypes` text DEFAULT NULL,
                            `dc_recommendedcomponents` text DEFAULT NULL,
                            `dc_assignments` text DEFAULT NULL,
                            `dc_requirements` text DEFAULT NULL,
                            `dc_remarks` text DEFAULT NULL,
                            `dc_acknowledgments` text DEFAULT NULL,
                            `dc_coteaching` text DEFAULT NULL,
                            `dc_coteachingcolleagueopenscourse` text DEFAULT NULL,
                            `dc_coteachingautonomousdepartment` text DEFAULT NULL,
                            `dc_coteachingdepartmentcredithours` text DEFAULT NULL,
                            `dc_yearofstudy` text DEFAULT NULL,
                            `dc_semester` text DEFAULT NULL,
                            `dc_coursetype` text DEFAULT NULL,
                            `dc_credithours` text DEFAULT NULL,
                            `dc_credits` text DEFAULT NULL,
                            `dc_institutiondescription` text DEFAULT NULL,
                            `dc_curriculumtitle` text DEFAULT NULL,
                            `dc_curriculumdescription` text DEFAULT NULL,
                            `dc_outcomes` text DEFAULT NULL,
                            `dc_curriculumkeywords` text DEFAULT NULL,
                            `dc_sector` text DEFAULT NULL,
                            `dc_targetgroup` text DEFAULT NULL,
                            `dc_curriculumtargetgroup` text DEFAULT NULL,
                            `dc_featuredbooks` text DEFAULT NULL,
                            `dc_structure` text DEFAULT NULL,
                            `dc_teachingmethod` text DEFAULT NULL,
                            `dc_assessmentmethod` text DEFAULT NULL,
                            `dc_eudoxuscode` text DEFAULT NULL,
                            `dc_eudoxusurl` text DEFAULT NULL,
                            `dc_kalliposurl` text DEFAULT NULL,
                            `dc_numberofunits` text DEFAULT NULL,
                            `dc_unittitle` text DEFAULT NULL,
                            `dc_unitdescription` text DEFAULT NULL,
                            `dc_unitkeywords` text DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `oai_identifier` (`oai_identifier`)) $charset_spec");

            Database::get()->query('CREATE INDEX `cid` ON oai_record (course_id)');
            Database::get()->query('CREATE INDEX `oaiid` ON oai_record (oai_identifier)');
        }

        // unique course_id for course_review
        $crevres = Database::get()->queryArray("SELECT DISTINCT course_id FROM course_review");
        foreach ($crevres as $crev) {
            $crevres2 = Database::get()->queryArray("SELECT * FROM course_review WHERE course_id = ?d ORDER BY last_review DESC", intval($crev->course_id));
            $crevcnt = 0;
            foreach ($revres2 as $crev2) {
                if ($crevcnt > 0) {
                    Database::get()->query("DELETE FROM course_review WHERE id = ?d", intval($crev2['id']));
                }
                $crevcnt++;
            }
        }
        Database::get()->query("ALTER TABLE course_review ADD UNIQUE crid (course_id)");

        if (!DBHelper::fieldExists('document', 'editable')) {
            Database::get()->query("ALTER TABLE `document` ADD editable TINYINT(1) NOT NULL DEFAULT 0,
                                                         ADD lock_user_id INT(11) NOT NULL DEFAULT 0");
        }
    }

    if (version_compare($oldversion, '3.0b2', '<')) {
        // Check whether new tables already exist and delete them if empty,
        // rename them otherwise
        $new_tables = array('cron_params', 'log', 'log_archive', 'forum',
            'forum_category', 'forum_post', 'forum_topic',
            'video', 'videolink', 'dropbox_msg', 'dropbox_attachment', 'dropbox_index',
            'lp_module', 'lp_learnPath', 'lp_rel_learnPath_module', 'lp_asset',
            'lp_user_module_progress', 'wiki_properties', 'wiki_acls', 'wiki_pages',
            'wiki_pages_content', 'poll', 'poll_answer_record', 'poll_question',
            'poll_question_answer', 'assignment', 'assignment_submit',
            'exercise', 'exercise_user_record', 'exercise_question',
            'exercise_answer', 'exercise_with_questions', 'course_module',
            'actions', 'actions_summary', 'logins', 'wiki_locks', 'bbb_servers', 'bbb_session',
            'blog_post', 'comments', 'rating', 'rating_cache', 'forum_user_stats');
        foreach ($new_tables as $table_name) {
            if (DBHelper::tableExists($table_name)) {
                if (Database::get()->query("SELECT COUNT(*) as value FROM `$table_name`")->value > 0) {
                    echo "Warning: Database inconsistent - table '$table_name' already",
                    " exists in $mysqlMainDb - renaming it to 'old_$table_name'<br>\n";
                    Database::get()->query("RENAME TABLE `$table_name` TO `old_$table_name`");
                } else {
                    Database::get()->query("DROP TABLE `$table_name`");
                }
            }
        }

        Database::get()->query("INSERT IGNORE INTO `config` (`key`, `value`) VALUES
                                        ('actions_expire_interval', 12),
                                        ('course_metadata', 0)");

        if (!DBHelper::fieldExists('user_request', 'state')) {
            Database::get()->query("ALTER TABLE `user_request`
                    CHANGE `name` `givenname` VARCHAR(100) NOT NULL DEFAULT '',
                    CHANGE `surname` `surname` VARCHAR(100) NOT NULL DEFAULT '',
                    CHANGE `uname` `username` VARCHAR(100) NOT NULL DEFAULT '',
                    CHANGE `email` `email` VARCHAR(100) NOT NULL DEFAULT '',
                    CHANGE `status` `state` INT(11) NOT NULL DEFAULT 0,
                    CHANGE `statut` `status` TINYINT(4) NOT NULL DEFAULT 1");
        }

        Database::get()->query("CREATE TABLE `cron_params` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(255) NOT NULL UNIQUE,
                        `last_run` DATETIME NOT NULL)
                        $charset_spec");

        Database::get()->query("DROP TABLE IF EXISTS passwd_reset");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `log` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `user_id` INT(11) NOT NULL DEFAULT 0,
                        `course_id` INT(11) NOT NULL DEFAULT 0,
                        `module_id` INT(11) NOT NULL default 0,
                        `details` TEXT NOT NULL,
                        `action_type` INT(11) NOT NULL DEFAULT 0,
                        `ts` DATETIME NOT NULL,
                        `ip` VARCHAR(45) NOT NULL DEFAULT '',
                        PRIMARY KEY (`id`))
                        $charset_spec");


        Database::get()->query("CREATE TABLE IF NOT EXISTS `log_archive` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `user_id` INT(11) NOT NULL DEFAULT 0,
                        `course_id` INT(11) NOT NULL DEFAULT 0,
                        `module_id` INT(11) NOT NULL default 0,
                        `details` TEXT NOT NULL,
                        `action_type` INT(11) NOT NULL DEFAULT 0,
                        `ts` DATETIME NOT NULL,
                        `ip` VARCHAR(45) NOT NULL DEFAULT '',
                        PRIMARY KEY (`id`))
                        $charset_spec");

        // add index on `loginout`.`id_user` for performace
        Database::get()->query("ALTER TABLE `loginout` ADD INDEX (`id_user`)");

        // update table admin_announcement
        if (!DBHelper::tableExists('admin_announcement')) {
            Database::get()->query("RENAME TABLE `admin_announcements` TO `admin_announcement`");
            Database::get()->query("ALTER TABLE admin_announcement CHANGE `ordre` `order` MEDIUMINT(11)");
            Database::get()->query("ALTER TABLE admin_announcement CHANGE `visible` `visible` TEXT");
            Database::get()->query("UPDATE admin_announcement SET visible = '1' WHERE visible = 'V'");
            Database::get()->query("UPDATE admin_announcement SET visible = '0' WHERE visible = 'I'");
            Database::get()->query("ALTER TABLE admin_announcement CHANGE `visible` `visible` TINYINT(4)");
        }

        // update table course_units and unit_resources
        if (!DBHelper::fieldExists('course_units', 'visible')) {
            Database::get()->query("UPDATE `course_units` SET visibility = '1' WHERE visibility = 'v'");
            Database::get()->query("UPDATE `course_units` SET visibility = '0' WHERE visibility = 'i'");
            Database::get()->query("ALTER TABLE `course_units` CHANGE `visibility` `visible` TINYINT(4) DEFAULT 0");
        }

        if (!DBHelper::fieldExists('unit_resources', 'visible')) {
            Database::get()->query("UPDATE `unit_resources` SET visibility = '1' WHERE visibility = 'v'");
            Database::get()->query("UPDATE `unit_resources` SET visibility = '0' WHERE visibility = 'i'");
            Database::get()->query("ALTER TABLE `unit_resources` CHANGE `visibility` `visible` TINYINT(4) DEFAULT 0");
        }

        // update table document
        if (!DBHelper::fieldExists('document', 'visible')) {
            Database::get()->query("UPDATE `document` SET visibility = '1' WHERE visibility = 'v'");
            Database::get()->query("UPDATE `document` SET visibility = '0' WHERE visibility = 'i'");
            Database::get()->query("ALTER TABLE `document`
                                CHANGE `visibility` `visible` TINYINT(4) NOT NULL DEFAULT 1");
        }

        // Rename table `annonces` to `announcements`
        if (!DBHelper::tableExists('announcement')) {
            Database::get()->query("RENAME TABLE annonces TO announcement");
            Database::get()->query("UPDATE announcement SET visibility = '0' WHERE visibility <> 'v'");
            Database::get()->query("UPDATE announcement SET visibility = '1' WHERE visibility = 'v'");
            Database::get()->query("ALTER TABLE announcement CHANGE `contenu` `content` TEXT,
                                       CHANGE `temps` `date` DATETIME,
                                       CHANGE `cours_id` `course_id` INT(11),
                                       CHANGE `ordre` `order` MEDIUMINT(11),
                                       CHANGE `visibility` `visible` TINYINT(4) DEFAULT 0,
                                       ADD `start_display` DATE NOT NULL DEFAULT '2014-01-01',
                                       ADD `stop_display` DATE NOT NULL DEFAULT '2094-12-31',
                                       DROP INDEX annonces");
        } else {
            Database::get()->query("ALTER TABLE announcement
                                       ADD `start_display` NOT NULL DATE DEFAULT '2014-01-01',
                                       ADD `stop_display` NOT NULL DATE DEFAULT '2094-12-31'");
        }

        // create forum tables
        Database::get()->query("CREATE TABLE IF NOT EXISTS `forum` (
                            `id` INT(10) NOT NULL AUTO_INCREMENT,
                            `name` VARCHAR(150) DEFAULT '' NOT NULL,
                            `desc` MEDIUMTEXT NOT NULL,
                            `num_topics` INT(10) DEFAULT 0 NOT NULL,
                            `num_posts` INT(10) DEFAULT 0 NOT NULL,
                            `last_post_id` INT(10) DEFAULT 0 NOT NULL,
                            `cat_id` INT(10) DEFAULT 0 NOT NULL,
                            `course_id` INT(11) NOT NULL,
                            PRIMARY KEY (`id`))
                            $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `forum_category` (
                            `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `cat_title` VARCHAR(100) DEFAULT '' NOT NULL,
                            `cat_order` INT(11) DEFAULT 0 NOT NULL,
                            `course_id` INT(11) NOT NULL,
                            KEY `forum_category_index` (`id`, `course_id`))
                            $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `forum_notify` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                            `user_id` INT(11) DEFAULT 0 NOT NULL,
                            `cat_id` INT(11) DEFAULT 0 NOT NULL ,
                            `forum_id` INT(11) DEFAULT 0 NOT NULL,
                            `topic_id` INT(11) DEFAULT 0 NOT NULL ,
                            `notify_sent` BOOL DEFAULT 0 NOT NULL ,
                            `course_id` INT(11) DEFAULT 0 NOT NULL)
                            $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `forum_post` (
                            `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `topic_id` INT(10) NOT NULL DEFAULT 0,
                            `post_text` MEDIUMTEXT NOT NULL,
                            `poster_id` INT(10) NOT NULL DEFAULT 0,
                            `post_time` DATETIME,
                            `poster_ip` VARCHAR(45) DEFAULT '' NOT NULL,
                            `parent_post_id` INT(10) NOT NULL DEFAULT 0)
                            $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `forum_topic` (
                            `id` int(10) NOT NULL auto_increment,
                            `title` varchar(100) DEFAULT NULL,
                            `poster_id` int(10) DEFAULT NULL,
                            `topic_time` datetime,
                            `num_views` int(10) NOT NULL default '0',
                            `num_replies` int(10) NOT NULL default '0',
                            `last_post_id` int(10) NOT NULL default '0',
                            `forum_id` int(10) NOT NULL default '0',
                            `locked` TINYINT DEFAULT 0 NOT NULL,
                            PRIMARY KEY (`id`))
                            $charset_spec");

        if (!DBHelper::fieldExists('forum_topic', 'locked')) {
            Database::get()->query("ALTER TABLE `forum_topic` ADD `locked` TINYINT DEFAULT 0 NOT NULL");
        }

        Database::get()->query("CREATE TABLE IF NOT EXISTS `forum_user_stats` (
                            `user_id` INT(11) NOT NULL,
                            `num_posts` INT(11) NOT NULL,
                            `course_id` INT(11) NOT NULL,
                            PRIMARY KEY (`user_id`,`course_id`)) $charset_spec");

        $forum_stats = Database::get()->queryArray("SELECT forum.course_id, forum_post.poster_id, count(*) as c FROM forum_post
                            INNER JOIN forum_topic ON forum_post.topic_id = forum_topic.id
                            INNER JOIN forum ON forum.id = forum_topic.forum_id
                            GROUP BY forum.course_id, forum_post.poster_id");

        if ($forum_stats) {
            $query = "INSERT INTO forum_user_stats (user_id, num_posts, course_id) VALUES ";
            $vars_to_flatten = array();
            foreach ($forum_stats as $forum_stat) {
                $query .= "(?d,?d,?d),";
                $vars_to_flatten[] = $forum_stat->poster_id;
                $vars_to_flatten[] = $forum_stat->c;
                $vars_to_flatten[] = $forum_stat->course_id;
            }
            $query = rtrim($query, ',');
            Database::get()->query($query, $vars_to_flatten);
        }

        // create video tables
        Database::get()->query("CREATE TABLE IF NOT EXISTS video (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `path` VARCHAR(255),
                            `url` VARCHAR(200),
                            `title` VARCHAR(200),
                            `category` INT(6) DEFAULT NULL,
                            `description` TEXT,
                            `creator` VARCHAR(200),
                            `publisher` VARCHAR(200),
                            `date` DATETIME,
                            `visible` TINYINT(4) NOT NULL DEFAULT 1,
                            `public` TINYINT(4) NOT NULL DEFAULT 1)
                            $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS videolink (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `url` VARCHAR(200) NOT NULL DEFAULT '',
                            `title` VARCHAR(200) NOT NULL DEFAULT '',
                            `category` INT(6) DEFAULT NULL,
                            `description` TEXT NOT NULL,
                            `creator` VARCHAR(200) NOT NULL DEFAULT '',
                            `publisher` VARCHAR(200) NOT NULL DEFAULT '',
                            `date` DATETIME,
                            `visible` TINYINT(4) NOT NULL DEFAULT 1,
                            `public` TINYINT(4) NOT NULL DEFAULT 1)
                            $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS video_category (
                            id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            name VARCHAR(255) NOT NULL,
                            description TEXT DEFAULT NULL)
                            $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS dropbox_msg (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `author_id` INT(11) UNSIGNED NOT NULL,
                            `subject` VARCHAR(250) NOT NULL,
                            `body` LONGTEXT NOT NULL,
                            `timestamp` INT(11) NOT NULL) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS dropbox_attachment (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `msg_id` INT(11) UNSIGNED NOT NULL,
                            `filename` VARCHAR(250) NOT NULL,
                            `real_filename` varchar(255) NOT NULL,
                            `filesize` INT(11) UNSIGNED NOT NULL) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS dropbox_index (
                            `msg_id` INT(11) UNSIGNED NOT NULL,
                            `recipient_id` INT(11) UNSIGNED NOT NULL,
                            `is_read` BOOLEAN NOT NULL DEFAULT 0,
                            `deleted` BOOLEAN NOT NULL DEFAULT 0,
                            PRIMARY KEY (`msg_id`, `recipient_id`)) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `lp_module` (
                            `module_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `name` VARCHAR(255) NOT NULL DEFAULT '',
                            `comment` TEXT NOT NULL,
                            `accessibility` enum('PRIVATE','PUBLIC') NOT NULL DEFAULT 'PRIVATE',
                            `startAsset_id` INT(11) NOT NULL DEFAULT 0,
                            `contentType` enum('CLARODOC','DOCUMENT','EXERCISE','HANDMADE','SCORM','SCORM_ASSET','LABEL','COURSE_DESCRIPTION','LINK','MEDIA','MEDIALINK') NOT NULL,
                            `launch_data` TEXT NOT NULL)
                            $charset_spec");
        //COMMENT='List of available modules used in learning paths';
        Database::get()->query("CREATE TABLE IF NOT EXISTS `lp_learnPath` (
                            `learnPath_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `name` VARCHAR(255) NOT NULL DEFAULT '',
                            `comment` TEXT NOT NULL,
                            `lock` enum('OPEN','CLOSE') NOT NULL DEFAULT 'OPEN',
                            `visible` TINYINT(4) NOT NULL DEFAULT 0,
                            `rank` INT(11) NOT NULL DEFAULT 0)
                            $charset_spec");
        //COMMENT='List of learning Paths';
        Database::get()->query("CREATE TABLE IF NOT EXISTS `lp_rel_learnPath_module` (
                            `learnPath_module_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `learnPath_id` INT(11) NOT NULL DEFAULT 0,
                            `module_id` INT(11) NOT NULL DEFAULT 0,
                            `lock` enum('OPEN','CLOSE') NOT NULL DEFAULT 'OPEN',
                            `visible` TINYINT(4),
                            `specificComment` TEXT NOT NULL,
                            `rank` INT(11) NOT NULL DEFAULT '0',
                            `parent` INT(11) NOT NULL DEFAULT '0',
                            `raw_to_pass` TINYINT(4) NOT NULL DEFAULT '50')
                            $charset_spec");
        //COMMENT='This table links module to the learning path using them';
        Database::get()->query("CREATE TABLE IF NOT EXISTS `lp_asset` (
                            `asset_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `module_id` INT(11) NOT NULL DEFAULT '0',
                            `path` VARCHAR(255) NOT NULL DEFAULT '',
                            `comment` VARCHAR(255) default NULL)
                            $charset_spec");
        //COMMENT='List of resources of module of learning paths';
        Database::get()->query("CREATE TABLE IF NOT EXISTS `lp_user_module_progress` (
                            `user_module_progress_id` INT(22) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                            `learnPath_module_id` INT(11) NOT NULL DEFAULT '0',
                            `learnPath_id` INT(11) NOT NULL DEFAULT '0',
                            `lesson_location` VARCHAR(255) NOT NULL DEFAULT '',
                            `lesson_status` enum('NOT ATTEMPTED','PASSED','FAILED','COMPLETED','BROWSED','INCOMPLETE','UNKNOWN') NOT NULL default 'NOT ATTEMPTED',
                            `entry` enum('AB-INITIO','RESUME','') NOT NULL DEFAULT 'AB-INITIO',
                            `raw` TINYINT(4) NOT NULL DEFAULT '-1',
                            `scoreMin` TINYINT(4) NOT NULL DEFAULT '-1',
                            `scoreMax` TINYINT(4) NOT NULL DEFAULT '-1',
                            `total_time` VARCHAR(13) NOT NULL DEFAULT '0000:00:00.00',
                            `session_time` VARCHAR(13) NOT NULL DEFAULT '0000:00:00.00',
                            `suspend_data` TEXT NOT NULL,
                            `credit` enum('CREDIT','NO-CREDIT') NOT NULL DEFAULT 'NO-CREDIT')
                            $charset_spec");
        //COMMENT='Record the last known status of the user in the course';
        DBHelper::indexExists('lp_user_module_progress', 'optimize') or
                Database::get()->query('CREATE INDEX `optimize` ON lp_user_module_progress (user_id, learnPath_module_id)');

        Database::get()->query("CREATE TABLE IF NOT EXISTS `wiki_properties` (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `title` VARCHAR(255) NOT NULL DEFAULT '',
                            `description` TEXT NULL,
                            `group_id` INT(11) NOT NULL DEFAULT 0 )
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `wiki_acls` (
                            `wiki_id` INT(11) UNSIGNED NOT NULL,
                            `flag` VARCHAR(255) NOT NULL,
                            `value` ENUM('false','true') NOT NULL DEFAULT 'false',
                            PRIMARY KEY (wiki_id, flag))
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `wiki_pages` (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `wiki_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                            `owner_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `title` VARCHAR(255) NOT NULL DEFAULT '',
                            `ctime` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `last_version` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                            `last_mtime` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' )
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `wiki_pages_content` (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `pid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                            `editor_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `mtime` DATETIME NOT NULL default '0000-00-00 00:00:00',
                            `content` TEXT NOT NULL,
                            `changelog` VARCHAR(200) )  $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `wiki_locks` (
                            `ptitle` VARCHAR(255) NOT NULL DEFAULT '',
                            `wiki_id` INT(11) UNSIGNED NOT NULL,
                            `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `ltime_created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `ltime_alive` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
                            PRIMARY KEY (ptitle, wiki_id) ) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `blog_post` (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `title` VARCHAR(255) NOT NULL DEFAULT '',
                            `content` TEXT NOT NULL,
                            `time` DATETIME NOT NULL,
                            `views` int(11) UNSIGNED NOT NULL DEFAULT '0',
                            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `course_id` INT(11) NOT NULL) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `comments` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `rid` INT(11) NOT NULL,
                            `rtype` VARCHAR(50) NOT NULL,
                            `content` TEXT NOT NULL,
                            `time` DATETIME NOT NULL,
                            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `rating` (
                            `rate_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `rid` INT(11) NOT NULL,
                            `rtype` VARCHAR(50) NOT NULL,
                            `value` TINYINT NOT NULL,
                            `widget` VARCHAR(30) NOT NULL,
                            `time` DATETIME NOT NULL,
                            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `rating_source` VARCHAR(50) NOT NULL,
                            INDEX `rating_index_1` (`rid`, `rtype`, `widget`),
                            INDEX `rating_index_2` (`rid`, `rtype`, `user_id`, `widget`)) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `rating_cache` (
                            `rate_cache_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `rid` INT(11) NOT NULL,
                            `rtype` VARCHAR(50) NOT NULL,
                            `value` FLOAT NOT NULL DEFAULT 0,
                            `count` INT(11) NOT NULL DEFAULT 0,
                            `tag` VARCHAR(50),
                            INDEX `rating_cache_index_1` (`rid`, `rtype`, `tag`)) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `gradebook` (
                            `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `students_semester` TINYINT(4) NOT NULL DEFAULT 1,
                            `range` TINYINT(4) NOT NULL DEFAULT 10) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `gradebook_activities` (
                            `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `gradebook_id` MEDIUMINT(11) NOT NULL,
                            `title` VARCHAR(250) DEFAULT NULL,
                            `activity_type` INT(11) DEFAULT NULL,
                            `date` DATETIME DEFAULT NULL,
                            `description` TEXT NOT NULL,
                            `weight` MEDIUMINT(11) NOT NULL DEFAULT 0,
                            `module_auto_id` MEDIUMINT(11) NOT NULL DEFAULT 0,
                            `module_auto_type` TINYINT(4) NOT NULL DEFAULT 0,
                            `auto` TINYINT(4) NOT NULL DEFAULT 0,
                            `visible` TINYINT(4) NOT NULL DEFAULT 0) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `gradebook_book` (
                            `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `gradebook_activity_id` MEDIUMINT(11) NOT NULL,
                            `uid` int(11) NOT NULL DEFAULT 0,
                            `grade` FLOAT NOT NULL DEFAULT -1,
                            `comments` TEXT NOT NULL) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `gradebook_users` (
                            `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `gradebook_id` MEDIUMINT(11) NOT NULL,
                            `uid` int(11) NOT NULL DEFAULT 0) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `attendance` (
                                `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `course_id` INT(11) NOT NULL,
                                `limit` TINYINT(4) NOT NULL DEFAULT 0,
                                `students_semester` TINYINT(4) NOT NULL DEFAULT 1) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `attendance_activities` (
                                `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `attendance_id` MEDIUMINT(11) NOT NULL,
                                `title` VARCHAR(250) DEFAULT NULL,
                                `date` DATETIME DEFAULT NULL,
                                `description` TEXT NOT NULL,
                                `module_auto_id` MEDIUMINT(11) NOT NULL DEFAULT 0,
                                `module_auto_type` TINYINT(4) NOT NULL DEFAULT 0,
                                `auto` TINYINT(4) NOT NULL DEFAULT 0) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `attendance_book` (
                                `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `attendance_activity_id` MEDIUMINT(11) NOT NULL,
                                `uid` int(11) NOT NULL DEFAULT 0,
                                `attend` TINYINT(4) NOT NULL DEFAULT 0,
                                `comments` TEXT NOT NULL) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `attendance_users` (
                                `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `attendance_id` MEDIUMINT(11) NOT NULL,
                                `uid` int(11) NOT NULL DEFAULT 0) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `poll` (
                            `pid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `creator_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `name` VARCHAR(255) NOT NULL DEFAULT '',
                            `creation_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `start_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `end_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `active` INT(11) NOT NULL DEFAULT 0,
                            `description` MEDIUMTEXT NOT NULL,
                            `end_message` MEDIUMTEXT NOT NUll,
                            `anonymized` INT(1) NOT NULL DEFAULT 0)
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `poll_answer_record` (
                            `arid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `pid` INT(11) NOT NULL DEFAULT 0,
                            `qid` INT(11) NOT NULL DEFAULT 0,
                            `aid` INT(11) NOT NULL DEFAULT 0,
                            `answer_text` TEXT NOT NULL,
                            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `submit_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00')
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `poll_question` (
                            `pqid` BIGINT(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `pid` INT(11) NOT NULL DEFAULT 0,
                            `question_text` VARCHAR(250) NOT NULL DEFAULT '',
                            `qtype` tinyint(3) UNSIGNED NOT NULL,
                            `q_position` INT(11) DEFAULT 1,
                            `q_scale` INT(11) NULL DEFAULT NULL)
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `poll_question_answer` (
                            `pqaid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `pqid` INT(11) NOT NULL DEFAULT 0,
                            `answer_text` TEXT NOT NULL)
                            $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `assignment` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `title` VARCHAR(200) NOT NULL DEFAULT '',
                            `description` TEXT NOT NULL,
                            `comments` TEXT NOT NULL,
                            `deadline` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `late_submission` TINYINT NOT NULL DEFAULT '0',
                            `submission_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `active` CHAR(1) NOT NULL DEFAULT 1,
                            `secret_directory` VARCHAR(30) NOT NULL,
                            `group_submissions` CHAR(1) DEFAULT 0 NOT NULL,
                            `max_grade` FLOAT DEFAULT NULL,
                            `assign_to_specific` CHAR(1) DEFAULT '0' NOT NULL,
                            file_path VARCHAR(200) DEFAULT '' NOT NULL,
                            file_name VARCHAR(200) DEFAULT '' NOT NULL)
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `assignment_submit` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `assignment_id` INT(11) NOT NULL DEFAULT 0,
                            `submission_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `submission_ip` VARCHAR(45) NOT NULL DEFAULT '',
                            `file_path` VARCHAR(200) NOT NULL DEFAULT '',
                            `file_name` VARCHAR(200) NOT NULL DEFAULT '',
                            `comments` TEXT NOT NULL,
                            `grade` FLOAT DEFAULT NULL,
                            `grade_comments` TEXT NOT NULL,
                            `grade_submission_date` DATE NOT NULL DEFAULT '1000-10-10',
                            `grade_submission_ip` VARCHAR(45) NOT NULL DEFAULT '',
                            `group_id` INT( 11 ) DEFAULT NULL )
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `assignment_to_specific` (
                            `user_id` int(11) NOT NULL,
                            `group_id` int(11) NOT NULL,
                            `assignment_id` int(11) NOT NULL,
                            PRIMARY KEY (user_id, group_id, assignment_id)
                          ) $charset_spec");
        Database::get()->query("DROP TABLE IF EXISTS agenda");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `agenda` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `title` VARCHAR(200) NOT NULL,
                            `content` TEXT NOT NULL,
                            `start` DATETIME NOT NULL DEFAULT '0000-00-00',
                            `duration` VARCHAR(20) NOT NULL,
                            `visible` TINYINT(4),
                             recursion_period varchar(30) DEFAULT NULL,
                             recursion_end date DEFAULT NULL,
                             `source_event_id` int(11) DEFAULT NULL)
                             $charset_spec");


        Database::get()->query("CREATE TABLE IF NOT EXISTS `exercise` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `title` VARCHAR(250) DEFAULT NULL,
                            `description` TEXT,
                            `type` TINYINT(4) UNSIGNED NOT NULL DEFAULT '1',
                            `start_date` DATETIME DEFAULT NULL,
                            `end_date` DATETIME DEFAULT NULL,
                            `temp_save` TINYINT(1) NOT NULL DEFAULT 0,
                            `time_constraint` INT(11) DEFAULT 0,
                            `attempts_allowed` INT(11) DEFAULT 0,
                            `random` SMALLINT(6) NOT NULL DEFAULT 0,
                            `active` TINYINT(4) NOT NULL DEFAULT 1,
                            `public` TINYINT(4) NOT NULL DEFAULT 1,
                            `results` TINYINT(1) NOT NULL DEFAULT 1,
                            `score` TINYINT(1) NOT NULL DEFAULT 1)
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `exercise_user_record` (
                            `eurid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `eid` INT(11) NOT NULL DEFAULT '0',
                            `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                            `record_start_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `record_end_date` DATETIME DEFAULT NULL,
                            `total_score` FLOAT(5,2) NOT NULL DEFAULT '0',
                            `total_weighting` FLOAT(5,2) DEFAULT '0',
                            `attempt` INT(11) NOT NULL DEFAULT '0',
                            `attempt_status` TINYINT(4) NOT NULL DEFAULT '1',
                            `secs_remaining` INT(11) NOT NULL DEFAULT '0')
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `exercise_answer_record` (
                            `answer_record_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `eurid` int(11) NOT NULL,
                            `question_id` int(11) NOT NULL,
                            `answer` text,
                            `answer_id` int(11) NOT NULL,
                            `weight` float(5,2) DEFAULT NULL,
                            `is_answered` TINYINT NOT NULL DEFAULT '1')
                             $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `exercise_question` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `question` TEXT,
                            `description` TEXT,
                            `weight` FLOAT(11,2) DEFAULT NULL,
                            `q_position` INT(11) DEFAULT 1,
                            `type` INT(11) DEFAULT 1,
                            `difficulty` INT(1) DEFAULT 0,
                            `category` INT(11) DEFAULT 0)
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `exercise_question_cats` (
                            `question_cat_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `question_cat_name` VARCHAR(300) NOT NULL,
                            `course_id` INT(11) NOT NULL)
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `exercise_answer` (
                            `id` INT(11) NOT NULL DEFAULT '0',
                            `question_id` INT(11) NOT NULL DEFAULT '0',
                            `answer` TEXT,
                            `correct` INT(11) DEFAULT NULL,
                            `comment` TEXT,
                            `weight` FLOAT(5,2),
                            `r_position` INT(11) DEFAULT NULL,
                            PRIMARY KEY (id, question_id) )
                            $charset_spec");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `exercise_with_questions` (
                            `question_id` INT(11) NOT NULL DEFAULT '0',
                            `exercise_id` INT(11) NOT NULL DEFAULT '0',
                            PRIMARY KEY (question_id, exercise_id) )");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `course_module` (
                            `id` int(11) NOT NULL auto_increment,
                            `module_id` int(11) NOT NULL,
                            `visible` tinyint(4) NOT NULL,
                            `course_id` int(11) NOT NULL,
                            PRIMARY KEY  (`id`),
                            UNIQUE KEY `module_course` (`module_id`,`course_id`))");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `actions` (
                          `id` int(11) NOT NULL auto_increment,
                          `user_id` int(11) NOT NULL,
                          `module_id` int(11) NOT NULL,
                          `action_type_id` int(11) NOT NULL,
                          `date_time` datetime NOT NULL default '0000-00-00 00:00:00',
                          `duration` int(11) NOT NULL default '900',
                          `course_id` INT(11) NOT NULL,
                          PRIMARY KEY  (`id`),
                          KEY `actionsindex` (`module_id`,`date_time`))");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `actions_summary` (
                          `id` int(11) NOT NULL auto_increment,
                          `module_id` int(11) NOT NULL,
                          `visits` int(11) NOT NULL,
                          `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
                          `end_date` datetime NOT NULL default '0000-00-00 00:00:00',
                          `duration` int(11) NOT NULL,
                          `course_id` INT(11) NOT NULL,
                          PRIMARY KEY  (`id`))");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `logins` (
                          `id` int(11) NOT NULL auto_increment,
                          `user_id` int(11) NOT NULL,
                          `ip` char(45) NOT NULL default '0.0.0.0',
                          `date_time` datetime NOT NULL default '0000-00-00 00:00:00',
                          `course_id` INT(11) NOT NULL,
                          PRIMARY KEY  (`id`))");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `note` (
                         `id` int(11) NOT NULL auto_increment,
                         `user_id` int(11) NOT NULL,
                         `title` varchar(300),
                         `content` text NOT NULL,
                         `date_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                         `order` mediumint(11) NOT NULL default 0,
                         `reference_obj_module` mediumint(11) default NULL,
                         `reference_obj_type` enum('course','personalevent','user','course_ebook','course_event','course_assignment','course_document','course_link','course_exercise','course_learningpath','course_video','course_videolink') default NULL,
                         `reference_obj_id` int(11) default NULL,
                         `reference_obj_course` int(11) default NULL,
                        PRIMARY KEY  (`id`))");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `course_settings` (
                          `setting_id` INT(11) NOT NULL,
                          `course_id` INT(11) NOT NULL,
                          `value` INT(11) NOT NULL DEFAULT 0,
                          PRIMARY KEY (`setting_id`, `course_id`))");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `personal_calendar` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `title` varchar(200) NOT NULL,
                        `content` text NOT NULL,
                        `start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                        `duration` time NOT NULL,
                        `recursion_period` varchar(30) DEFAULT NULL,
                        `recursion_end` date DEFAULT NULL,
                        `source_event_id` int(11) DEFAULT NULL,
                        `reference_obj_module` mediumint(11) DEFAULT NULL,
                        `reference_obj_type` enum('course','personalevent','user','course_ebook','course_event','course_assignment','course_document','course_link','course_exercise','course_learningpath','course_video','course_videolink') DEFAULT NULL,
                        `reference_obj_id` int(11) DEFAULT NULL,
                        `reference_obj_course` int(11) DEFAULT NULL,
                        PRIMARY KEY (`id`))");

        Database::get()->query("CREATE TABLE  IF NOT EXISTS `personal_calendar_settings` (
                        `user_id` int(11) NOT NULL,
                        `view_type` enum('day','month','week') DEFAULT 'month',
                        `personal_color` varchar(30) DEFAULT '#5882fa',
                        `course_color` varchar(30) DEFAULT '#acfa58',
                        `deadline_color` varchar(30) DEFAULT '#fa5882',
                        `admin_color` varchar(30) DEFAULT '#eeeeee',
                        `show_personal` bit(1) DEFAULT b'1',
                        `show_course` bit(1) DEFAULT b'1',
                        `show_deadline` bit(1) DEFAULT b'1',
                        `show_admin` bit(1) DEFAULT b'1',
                        PRIMARY KEY (`user_id`))");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `admin_calendar` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `user_id` int(11) NOT NULL,
                                `title` varchar(200) NOT NULL,
                                `content` text NOT NULL,
                                `start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                                `duration` time NOT NULL,
                                `recursion_period` varchar(30) DEFAULT NULL,
                                `recursion_end` date DEFAULT NULL,
                                `source_event_id` int(11) DEFAULT NULL,
                                `visibility_level` int(11) DEFAULT '1',
                                `email_notification` time DEFAULT NULL,
                                PRIMARY KEY (`id`),
                                KEY `user_events` (`user_id`),
                                KEY `admin_events_dates` (`start`))");

        //create triggers
        Database::get()->query("DROP TRIGGER IF EXISTS personal_calendar_settings_init");
        Database::get()->query("CREATE TRIGGER personal_calendar_settings_init "
                . "AFTER INSERT ON `user` FOR EACH ROW "
                . "INSERT INTO personal_calendar_settings(user_id) VALUES (NEW.id)");
        Database::get()->query("INSERT IGNORE INTO personal_calendar_settings(user_id) SELECT id FROM user");

        // bbb_sessions tables
        Database::get()->query('CREATE TABLE IF NOT EXISTS `bbb_session` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `course_id` int(11) DEFAULT NULL,
                      `title` varchar(255) DEFAULT NULL,
                      `description` text,
                      `start_date` datetime DEFAULT NULL,
                      `public` enum("0","1") DEFAULT NULL,
                      `active` enum("0","1") DEFAULT NULL,
                      `running_at` int(11) DEFAULT NULL,
                      `meeting_id` varchar(255) DEFAULT NULL,
                      `mod_pw` varchar(255) DEFAULT NULL,
                      `att_pw` varchar(255) DEFAULT NULL,
                      `unlock_interval` int(11) DEFAULT NULL,
                      `external_users` varchar(255) DEFAULT "",
                      `participants` varchar(255) DEFAULT "",
                      `record` enum("true","false") DEFAULT "false",
                       `sessionUsers` int(11) DEFAULT 0,
                      PRIMARY KEY (`id`))');

        Database::get()->query('CREATE TABLE IF NOT EXISTS `bbb_servers` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `hostname` varchar(255) DEFAULT NULL,
                        `ip` varchar(255) NOT NULL,
                        `enabled` enum("true","false") DEFAULT NULL,
                        `server_key` varchar(255) DEFAULT NULL,
                        `api_url` varchar(255) DEFAULT NULL,
                        `max_rooms` int(11) DEFAULT NULL,
                        `max_users` int(11) DEFAULT NULL,
                        `enable_recordings` enum("true","false") DEFAULT NULL,
                        `weight` int(11) DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `idx_bbb_servers` (`hostname`))');

        Database::get()->query("CREATE TABLE IF NOT EXISTS `idx_queue` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `course_id` int(11) NOT NULL UNIQUE,
                        PRIMARY KEY (`id`)) $charset_spec");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `idx_queue_async` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `request_type` VARCHAR(255) NOT NULL,
                        `resource_type` VARCHAR(255) NOT NULL,
                        `resource_id` int(11) NOT NULL,
                        PRIMARY KEY (`id`)) $charset_spec");

        Database::get()->query("CREATE TABLE `course_weekly_view` (
                        `id` INT(11) NOT NULL auto_increment,
                        `course_id` INT(11) NOT NULL,
                        `title` VARCHAR(255) NOT NULL DEFAULT '',
                        `comments` MEDIUMTEXT,
                        `start_week` DATE NOT NULL default '0000-00-00',
                        `finish_week` DATE NOT NULL default '0000-00-00',
                        `visible` TINYINT(4) NOT NULL DEFAULT 1,
                        `public` TINYINT(4) NOT NULL DEFAULT 1,
                        `order` INT(11) NOT NULL DEFAULT 0,
                        PRIMARY KEY  (`id`)) $charset_spec");

        Database::get()->query("CREATE TABLE `course_weekly_view_activities` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        `course_weekly_view_id` INT(11) NOT NULL ,
                        `title` VARCHAR(255) NOT NULL DEFAULT '',
                        `comments` MEDIUMTEXT,
                        `res_id` INT(11) NOT NULL,
                        `type` VARCHAR(255) NOT NULL DEFAULT '',
                        `visible` TINYINT(4),
                        `order` INT(11) NOT NULL DEFAULT 0,
                        `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00') $charset_spec");

        // hierarchy tables
        $n = Database::get()->queryArray("SHOW TABLES LIKE 'faculte'");
        $root_node = null;
        $rebuildHierarchy = (count($n) == 1) ? true : false;
        // Whatever code $rebuildHierarchy wraps, can only be executed once.
        // Everything else can be executed several times.

        if ($rebuildHierarchy) {
            Database::get()->query("DROP TABLE IF EXISTS `hierarchy`");
            Database::get()->query("DROP TABLE IF EXISTS `course_department`");
            Database::get()->query("DROP TABLE IF EXISTS `user_department`");
        }

        Database::get()->query("CREATE TABLE IF NOT EXISTS `hierarchy` (
                             `id` INT(11) NOT NULL auto_increment PRIMARY KEY,
                             `code` VARCHAR(20),
                             `name` TEXT NOT NULL,
                             `number` INT(11) NOT NULL DEFAULT 1000,
                             `generator` INT(11) NOT NULL DEFAULT 100,
                             `lft` INT(11) NOT NULL,
                             `rgt` INT(11) NOT NULL,
                             `allow_course` BOOLEAN NOT NULL DEFAULT FALSE,
                             `allow_user` BOOLEAN NOT NULL DEFAULT FALSE,
                             `order_priority` INT(11) DEFAULT NULL,
                             KEY `lftindex` (`lft`),
                             KEY `rgtindex` (`rgt`) ) $charset_spec");

        if ($rebuildHierarchy) {
            // copy faculties into the tree
            $max = Database::get()->querySingle("SELECT MAX(id) as max FROM `faculte`")->max;
            $i = 0;
            Database::get()->queryFunc("SELECT * FROM `faculte`", function ($r) use (&$i, &$max, $langpre, $langpost, $langother) {
                $lft = 2 + 8 * $i;
                $rgt = $lft + 7;
                Database::get()->query("INSERT INTO `hierarchy` (id, code, name, number, generator, lft, rgt, allow_course, allow_user)
                                VALUES (?d, ?s, ?s, ?d, ?d, ?d, ?d, true, true)", $r->id, $r->code, $r->name, $r->number, $r->generator, $lft, $rgt);

                Database::get()->query("INSERT INTO `hierarchy` (id, code, name, lft, rgt, allow_course, allow_user)
                                VALUES (?d, ?s, ?s, ?d, ?d, true, true)", ( ++$max), $r->code, $langpre, ($lft + 1), ($lft + 2));
                Database::get()->query("INSERT INTO `hierarchy` (id, code, name, lft, rgt, allow_course, allow_user)
                                VALUES (?d, ?s, ?s, ?d, ?d, true, true)", ( ++$max), $r->code, $langpost, ($lft + 3), ($lft + 4));
                Database::get()->query("INSERT INTO `hierarchy` (id, code, name, lft, rgt, allow_course, allow_user)
                                VALUES (?d, ?s, ?s, ?d, ?d, true, true)", ( ++$max), $r->code, $langother, ($lft + 5), ($lft + 6));
                $i++;
            });

            $root_rgt = 2 + 8 * intval(Database::get()->querySingle("SELECT COUNT(*) as value FROM `faculte`")->value);
            $rnode = Database::get()->query("INSERT INTO `hierarchy` (code, name, lft, rgt)
                            VALUES ('', ?s, 1, ?d)", $_POST['Institution'], $root_rgt);
            $root_node = $rnode->lastInsertID;
        }

        Database::get()->query("CREATE TABLE IF NOT EXISTS `course_department` (
                             `id` int(11) NOT NULL auto_increment PRIMARY KEY,
                             `course` int(11) NOT NULL references course(id),
                             `department` int(11) NOT NULL references hierarchy(id) )");

        if ($rebuildHierarchy) {
            Database::get()->queryFunc("SELECT cours_id, faculteid, type FROM `cours`", function ($r) use($langpre, $langpost, $langother) {
                // take care of courses with not type
                if (!empty($r->type) && strlen($r->type) > 0) {
                    $qlike = ${'lang' . $r->type};
                } else {
                    $qlike = $langother;
                }
                // take care of courses with no parent
                if (!empty($r->faculteid)) {
                    $qfaculteid = $r->faculteid;
                } else {
                    $qfaculteid = $root_node;
                }

                $node = Database::get()->querySingle("SELECT node.id FROM `hierarchy` AS node, `hierarchy` AS parent
                                            WHERE node.name LIKE ?s AND
                                                  parent.id = ?d AND
                                                  node.lft BETWEEN parent.lft AND parent.rgt", $qlike, $qfaculteid);
                if ($node) {
                    Database::get()->query("INSERT INTO `course_department` (course, department) VALUES (?d, ?d)", $r->cours_id, $node->id);
                }
            });
        }

        Database::get()->query("CREATE TABLE IF NOT EXISTS `user_department` (
                             `id` int(11) NOT NULL auto_increment PRIMARY KEY,
                             `user` mediumint(8) unsigned NOT NULL references user(user_id),
                             `department` int(11) NOT NULL references hierarchy(id) )");

        if ($rebuildHierarchy) {
            Database::get()->queryFunc("SELECT id, department FROM `user` WHERE department IS NOT NULL", function ($r) {
                Database::get()->query("INSERT INTO `user_department` (user, department) VALUES(?d, ?d)", $r->id, $r->department);
            });
        }

        if ($rebuildHierarchy) {
            // drop old way of referencing course type and course faculty
            Database::get()->query("ALTER TABLE `user` DROP COLUMN department");
            Database::get()->query("DROP TABLE IF EXISTS `faculte`");
        }

        // hierarchy stored procedures
        Database::get()->query("DROP VIEW IF EXISTS `hierarchy_depth`");
        Database::get()->query("CREATE VIEW `hierarchy_depth` AS
                                SELECT node.id, node.code, node.name, node.number, node.generator,
                                       node.lft, node.rgt, node.allow_course, node.allow_user,
                                       node.order_priority, COUNT(parent.id) - 1 AS depth
                                FROM hierarchy AS node,
                                     hierarchy AS parent
                                WHERE node.lft BETWEEN parent.lft AND parent.rgt
                                GROUP BY node.id
                                ORDER BY node.lft");

        Database::get()->query("DROP PROCEDURE IF EXISTS `add_node`");
        Database::get()->query("CREATE PROCEDURE `add_node` (IN name VARCHAR(255), IN parentlft INT(11),
                                    IN p_code VARCHAR(10), IN p_allow_course BOOLEAN, IN p_allow_user BOOLEAN,
                                    IN p_order_priority INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    DECLARE lft, rgt INT(11);

                                    SET lft = parentlft + 1;
                                    SET rgt = parentlft + 2;

                                    CALL shift_right(parentlft, 2, 0);

                                    INSERT INTO `hierarchy` (name, lft, rgt, code, allow_course, allow_user, order_priority) VALUES (name, lft, rgt, p_code, p_allow_course, p_allow_user, p_order_priority);
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `add_node_ext`");
        Database::get()->query("CREATE PROCEDURE `add_node_ext` (IN name VARCHAR(255), IN parentlft INT(11),
                                    IN p_code VARCHAR(10), IN p_number INT(11), IN p_generator INT(11),
                                    IN p_allow_course BOOLEAN, IN p_allow_user BOOLEAN, IN p_order_priority INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    DECLARE lft, rgt INT(11);

                                    SET lft = parentlft + 1;
                                    SET rgt = parentlft + 2;

                                    CALL shift_right(parentlft, 2, 0);

                                    INSERT INTO `hierarchy` (name, lft, rgt, code, number, generator, allow_course, allow_user, order_priority) VALUES (name, lft, rgt, p_code, p_number, p_generator, p_allow_course, p_allow_user, p_order_priority);
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `update_node`");
        Database::get()->query("CREATE PROCEDURE `update_node` (IN p_id INT(11), IN p_name VARCHAR(255),
                                    IN nodelft INT(11), IN p_lft INT(11), IN p_rgt INT(11), IN parentlft INT(11),
                                    IN p_code VARCHAR(10), IN p_allow_course BOOLEAN, IN p_allow_user BOOLEAN, IN p_order_priority INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    UPDATE `hierarchy` SET name = p_name, lft = p_lft, rgt = p_rgt,
                                        code = p_code, allow_course = p_allow_course, allow_user = p_allow_user,
                                        order_priority = p_order_priority WHERE id = p_id;

                                    IF nodelft <> parentlft THEN
                                        CALL move_nodes(nodelft, p_lft, p_rgt);
                                    END IF;
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `delete_node`");
        Database::get()->query("CREATE PROCEDURE `delete_node` (IN p_id INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    DECLARE p_lft, p_rgt INT(11);

                                    SELECT lft, rgt INTO p_lft, p_rgt FROM `hierarchy` WHERE id = p_id;
                                    DELETE FROM `hierarchy` WHERE id = p_id;

                                    CALL delete_nodes(p_lft, p_rgt);
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `shift_right`");
        Database::get()->query("CREATE PROCEDURE `shift_right` (IN node INT(11), IN shift INT(11), IN maxrgt INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    IF maxrgt > 0 THEN
                                        UPDATE `hierarchy` SET rgt = rgt + shift WHERE rgt > node AND rgt <= maxrgt;
                                    ELSE
                                        UPDATE `hierarchy` SET rgt = rgt + shift WHERE rgt > node;
                                    END IF;

                                    IF maxrgt > 0 THEN
                                        UPDATE `hierarchy` SET lft = lft + shift WHERE lft > node AND lft <= maxrgt;
                                    ELSE
                                        UPDATE `hierarchy` SET lft = lft + shift WHERE lft > node;
                                    END IF;
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `shift_left`");
        Database::get()->query("CREATE PROCEDURE `shift_left` (IN node INT(11), IN shift INT(11), IN maxrgt INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    IF maxrgt > 0 THEN
                                        UPDATE `hierarchy` SET rgt = rgt - shift WHERE rgt > node AND rgt <= maxrgt;
                                    ELSE
                                        UPDATE `hierarchy` SET rgt = rgt - shift WHERE rgt > node;
                                    END IF;

                                    IF maxrgt > 0 THEN
                                        UPDATE `hierarchy` SET lft = lft - shift WHERE lft > node AND lft <= maxrgt;
                                    ELSE
                                        UPDATE `hierarchy` SET lft = lft - shift WHERE lft > node;
                                    END IF;
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `shift_end`");
        Database::get()->query("CREATE PROCEDURE `shift_end` (IN p_lft INT(11), IN p_rgt INT(11), IN maxrgt INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    UPDATE `hierarchy`
                                    SET lft = (lft - (p_lft - 1)) + maxrgt,
                                        rgt = (rgt - (p_lft - 1)) + maxrgt WHERE lft BETWEEN p_lft AND p_rgt;
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `get_maxrgt`");
        Database::get()->query("CREATE PROCEDURE `get_maxrgt` (OUT maxrgt INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    SELECT rgt INTO maxrgt FROM `hierarchy` ORDER BY rgt DESC LIMIT 1;
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `get_parent`");
        Database::get()->query("CREATE PROCEDURE `get_parent` (IN p_lft INT(11), IN p_rgt INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    SELECT * FROM `hierarchy` WHERE lft < p_lft AND rgt > p_rgt ORDER BY lft DESC LIMIT 1;
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `delete_nodes`");
        Database::get()->query("CREATE PROCEDURE `delete_nodes` (IN p_lft INT(11), IN p_rgt INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    DECLARE node_width INT(11);
                                    SET node_width = p_rgt - p_lft + 1;

                                    DELETE FROM `hierarchy` WHERE lft BETWEEN p_lft AND p_rgt;
                                    UPDATE `hierarchy` SET rgt = rgt - node_width WHERE rgt > p_rgt;
                                    UPDATE `hierarchy` SET lft = lft - node_width WHERE lft > p_lft;
                                END");

        Database::get()->query("DROP PROCEDURE IF EXISTS `move_nodes`");
        Database::get()->query("CREATE PROCEDURE `move_nodes` (INOUT nodelft INT(11), IN p_lft INT(11), IN p_rgt INT(11))
                                LANGUAGE SQL
                                BEGIN
                                    DECLARE node_width, maxrgt INT(11);

                                    SET node_width = p_rgt - p_lft + 1;
                                    CALL get_maxrgt(maxrgt);

                                    CALL shift_end(p_lft, p_rgt, maxrgt);

                                    IF nodelft = 0 THEN
                                        CALL shift_left(p_rgt, node_width, 0);
                                    ELSE
                                        CALL shift_left(p_rgt, node_width, maxrgt);

                                        IF p_lft < nodelft THEN
                                            SET nodelft = nodelft - node_width;
                                        END IF;

                                        CALL shift_right(nodelft, node_width, maxrgt);

                                        UPDATE `hierarchy` SET rgt = (rgt - maxrgt) + nodelft WHERE rgt > maxrgt;
                                        UPDATE `hierarchy` SET lft = (lft - maxrgt) + nodelft WHERE lft > maxrgt;
                                    END IF;
                                END");

        // Update ip-containing fields to support IPv6 addresses
        Database::get()->query("ALTER TABLE `log` CHANGE COLUMN `ip` `ip` VARCHAR(45)");
        Database::get()->query("ALTER TABLE `login_failure` CHANGE COLUMN `ip` `ip` VARCHAR(45)");
        Database::get()->query("ALTER TABLE `loginout` CHANGE `ip` `ip` CHAR(45) NOT NULL DEFAULT '0.0.0.0'");
        Database::get()->query("ALTER TABLE `log_archive` CHANGE COLUMN `ip` `ip` VARCHAR(45)");
        Database::get()->query("ALTER TABLE `assignment_submit`
                            CHANGE COLUMN `submission_ip` `submission_ip` VARCHAR(45)");
        Database::get()->query("ALTER TABLE `assignment_submit`
                            CHANGE COLUMN `grade_submission_ip` `grade_submission_ip` VARCHAR(45)");
        Database::get()->query("ALTER TABLE `forum_post`
                            CHANGE COLUMN `poster_ip` `poster_ip` VARCHAR(45)");
        Database::get()->query("ALTER TABLE `logins` CHANGE COLUMN `ip` `ip` VARCHAR(45)");

        // There is a special case with user_request storing its IP in numeric format

        $fields_user_request = Database::get()->queryArray("SHOW COLUMNS FROM user_request");
        foreach ($fields_user_request as $row2) {
            if ($row2->Field == "ip_address") {
                Database::get()->query("ALTER TABLE `user_request` ADD `request_ip` varchar(45) NOT NULL DEFAULT ''");
                Database::get()->queryFunc("SELECT id,INET_NTOA(ip_address) as ip_addr FROM user_request", function ($row) {
                    Database::get()->query("UPDATE `user_request` SET `request_ip` = ?s WHERE `id` = ?s", $row->ip_addr, $row->id);
                });
                Database::get()->query("ALTER TABLE `user_request` DROP `ip_address`");
                break;
            }
        }
    }

    // Rename table `cours` to `course` and `cours_user` to `course_user`
    if (!DBHelper::tableExists('course')) {
        DBHelper::fieldExists('cours', 'expand_glossary') or
                Database::get()->query("ALTER TABLE `cours` ADD `expand_glossary` BOOL NOT NULL DEFAULT 0");
        DBHelper::fieldExists('cours', 'glossary_index') or
                Database::get()->query("ALTER TABLE `cours` ADD `glossary_index` BOOL NOT NULL DEFAULT 1");
        Database::get()->query("RENAME TABLE `cours` TO `course`");
        Database::get()->query("UPDATE course SET description = '' WHERE description IS NULL");
        Database::get()->query("UPDATE course SET course_keywords = '' WHERE course_keywords IS NULL");
        if (DBHelper::fieldExists('course', 'course_objectives')) {
            Database::get()->query("ALTER TABLE course DROP COLUMN `course_objectives`,
                                                DROP COLUMN `course_prerequisites`,
                                                DROP COLUMN `course_references`");
        }
        Database::get()->query("ALTER TABLE course CHANGE `cours_id` `id` INT(11) NOT NULL AUTO_INCREMENT,
                                             CHANGE `languageCourse` `lang` VARCHAR(16) DEFAULT 'el',
                                             CHANGE `intitule` `title` VARCHAR(250) NOT NULL DEFAULT '',
                                             CHANGE `description` `description` MEDIUMTEXT NOT NULL,
                                             CHANGE `course_keywords` `keywords` TEXT NOT NULL,
                                             DROP COLUMN `course_addon`,
                                             CHANGE `titulaires` `prof_names` varchar(200) NOT NULL DEFAULT '',
                                             CHANGE `fake_code` `public_code` varchar(20) NOT NULL DEFAULT '',
                                             DROP COLUMN `departmentUrlName`,
                                             DROP COLUMN `departmentUrl`,
                                             DROP COLUMN `lastVisit`,
                                             DROP COLUMN `lastEdit`,
                                             DROP COLUMN `expirationDate`,
                                             DROP COLUMN `type`,
                                             DROP COLUMN `faculteid`,
                                             CHANGE `first_create` `created` datetime NOT NULL default '0000-00-00 00:00:00',
                                             CHANGE `expand_glossary` `glossary_expand` BOOL NOT NULL DEFAULT 0,
                                             ADD `view_type` VARCHAR(255) NOT NULL DEFAULT 'units',
                                             ADD `start_date` DATE NOT NULL default '0000-00-00',
                                             ADD `finish_date` DATE NOT NULL default '0000-00-00',
                                             DROP INDEX cours");
        Database::get()->queryFunc("SELECT DISTINCT lang from course", function ($old_lang) {
            Database::get()->query("UPDATE course SET lang = ?s WHERE lang = ?s", langname_to_code($old_lang->lang), $old_lang->lang);
        });
        Database::get()->query("RENAME TABLE `cours_user` TO `course_user`");
        Database::get()->query('ALTER TABLE `course_user`
                                            CHANGE `statut` `status` TINYINT(4) NOT NULL DEFAULT 0,
                                            CHANGE `cours_id` `course_id` INT(11) NOT NULL DEFAULT 0');
        if (DBHelper::fieldExists('course_user', 'code_cours')) {
            Database::get()->query('ALTER TABLE `course_user`
                                        DROP COLUMN `code_cours`');
        }
    }

    DBHelper::fieldExists('ebook', 'visible') or
            Database::get()->query("ALTER TABLE `ebook` ADD `visible` BOOL NOT NULL DEFAULT 1");
    DBHelper::fieldExists('admin', 'privilege') or
            Database::get()->query("ALTER TABLE `admin` ADD `privilege` INT NOT NULL DEFAULT '0'");
    DBHelper::fieldExists('course_user', 'editor') or
            Database::get()->query("ALTER TABLE `course_user` ADD `editor` INT NOT NULL DEFAULT '0' AFTER `tutor`");
    if (!DBHelper::fieldExists('glossary', 'category_id')) {
        Database::get()->query("ALTER TABLE glossary
                                ADD category_id INT(11) DEFAULT NULL,
                                ADD notes TEXT NOT NULL");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `glossary_category` (
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `course_id` INT(11) NOT NULL,
                                `name` VARCHAR(255) NOT NULL,
                                `description` TEXT NOT NULL,
                                `order` INT(11) NOT NULL DEFAULT 0)");
    }

    Database::get()->query("CREATE TABLE IF NOT EXISTS `actions_daily` (
                `id` int(11) NOT NULL auto_increment,
                `user_id` int(11) NOT NULL,
                `module_id` int(11) NOT NULL,
                `course_id` int(11) NOT NULL,
                `hits` int(11) NOT NULL,
                `duration` int(11) NOT NULL,
                `day` date NOT NULL,
                `last_update` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `actionsdailyindex` (`module_id`, `day`),
                KEY `actionsdailyuserindex` (`user_id`),
                KEY `actionsdailydayindex` (`day`),
                KEY `actionsdailymoduleindex` (`module_id`),
                KEY `actionsdailycourseindex` (`course_id`) )");

    Database::get()->query("ALTER TABLE monthly_summary CHANGE details details MEDIUMTEXT");

    // drop stale full text indexes
    if (DBHelper::indexExists('document', 'document')) {
        Database::get()->query("ALTER TABLE document DROP INDEX document");
    }
    if (DBHelper::indexExists('course_units', 'course_units_title')) {
        Database::get()->query("ALTER TABLE course_units DROP INDEX course_units_title");
    }
    if (DBHelper::indexExists('course_units', 'course_units_comments')) {
        Database::get()->query("ALTER TABLE course_units DROP INDEX course_units_comments");
    }
    if (DBHelper::indexExists('unit_resources', 'unit_resources_title')) {
        Database::get()->query("ALTER TABLE unit_resources DROP INDEX unit_resources_title");
    }

    // // ----------------------------------
    // creation of indexes
    // ----------------------------------
    updateInfo(-1, $langIndexCreation);

    DBHelper::indexExists('actions_daily', 'actions_daily_index') or
            Database::get()->query("CREATE INDEX `actions_daily_index` ON actions_daily(user_id, module_id, course_id)");
    DBHelper::indexExists('actions_summary', 'actions_summary_index') or
            Database::get()->query("CREATE INDEX `actions_summary_index` ON actions_summary(module_id, course_id)");
    DBHelper::indexExists('admin', 'admin_index') or
            Database::get()->query("CREATE INDEX `admin_index` ON admin(user_id)");
    DBHelper::indexExists('agenda', 'agenda_index') or
            Database::get()->query("CREATE INDEX `agenda_index` ON agenda(course_id)");
    DBHelper::indexExists('announcement', 'ann_index') or
            Database::get()->query("CREATE INDEX `ann_index` ON announcement(course_id)");
    DBHelper::indexExists('assignment', 'assignment_index') or
            Database::get()->query("CREATE INDEX `assignment_index` ON assignment(course_id)");
    DBHelper::indexExists('assignment_submit', 'assign_submit_index') or
            Database::get()->query("CREATE INDEX `assign_submit_index` ON assignment_submit(uid, assignment_id)");
    DBHelper::indexExists('assignment_to_specific', 'assign_spec_index') or
            Database::get()->query("CREATE INDEX `assign_spec_index` ON assignment_to_specific(user_id)");
    DBHelper::indexExists('attendance', 'att_index') or
            Database::get()->query("CREATE INDEX `att_index` ON attendance(course_id)");
    DBHelper::indexExists('attendance_activities', 'att_act_index') or
            Database::get()->query("CREATE INDEX `att_act_index` ON attendance_activities(attendance_id)");
    DBHelper::indexExists('attendance_book', 'att_book_index') or
            Database::get()->query("CREATE INDEX `att_book_index` ON attendance_book(attendance_activity_id)");
    DBHelper::indexExists('bbb_session', 'bbb_index') or
            Database::get()->query("CREATE INDEX `bbb_index` ON bbb_session(course_id)");
    DBHelper::indexExists('course', 'course_index') or
            Database::get()->query("CREATE INDEX `course_index` ON course(code)");
    DBHelper::indexExists('course_department', 'cdep_index') or
            Database::get()->query("CREATE INDEX `cdep_index` ON course_department(course, department)");
    DBHelper::indexExists('course_description', 'cd_type_index') or
            Database::get()->query('CREATE INDEX `cd_type_index` ON course_description(`type`)');
    DBHelper::indexExists('course_description', 'cd_cid_type_index') or
            Database::get()->query('CREATE INDEX `cd_cid_type_index` ON course_description (course_id, `type`)');
    DBHelper::indexExists('course_description', 'cid') or
            Database::get()->query('CREATE INDEX `cid` ON course_description (course_id)');
    DBHelper::indexExists('course_module', 'visible_cid') or
            Database::get()->query('CREATE INDEX `visible_cid` ON course_module (visible, course_id)');
    DBHelper::indexExists('course_review', 'crev_index') or
            Database::get()->query("CREATE INDEX `crev_index` ON course_review(course_id)");
    DBHelper::indexExists('course_units', 'course_units_index') or
            Database::get()->query('CREATE INDEX `course_units_index` ON course_units (course_id, `order`)');
    DBHelper::indexExists('course_user', 'cu_index') or
            Database::get()->query("CREATE INDEX `cu_index` ON course_user (course_id, user_id, status)");
    DBHelper::indexExists('document', 'doc_path_index') or
            Database::get()->query('CREATE INDEX `doc_path_index` ON document (course_id, subsystem,path)');
    DBHelper::indexExists('dropbox_attachment', 'drop_att_index') or
            Database::get()->query("CREATE INDEX `drop_att_index` ON dropbox_attachment(msg_id)");
    DBHelper::indexExists('dropbox_index', 'drop_index') or
            Database::get()->query("CREATE INDEX `drop_index` ON dropbox_index(recipient_id, is_read)");
    DBHelper::indexExists('dropbox_msg', 'drop_msg_index') or
            Database::get()->query("CREATE INDEX `drop_msg_index` ON dropbox_msg(course_id, author_id)");
    DBHelper::indexExists('ebook', 'ebook_index') or
            Database::get()->query("CREATE INDEX `ebook_index` ON ebook(course_id)");
    DBHelper::indexExists('ebook_section', 'ebook_sec_index') or
            Database::get()->query("CREATE INDEX `ebook_sec_index` ON ebook_section(ebook_id)");
    DBHelper::indexExists('ebook_subsection', 'ebook_sub_sec_index') or
            Database::get()->query("CREATE INDEX `ebook_sub_sec_index` ON ebook_subsection(section_id)");
    DBHelper::indexExists('exercise', 'exer_index') or
            Database::get()->query('CREATE INDEX `exer_index` ON exercise (course_id)');
    DBHelper::indexExists('exercise_user_record', 'eur_index1') or
            Database::get()->query('CREATE INDEX `eur_index1` ON exercise_user_record (eid)');
    DBHelper::indexExists('exercise_user_record', 'eur_index2') or
            Database::get()->query('CREATE INDEX `eur_index2` ON exercise_user_record (uid)');
    DBHelper::indexExists('exercise_answer_record', 'ear_index1') or
            Database::get()->query('CREATE INDEX `ear_index1` ON exercise_answer_record (eurid)');
    DBHelper::indexExists('exercise_answer_record', 'ear_index2') or
            Database::get()->query('CREATE INDEX `ear_index2` ON exercise_answer_record (question_id)');
    DBHelper::indexExists('exercise_question', 'eq_index') or
            Database::get()->query('CREATE INDEX `eq_index` ON exercise_question (course_id)');
    DBHelper::indexExists('exercise_answer', 'ea_index') or
            Database::get()->query('CREATE INDEX `ea_index` ON exercise_answer (question_id)');
    DBHelper::indexExists('forum', 'for_index') or
            Database::get()->query("CREATE INDEX `for_index` ON forum(course_id)");
    DBHelper::indexExists('forum_category', 'for_cat_index') or
            Database::get()->query("CREATE INDEX `for_cat_index` ON forum_category(course_id)");
    DBHelper::indexExists('forum_notify', 'for_not_index') or
            Database::get()->query("CREATE INDEX `for_not_index` ON forum_notify(course_id)");
    DBHelper::indexExists('forum_post', 'for_post_index') or
            Database::get()->query("CREATE INDEX `for_post_index` ON forum_post(topic_id)");
    DBHelper::indexExists('forum_topic', 'for_topic_index') or
            Database::get()->query("CREATE INDEX `for_topic_index` ON forum_topic(forum_id)");
    DBHelper::indexExists('glossary', 'glos_index') or
            Database::get()->query("CREATE INDEX `glos_index` ON glossary(course_id)");
    DBHelper::indexExists('glossary_category', 'glos_cat_index') or
            Database::get()->query("CREATE INDEX `glos_cat_index` ON glossary_category(course_id)");
    DBHelper::indexExists('gradebook', 'grade_index') or
            Database::get()->query("CREATE INDEX `grade_index` ON gradebook(course_id)");
    DBHelper::indexExists('gradebook_activities', 'grade_act_index') or
            Database::get()->query("CREATE INDEX `grade_act_index` ON gradebook_activities(gradebook_id)");
    DBHelper::indexExists('gradebook_book', 'grade_book_index') or
            Database::get()->query("CREATE INDEX `grade_book_index` ON gradebook_book(gradebook_activity_id)");
    DBHelper::indexExists('group', 'group_index') or
            Database::get()->query("CREATE INDEX `group_index` ON `group`(course_id)");
    DBHelper::indexExists('group_properties', 'gr_prop_index') or
            Database::get()->query("CREATE INDEX `gr_prop_index` ON group_properties(course_id)");
    DBHelper::indexExists('hierarchy', 'hier_index') or
            Database::get()->query("CREATE INDEX `hier_index` ON hierarchy(code,name(20))");
    DBHelper::indexExists('link', 'link_index') or
            Database::get()->query("CREATE INDEX `link_index` ON link(course_id)");
    DBHelper::indexExists('link_category', 'link_cat_index') or
            Database::get()->query("CREATE INDEX `link_cat_index` ON link_category(course_id)");
    DBHelper::indexExists('log', 'cmid') or
            Database::get()->query('CREATE INDEX `cmid` ON log (course_id, module_id)');
    DBHelper::indexExists('logins', 'logins_id') or
            Database::get()->query("CREATE INDEX `logins_id` ON logins(user_id, course_id)");
    DBHelper::indexExists('loginout', 'loginout_id') or
            Database::get()->query("CREATE INDEX `loginout_id` ON loginout(id_user)");
    DBHelper::indexExists('lp_asset', 'lp_as_id') or
            Database::get()->query("CREATE INDEX `lp_as_id` ON lp_asset(module_id)");
    DBHelper::indexExists('lp_learnPath', 'lp_id') or
            Database::get()->query("CREATE INDEX `lp_id` ON lp_learnPath(course_id)");
    DBHelper::indexExists('lp_module', 'lp_mod_id') or
            Database::get()->query("CREATE INDEX `lp_mod_id` ON lp_module(course_id)");
    DBHelper::indexExists('lp_rel_learnPath_module', 'lp_rel_lp_id') or
            Database::get()->query("CREATE INDEX `lp_rel_lp_id` ON lp_rel_learnPath_module(learnPath_id, module_id)");
    DBHelper::indexExists('lp_user_module_progress', 'optimize') or
            Database::get()->query("CREATE INDEX `optimize` ON lp_user_module_progress (user_id, learnPath_module_id)");
    DBHelper::indexExists('oai_record', 'cid') or
            Database::get()->query('CREATE INDEX `cid` ON oai_record (course_id)');
    DBHelper::indexExists('oai_record', 'oaiid') or
            Database::get()->query('CREATE INDEX `oaiid` ON oai_record (oai_identifier)');
    DBHelper::indexExists('poll', 'poll_index') or
            Database::get()->query("CREATE INDEX `poll_index` ON poll(course_id)");
    DBHelper::indexExists('poll_answer_record', 'poll_ans_id') or
            Database::get()->query("CREATE INDEX `poll_ans_id` ON poll_answer_record(pid, user_id)");
    DBHelper::indexExists('poll_question', 'poll_q_id') or
            Database::get()->query("CREATE INDEX `poll_q_id` ON poll_question(pid)");
    DBHelper::indexExists('poll_question_answer', 'poll_qa_id') or
            Database::get()->query("CREATE INDEX `poll_qa_id` ON poll_question_answer(pqid)");
    DBHelper::indexExists('unit_resources', 'unit_res_index') or
            Database::get()->query('CREATE INDEX `unit_res_index` ON unit_resources (unit_id, visibility,res_id)');
    DBHelper::indexExists('user', 'u_id') or
            Database::get()->query("CREATE INDEX `u_id` ON user(username)");
    DBHelper::indexExists('user_department', 'udep_id') or
            Database::get()->query("CREATE INDEX `udep_id` ON user_department(user, department)");
    DBHelper::indexExists('video', 'cid') or
            Database::get()->query('CREATE INDEX `cid` ON video (course_id)');
    DBHelper::indexExists('videolink', 'cid') or
            Database::get()->query('CREATE INDEX `cid` ON videolink (course_id)');
    DBHelper::indexExists('wiki_locks', 'wiki_id') or
            Database::get()->query("CREATE INDEX `wiki_id` ON wiki_locks(wiki_id)");
    DBHelper::indexExists('wiki_pages', 'wiki_pages_id') or
            Database::get()->query("CREATE INDEX `wiki_pages_id` ON wiki_pages(wiki_id)");
    DBHelper::indexExists('wiki_pages_content', 'wiki_pcon_id') or
            Database::get()->query("CREATE INDEX `wiki_pcon_id` ON wiki_pages_content(pid)");
    DBHelper::indexExists('wiki_properties', 'wik_prop_id') or
            Database::get()->query("CREATE INDEX `wik_prop_id` ON  wiki_properties(course_id)");
    DBHelper::indexExists('idx_queue', 'idx_queue_cid') or
            Database::get()->query("CREATE INDEX `idx_queue_cid` ON `idx_queue` (course_id)");
    DBHelper::indexExists('idx_queue_async', 'idx_queue_async_uid') or
            Database::get()->query("CREATE INDEX `idx_queue_async_uid` ON idx_queue_async(user_id)");

    DBHelper::indexExists('attendance_users', 'attendance_users_aid') or
            Database::get()->query('CREATE INDEX `attendance_users_aid` ON `attendance_users` (attendance_id)');
    DBHelper::indexExists('gradebook_users', 'gradebook_users_gid') or
            Database::get()->query('CREATE INDEX `gradebook_users_gid` ON `gradebook_users` (gradebook_id)');

    DBHelper::indexExists('actions_daily', 'actions_daily_mcd') or
            Database::get()->query('CREATE INDEX `actions_daily_mcd` ON `actions_daily` (module_id, course_id, day)');
    DBHelper::indexExists('actions_daily', 'actions_daily_hdi') or
            Database::get()->query('CREATE INDEX `actions_daily_hdi` ON `actions_daily` (hits, duration, id)');
    DBHelper::indexExists('loginout', 'loginout_ia') or
            Database::get()->query('CREATE INDEX `loginout_ia` ON `loginout` (id_user, action)');
    DBHelper::indexExists('announcement', 'announcement_cvo') or
            Database::get()->query('CREATE INDEX `announcement_cvo` ON `announcement` (course_id, visible, `order`)');

    DBHelper::indexExists('actions_summary', 'actions_summary_module_id') or
            Database::get()->query("CREATE INDEX `actions_summary_module_id` ON actions_summary(module_id)");
    DBHelper::indexExists('actions_summary', 'actions_summary_course_id') or
            Database::get()->query("CREATE INDEX `actions_summary_course_id` ON actions_summary(course_id)");

    DBHelper::indexExists('document', 'doc_course_id') or
            Database::get()->query('CREATE INDEX `doc_course_id` ON document (course_id)');
    DBHelper::indexExists('document', 'doc_subsystem') or
            Database::get()->query('CREATE INDEX `doc_subsystem` ON document (subsystem)');
    DBHelper::indexExists('document', 'doc_path') or
            Database::get()->query('CREATE INDEX `doc_path` ON document (path)');

    DBHelper::indexExists('dropbox_index', 'drop_index_recipient_id') or
            Database::get()->query("CREATE INDEX `drop_index_recipient_id` ON dropbox_index(recipient_id)");
    DBHelper::indexExists('dropbox_index', 'drop_index_recipient_id') or
            Database::get()->query("CREATE INDEX `drop_index_is_read` ON dropbox_index(is_read)");

    DBHelper::indexExists('dropbox_msg', 'drop_msg_index_course_id') or
            Database::get()->query("CREATE INDEX `drop_msg_index_course_id` ON dropbox_msg(course_id)");
    DBHelper::indexExists('dropbox_msg', 'drop_msg_index_author_id') or
            Database::get()->query("CREATE INDEX `drop_msg_index_author_id` ON dropbox_msg(author_id)");

    DBHelper::indexExists('exercise_with_questions', 'ewq_index_question_id') or
            Database::get()->query('CREATE INDEX `ewq_index_question_id` ON exercise_with_questions (question_id)');
    DBHelper::indexExists('exercise_with_questions', 'ewq_index_exercise_id') or
            Database::get()->query('CREATE INDEX `ewq_index_exercise_id` ON exercise_with_questions (exercise_id)');

    DBHelper::indexExists('group_members', 'gr_mem_user_id') or
            Database::get()->query("CREATE INDEX `gr_mem_user_id` ON group_members(user_id)");
    DBHelper::indexExists('group_members', 'gr_mem_group_id') or
            Database::get()->query("CREATE INDEX `gr_mem_group_id` ON group_members(group_id)");

    DBHelper::indexExists('log', 'log_course_id') or
            Database::get()->query("CREATE INDEX `log_course_id` ON log (course_id)");
    DBHelper::indexExists('log', 'log_module_id') or
            Database::get()->query("CREATE INDEX `log_module_id` ON log (module_id)");

    DBHelper::indexExists('logins', 'logins_id_user_id') or
            Database::get()->query("CREATE INDEX `logins_id_user_id` ON logins(user_id)");
    DBHelper::indexExists('logins', 'logins_id_course_id') or
            Database::get()->query("CREATE INDEX `logins_id_course_id` ON logins(course_id)");

    DBHelper::indexExists('lp_rel_learnPath_module', 'lp_rel_learnPath_id') or
            Database::get()->query("CREATE INDEX `lp_rel_learnPath_id` ON lp_rel_learnPath_module(learnPath_id)");
    DBHelper::indexExists('lp_rel_learnPath_module', 'lp_rel_learnPath_id') or
            Database::get()->query("CREATE INDEX `lp_rel_module_id` ON lp_rel_learnPath_module(module_id)");

    DBHelper::indexExists('lp_user_module_progress', 'lp_learnPath_module_id') or
            Database::get()->query("CREATE INDEX `lp_learnPath_module_id` ON lp_user_module_progress (learnPath_module_id)");
    DBHelper::indexExists('lp_user_module_progress', 'lp_user_id') or
            Database::get()->query("CREATE INDEX `lp_user_id` ON lp_user_module_progress (user_id)");

    DBHelper::indexExists('poll_answer_record', 'poll_ans_id_user_id') or
            Database::get()->query("CREATE INDEX `poll_ans_id_user_id` ON poll_answer_record(user_id)");
    DBHelper::indexExists('poll_answer_record', 'poll_ans_id_user_id') or
            Database::get()->query("CREATE INDEX `poll_ans_id_pid` ON poll_answer_record(pid)");

    DBHelper::indexExists('unit_resources', 'unit_res_unit_id') or
            Database::get()->query("CREATE INDEX `unit_res_unit_id` ON unit_resources (unit_id)");
    DBHelper::indexExists('unit_resources', 'unit_res_visible') or
            Database::get()->query("CREATE INDEX `unit_res_visible` ON unit_resources (visible)");
    DBHelper::indexExists('unit_resources', 'unit_res_res_id') or
            Database::get()->query("CREATE INDEX `unit_res_res_id` ON unit_resources (res_id)");

    DBHelper::indexExists('personal_calendar', 'pcal_start') or
            Database::get()->query('CREATE INDEX `pcal_start` ON personal_calendar (start)');

    DBHelper::indexExists('agenda', 'agenda_start') or
            Database::get()->query('CREATE INDEX `agenda_start` ON agenda (start)');

    DBHelper::indexExists('assignment', 'assignment_deadline') or
            Database::get()->query('CREATE INDEX `assignment_deadline` ON assignment (deadline)');

    // **********************************************
    // upgrade courses databases
    // **********************************************
    $res = Database::get()->queryArray("SELECT id, code, lang FROM course ORDER BY code");
    $total = count($res);
    $i = 1;
    foreach ($res as $row) {
        updateInfo($i / ($total + 1), $langUpgCourse);

        if (version_compare($oldversion, '2.2', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 2.2");
            upgrade_course_2_2($row->code, $row->lang);
        }
        if (version_compare($oldversion, '2.3', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 2.3");
            upgrade_course_2_3($row->code);
        }
        if (version_compare($oldversion, '2.4', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 2.4");
            upgrade_course_index_php($row->code);
            upgrade_course_2_4($row->code, $row->id, $row->lang);
        }
        if (version_compare($oldversion, '2.5', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 2.5");
            upgrade_course_2_5($row->code, $row->lang);
        }
        if (version_compare($oldversion, '2.8', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 2.8");
            upgrade_course_2_8($row->code, $row->lang);
        }
        if (version_compare($oldversion, '2.9', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 2.9");
            upgrade_course_2_9($row->code, $row->lang);
        }
        if (version_compare($oldversion, '2.10', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 2.10");
            upgrade_course_2_10($row->code, $row->id);
        }
        if (version_compare($oldversion, '2.11', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 2.10");
            upgrade_course_2_11($row->code);
        }
        if (version_compare($oldversion, '3.0b2', '<')) {
            updateInfo(-1, $langUpgCourse . " " . $row->code . " 3.0");
            upgrade_course_3_0($row->code, $row->id);
        }
        $i++;
    }

    if (version_compare($oldversion, '2.1.3', '<')) {
        updateInfo(0.98, $langChangeDBCharset . " " . $mysqlMainDb . " " . $langToUTF);
        convert_db_utf8($mysqlMainDb);
    }

    if (version_compare($oldversion, '3.0b2', '<')) {
        Database::get()->query("USE `$mysqlMainDb`");

        Database::get()->query("CREATE VIEW `actions_daily_tmpview` AS
                SELECT
                `user_id`,
                `module_id`,
                `course_id`,
                COUNT(`id`) AS `hits`,
                SUM(`duration`) AS `duration`,
                DATE(`date_time`) AS `day`
                FROM `actions`
                GROUP BY DATE(`date_time`), `user_id`, `module_id`, `course_id`");

        Database::get()->queryFunc("SELECT * FROM `actions_daily_tmpview`", function ($row) {
            Database::get()->query("INSERT INTO `actions_daily`
                    (`id`, `user_id`, `module_id`, `course_id`, `hits`, `duration`, `day`, `last_update`)
                    VALUES
                    (NULL, ?d, ?d, ?d, ?d, ?d, ?t, NOW())", $row->user_id, $row->module_id, $row->course_id, $row->hits, $row->duration, $row->day);
        });

        Database::get()->query("DROP VIEW IF EXISTS `actions_daily_tmpview`");
        Database::get()->query("DROP TABLE IF EXISTS `actions`");

        // improve primary key for table exercise_answer
        Database::get()->query("ALTER TABLE `exercise_answer` CHANGE id oldid INT(11)");
        Database::get()->query("ALTER TABLE `exercise_answer` DROP PRIMARY KEY");
        Database::get()->query("ALTER TABLE `exercise_answer` ADD `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
        Database::get()->query("ALTER TABLE `exercise_answer` DROP `oldid`");

        if (get_config('enable_search')) {
            set_config('enable_search', 0);
            set_config('enable_indexing', 0);
            echo "<hr><p class='alert alert-info'>$langUpgIndexingNotice</p>";
        }

        // convert tables to InnoDB storage engine
        $result = Database::get()->queryArray("SHOW FULL TABLES");
        foreach ($result as $table) {
            $value = "Tables_in_$mysqlMainDb";
            if ($table->Table_type === 'BASE TABLE') {
                Database::get()->query("ALTER TABLE `" . $table->$value . "` ENGINE = InnoDB");
            }
        }
    }

    if (version_compare($oldversion, '3.0', '<')) {
        Database::get()->query("USE `$mysqlMainDb`");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `theme_options` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `name` VARCHAR(300) NOT NULL,
                                `styles` LONGTEXT NOT NULL,
                                PRIMARY KEY (`id`)) $charset_spec");
        //Add course home_layout fiels
        if (!DBHelper::fieldExists('home_layout', 'course')) {
            Database::get()->query("ALTER TABLE course ADD home_layout TINYINT(1) NOT NULL DEFAULT 1");
            Database::get()->query("UPDATE course SET home_layout = 3");
        }
        if (!DBHelper::fieldExists('q_scale', 'poll_question')) {
            Database::get()->query("ALTER TABLE poll_question ADD q_scale INT(11) NULL DEFAULT NULL");
        }
        //Add course image field
        if (!DBHelper::fieldExists('course_image', 'course')) {
            Database::get()->query("ALTER TABLE course ADD course_image VARCHAR(400) NULL");
        }
        // Move course description from unit_resources to new course.description field
        if (!DBHelper::fieldExists('description', 'course')) {
            Database::get()->query("ALTER TABLE course ADD description MEDIUMTEXT NOT NULL");
            $result = Database::get()->query("UPDATE course, course_units, unit_resources
                SET course.description = unit_resources.comments
                WHERE course.id = course_units.course_id AND
                      course_units.id = unit_resources.unit_id AND
                      course_units.`order` = -1 AND
                      unit_resources.res_id = -1");
            if ($result->affectedRows) {
                Database::get()->query("DELETE FROM unit_resources WHERE res_id = -1");
                Database::get()->query("DELETE FROM course_units WHERE `order` = -1");
            }
        }
        set_config('theme', 'default');
        set_config('theme_options_id', 0);
    }
    // update eclass version
    Database::get()->query("UPDATE config SET `value` = '" . ECLASS_VERSION . "' WHERE `key`='version'");

    updateInfo(1, $langUpgradeSuccess);
    $logdate = date("Y-m-d_G:i:s");

    $output_result = "<br/><div class='alert alert-success'>$langUpgradeSuccess<br/><b>$langUpgReady</b><br/><a href=\"../courses/log-$logdate.html\" target=\"_blank\">Log output</a></div><p/>";
    if ($debug_error) {
        $output_result .= "<div class='alert alert-danger'>" . $langUpgSucNotice . "</div>";
    }
    updateInfo(1, $output_result, false);
    $debug_output = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Open eClass upgrade log of $logdate</title></head><body>$debug_output</body></html>";
    file_put_contents($webDir . "/courses/log-$logdate.html", $debug_output);
} // end of if not submit
