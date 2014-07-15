-- MySQL dump 10.13  Distrib 5.1.73, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: openeclass
-- ------------------------------------------------------
-- Server version	5.1.73

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actions_daily`
--

DROP TABLE IF EXISTS `actions_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actions_daily` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `hits` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `day` date NOT NULL,
  `last_update` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `actionsdailyindex` (`module_id`,`day`),
  KEY `actionsdailyuserindex` (`user_id`),
  KEY `actionsdailydayindex` (`day`),
  KEY `actionsdailymoduleindex` (`module_id`),
  KEY `actionsdailycourseindex` (`course_id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actions_daily`
--

LOCK TABLES `actions_daily` WRITE;
/*!40000 ALTER TABLE `actions_daily` DISABLE KEYS */;
INSERT INTO `actions_daily` VALUES (1,1,27,1,4,1074,'2014-07-11','2014-07-11 13:57:20'),(2,1,7,1,10,90,'2014-07-11','2014-07-11 13:57:38'),(3,1,34,1,1,8,'2014-07-11','2014-07-11 13:42:31'),(4,1,10,1,5,33,'2014-07-11','2014-07-11 13:45:22'),(5,1,9,1,1,900,'2014-07-11','2014-07-11 13:57:42'),(6,1,27,1,6,542,'2014-07-14','2014-07-14 20:17:55'),(7,1,20,1,2,9,'2014-07-14','2014-07-14 20:10:45'),(8,1,7,1,11,1575,'2014-07-14','2014-07-14 20:31:12'),(9,1,2,1,3,6,'2014-07-14','2014-07-14 20:20:08'),(10,1,1,1,2,904,'2014-07-14','2014-07-14 20:10:32'),(11,0,27,1,1,16,'2014-07-14','2014-07-14 13:56:05'),(12,0,7,1,2,3,'2014-07-14','2014-07-14 13:56:21'),(13,0,1,1,1,900,'2014-07-14','2014-07-14 13:56:24'),(14,1,27,2,2,829,'2014-07-14','2014-07-14 20:17:26'),(15,1,2,2,2,902,'2014-07-14','2014-07-14 20:31:22'),(16,1,3,1,1,112,'2014-07-14','2014-07-14 20:18:16'),(17,1,7,2,1,12,'2014-07-14','2014-07-14 20:31:10'),(18,1,27,3,1,25,'2014-07-14','2014-07-14 20:33:34'),(19,1,7,3,5,256,'2014-07-14','2014-07-14 20:38:07'),(20,1,2,3,3,970,'2014-07-14','2014-07-14 20:39:25'),(21,1,27,4,1,108,'2014-07-14','2014-07-14 20:41:01'),(22,1,20,4,1,2,'2014-07-14','2014-07-14 20:42:49'),(23,1,7,4,2,902,'2014-07-14','2014-07-14 20:42:53'),(24,1,27,5,1,7,'2014-07-14','2014-07-14 20:43:54'),(25,1,7,5,8,160,'2014-07-14','2014-07-14 20:46:35'),(26,1,2,5,1,4,'2014-07-14','2014-07-14 20:46:41'),(27,1,1,5,3,953,'2014-07-14','2014-07-14 20:47:38'),(28,1,27,6,1,40,'2014-07-14','2014-07-14 20:49:50'),(29,1,7,6,10,282,'2014-07-14','2014-07-14 20:54:59'),(30,1,2,6,1,7,'2014-07-14','2014-07-14 20:54:39'),(31,1,20,6,2,934,'2014-07-14','2014-07-14 20:55:53');
/*!40000 ALTER TABLE `actions_daily` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `actions_summary`
--

DROP TABLE IF EXISTS `actions_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actions_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `visits` int(11) NOT NULL,
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `duration` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actions_summary`
--

LOCK TABLES `actions_summary` WRITE;
/*!40000 ALTER TABLE `actions_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `actions_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `user_id` int(11) NOT NULL,
  `privilege` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (0,0);
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_announcement`
--

DROP TABLE IF EXISTS `admin_announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_announcement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text,
  `date` datetime NOT NULL,
  `begin` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `lang` varchar(16) NOT NULL DEFAULT 'el',
  `order` mediumint(11) NOT NULL DEFAULT '0',
  `visible` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_announcement`
--

LOCK TABLES `admin_announcement` WRITE;
/*!40000 ALTER TABLE `admin_announcement` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_announcement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agenda`
--

DROP TABLE IF EXISTS `agenda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agenda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `duration` varchar(20) NOT NULL,
  `visible` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agenda`
--

LOCK TABLES `agenda` WRITE;
/*!40000 ALTER TABLE `agenda` DISABLE KEYS */;
INSERT INTO `agenda` VALUES (1,5,'Εγγραφές στα εργαστηριακά τμήματα','','2014-10-07 10:00:00','4',1);
/*!40000 ALTER TABLE `agenda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement`
--

DROP TABLE IF EXISTS `announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text,
  `date` date DEFAULT NULL,
  `course_id` int(11) NOT NULL DEFAULT '0',
  `order` mediumint(11) NOT NULL DEFAULT '0',
  `visible` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement`
--

LOCK TABLES `announcement` WRITE;
/*!40000 ALTER TABLE `announcement` DISABLE KEYS */;
INSERT INTO `announcement` VALUES (1,'Αναρτήθηκε η 2η σειρά γραπτών ασκήσεων.','','2014-07-11',1,1,1),(2,'Έναρξη διαλέξεων','<p>Την Τρίτη 14 Οκτωβρίου θα πραγματοποιηθεί η πρώτη διάλεξη του μαθήματος.</p>','2014-07-14',3,1,1),(3,'Εγγραφές εργαστηρίων','<p>Οι εγγραφές στα εργαστηριακά μαθήματα ξεκινούν την Τετάρτη 8 Οκτωβρίου στις 10:00.</p>','2014-07-14',5,1,1),(4,'Εξετάσεις εργαστηρίου','<p>Την Δευτέρα 23 Ιουνίου θα πραγματοποιηθούν οι εξετάσεις των εργαστηριακών τμημάτων.</p>','2014-07-14',6,1,1);
/*!40000 ALTER TABLE `announcement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment`
--

DROP TABLE IF EXISTS `assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `comments` text NOT NULL,
  `deadline` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `submission_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` char(1) NOT NULL DEFAULT '1',
  `secret_directory` varchar(30) NOT NULL,
  `group_submissions` char(1) NOT NULL DEFAULT '0',
  `max_grade` float DEFAULT NULL,
  `assign_to_specific` char(1) NOT NULL,
  `file_path` varchar(200) NOT NULL DEFAULT '',
  `file_name` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment`
--

LOCK TABLES `assignment` WRITE;
/*!40000 ALTER TABLE `assignment` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_submit`
--

DROP TABLE IF EXISTS `assignment_submit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_submit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `assignment_id` int(11) NOT NULL DEFAULT '0',
  `submission_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `submission_ip` varchar(45) NOT NULL DEFAULT '',
  `file_path` varchar(200) NOT NULL DEFAULT '',
  `file_name` varchar(200) NOT NULL DEFAULT '',
  `comments` text NOT NULL,
  `grade` float DEFAULT NULL,
  `grade_comments` text NOT NULL,
  `grade_submission_date` date NOT NULL DEFAULT '1000-10-10',
  `grade_submission_ip` varchar(45) NOT NULL DEFAULT '',
  `group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_submit`
--

LOCK TABLES `assignment_submit` WRITE;
/*!40000 ALTER TABLE `assignment_submit` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_submit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_to_specific`
--

DROP TABLE IF EXISTS `assignment_to_specific`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_to_specific` (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`,`assignment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_to_specific`
--

LOCK TABLES `assignment_to_specific` WRITE;
/*!40000 ALTER TABLE `assignment_to_specific` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_to_specific` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `limit` tinyint(4) NOT NULL DEFAULT '0',
  `students_semester` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_activities`
--

DROP TABLE IF EXISTS `attendance_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_activities` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `attendance_id` mediumint(11) NOT NULL,
  `title` varchar(250) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `description` text NOT NULL,
  `module_auto_id` mediumint(11) NOT NULL DEFAULT '0',
  `module_auto_type` tinyint(4) NOT NULL DEFAULT '0',
  `auto` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_activities`
--

LOCK TABLES `attendance_activities` WRITE;
/*!40000 ALTER TABLE `attendance_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_book`
--

DROP TABLE IF EXISTS `attendance_book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_book` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `attendance_activity_id` mediumint(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `attend` tinyint(4) NOT NULL DEFAULT '0',
  `comments` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_book`
--

LOCK TABLES `attendance_book` WRITE;
/*!40000 ALTER TABLE `attendance_book` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth`
--

DROP TABLE IF EXISTS `auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth` (
  `auth_id` int(2) NOT NULL AUTO_INCREMENT,
  `auth_name` varchar(20) NOT NULL DEFAULT '',
  `auth_settings` text,
  `auth_instructions` text,
  `auth_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`auth_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth`
--

LOCK TABLES `auth` WRITE;
/*!40000 ALTER TABLE `auth` DISABLE KEYS */;
INSERT INTO `auth` VALUES (1,'eclass','','',1),(2,'pop3','','',0),(3,'imap','','',0),(4,'ldap','','',0),(5,'db','','',0),(6,'shibboleth','','',0),(7,'cas','','',0);
/*!40000 ALTER TABLE `auth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bbb_servers`
--

DROP TABLE IF EXISTS `bbb_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbb_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) DEFAULT NULL,
  `ip` varchar(255) NOT NULL,
  `enabled` enum('true','false') DEFAULT NULL,
  `server_key` varchar(255) DEFAULT NULL,
  `api_url` varchar(255) DEFAULT NULL,
  `max_rooms` int(11) DEFAULT NULL,
  `max_users` int(11) DEFAULT NULL,
  `enable_recordings` enum('yes','no') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bbb_servers` (`hostname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bbb_servers`
--

LOCK TABLES `bbb_servers` WRITE;
/*!40000 ALTER TABLE `bbb_servers` DISABLE KEYS */;
/*!40000 ALTER TABLE `bbb_servers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bbb_session`
--

DROP TABLE IF EXISTS `bbb_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbb_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `start_date` datetime DEFAULT NULL,
  `public` enum('0','1') DEFAULT NULL,
  `active` enum('0','1') DEFAULT NULL,
  `running_at` int(11) DEFAULT NULL,
  `meeting_id` varchar(255) DEFAULT NULL,
  `mod_pw` varchar(255) DEFAULT NULL,
  `att_pw` varchar(255) DEFAULT NULL,
  `unlock_interval` int(11) DEFAULT NULL,
  `external_users` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bbb_session`
--

LOCK TABLES `bbb_session` WRITE;
/*!40000 ALTER TABLE `bbb_session` DISABLE KEYS */;
/*!40000 ALTER TABLE `bbb_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `key` varchar(32) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES ('base_url','http://localhost/openeclass/'),('default_language','el'),('dont_display_login_form','0'),('email_required','0'),('email_from','1'),('email_verification_required','0'),('dont_mail_unverified_mails','0'),('am_required','0'),('dropbox_allow_student_to_student','0'),('block_username_change','0'),('betacms','0'),('enable_mobileapi','1'),('code_key','zvmjHmonTm56llrKxhgDpHQLn3XDwvKbrbZvE+qC14I='),('display_captcha','0'),('insert_xml_metadata','0'),('doc_quota','200'),('video_quota','100'),('group_quota','100'),('dropbox_quota','100'),('user_registration','1'),('alt_auth_stud_reg','2'),('alt_auth_prof_reg','2'),('eclass_stud_reg','2'),('eclass_prof_reg','1'),('course_multidep','0'),('user_multidep','0'),('restrict_owndep','0'),('restrict_teacher_owndep','0'),('max_glossary_terms','250'),('phpSysInfoURL','../admin/sysinfo/'),('email_sender','root@localhost'),('admin_name','Διαχειριστής Πλατφόρμας'),('email_helpdesk',''),('site_name','Open eClass'),('phone','+30 2xx xxxx xxx'),('fax',''),('postaddress',''),('institution','Ακαδημαϊκό Διαδίκτυο GUNet'),('institution_url','http://www.gunet.gr/'),('account_duration','126144000'),('language','el'),('active_ui_languages','el es it en fr de'),('student_upload_whitelist','pdf, ps, eps, tex, latex, dvi, texinfo, texi, zip, rar, tar, bz2, gz, 7z, xz, lha, lzh, z, Z, doc, docx, odt, ott, sxw, stw, fodt, txt, rtf, dot, mcw, wps, xls, xlsx, xlt, ods, ots, sxc, stc, fods, uos, csv, ppt, pps, pot, pptx, ppsx, odp, otp, sxi, sti, fodp, uop, potm, odg, otg, sxd, std, fodg, odb, mdb, ttf, otf, jpg, jpeg, png, gif, bmp, tif, tiff, psd, dia, svg, ppm, xbm, xpm, ico, avi, asf, asx, wm, wmv, wma, dv, mov, moov, movie, mp4, mpg, mpeg, 3gp, 3g2, m2v, aac, m4a, flv, f4v, m4v, mp3, swf, webm, ogv, ogg, mid, midi, aif, rm, rpm, ram, wav, mp2, m3u, qt, vsd, vss, vst'),('teacher_upload_whitelist','htm, html, js, css, xml, xsl, cpp, c, java, m, h, tcl, py, sgml, sgm, ini, ds_store'),('login_fail_check','1'),('login_fail_threshold','15'),('login_fail_deny_interval','5'),('login_fail_forgive_interval','24'),('actions_expire_interval','12'),('log_expire_interval','5'),('log_purge_interval','12'),('course_metadata','0'),('opencourses_enable','0'),('version','2.99');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course`
--

DROP TABLE IF EXISTS `course`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `lang` varchar(16) NOT NULL DEFAULT 'el',
  `title` varchar(250) NOT NULL DEFAULT '',
  `keywords` text NOT NULL,
  `course_license` tinyint(4) NOT NULL DEFAULT '0',
  `visible` tinyint(4) NOT NULL,
  `prof_names` varchar(200) NOT NULL DEFAULT '',
  `public_code` varchar(20) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `doc_quota` float NOT NULL DEFAULT '1.04858e+08',
  `video_quota` float NOT NULL DEFAULT '1.04858e+08',
  `group_quota` float NOT NULL DEFAULT '1.04858e+08',
  `dropbox_quota` float NOT NULL DEFAULT '1.04858e+08',
  `password` varchar(50) NOT NULL DEFAULT '',
  `glossary_expand` tinyint(1) NOT NULL DEFAULT '0',
  `glossary_index` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course`
--

LOCK TABLES `course` WRITE;
/*!40000 ALTER TABLE `course` DISABLE KEYS */;
INSERT INTO `course` VALUES (1,'TMA5100','el','Θεμελιώδη Θέματα Επιστήμης Η/Υ','',5,2,'Τέλης Τιπιτέλης','TMA5100','2014-07-11 13:37:29',2.09715e+08,1.04858e+08,1.04858e+08,1.04858e+08,'',0,1),(2,'TMA1100','el','Αλγοριθμική','',1,2,'Διαχειριστής Πλατφόρμας','TMA1100','2014-07-14 20:16:57',2.09715e+08,1.04858e+08,1.04858e+08,1.04858e+08,'',0,1),(3,'TMA7100','el','Εκπαιδευτική Τεχνολογία και Διδακτική της Πληροφορικής','',5,2,'Διαχειριστής Πλατφόρμας','TMA7100','2014-07-14 20:33:16',2.09715e+08,1.04858e+08,1.04858e+08,1.04858e+08,'',0,1),(4,'TMA7101','el','Νέες Δικτυακές Τεχνολογίες','',5,2,'Διαχειριστής Πλατφόρμας','TMA7101','2014-07-14 20:40:55',2.09715e+08,1.04858e+08,1.04858e+08,1.04858e+08,'',0,1),(5,'TMA6100','el','Τεχνητή Νοημοσύνη','',5,2,'Διαχειριστής Πλατφόρμας','TMA6100','2014-07-14 20:43:51',2.09715e+08,1.04858e+08,1.04858e+08,1.04858e+08,'',0,1),(6,'TMA7102','el','Ενσωματωμένα Συστήματα','',4,2,'Διαχειριστής Πλατφόρμας','TMA7102','2014-07-14 20:49:44',2.09715e+08,1.04858e+08,1.04858e+08,1.04858e+08,'',0,1);
/*!40000 ALTER TABLE `course` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_department`
--

DROP TABLE IF EXISTS `course_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course` int(11) NOT NULL,
  `department` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_department`
--

LOCK TABLES `course_department` WRITE;
/*!40000 ALTER TABLE `course_department` DISABLE KEYS */;
INSERT INTO `course_department` VALUES (1,1,8),(2,2,4),(3,3,10),(4,4,10),(5,5,9),(6,6,10);
/*!40000 ALTER TABLE `course_department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_module`
--

DROP TABLE IF EXISTS `course_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `visible` tinyint(4) NOT NULL,
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_course` (`module_id`,`course_id`),
  KEY `visible_cid` (`visible`,`course_id`)
) ENGINE=MyISAM AUTO_INCREMENT=121 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_module`
--

LOCK TABLES `course_module` WRITE;
/*!40000 ALTER TABLE `course_module` DISABLE KEYS */;
INSERT INTO `course_module` VALUES (1,1,1,1),(2,2,1,1),(3,3,1,1),(4,7,1,1),(5,20,1,1),(6,4,0,1),(7,5,0,1),(8,9,0,1),(9,10,0,1),(10,32,0,1),(11,30,0,1),(12,15,0,1),(13,16,0,1),(14,17,0,1),(15,18,0,1),(16,19,0,1),(17,21,0,1),(18,23,0,1),(19,26,0,1),(20,34,0,1),(21,1,1,2),(22,2,1,2),(23,3,1,2),(24,7,1,2),(25,20,1,2),(26,4,0,2),(27,5,0,2),(28,9,0,2),(29,10,0,2),(30,32,0,2),(31,30,0,2),(32,15,0,2),(33,16,0,2),(34,17,0,2),(35,18,0,2),(36,19,0,2),(37,21,0,2),(38,23,0,2),(39,26,0,2),(40,34,0,2),(41,1,1,3),(42,2,1,3),(43,3,1,3),(44,7,1,3),(45,20,1,3),(46,4,0,3),(47,5,0,3),(48,9,0,3),(49,10,0,3),(50,32,0,3),(51,30,0,3),(52,15,0,3),(53,16,0,3),(54,17,0,3),(55,18,0,3),(56,19,0,3),(57,21,0,3),(58,23,0,3),(59,26,0,3),(60,34,0,3),(61,1,1,4),(62,2,1,4),(63,3,1,4),(64,7,1,4),(65,20,1,4),(66,4,0,4),(67,5,0,4),(68,9,0,4),(69,10,0,4),(70,32,0,4),(71,30,0,4),(72,15,0,4),(73,16,0,4),(74,17,0,4),(75,18,0,4),(76,19,0,4),(77,21,0,4),(78,23,0,4),(79,26,0,4),(80,34,0,4),(81,1,1,5),(82,2,1,5),(83,3,1,5),(84,7,1,5),(85,20,1,5),(86,4,0,5),(87,5,0,5),(88,9,0,5),(89,10,0,5),(90,32,0,5),(91,30,0,5),(92,15,0,5),(93,16,0,5),(94,17,0,5),(95,18,0,5),(96,19,0,5),(97,21,0,5),(98,23,0,5),(99,26,0,5),(100,34,0,5),(101,1,1,6),(102,2,1,6),(103,3,1,6),(104,7,1,6),(105,20,1,6),(106,4,0,6),(107,5,0,6),(108,9,0,6),(109,10,0,6),(110,32,0,6),(111,30,0,6),(112,15,0,6),(113,16,0,6),(114,17,0,6),(115,18,0,6),(116,19,0,6),(117,21,0,6),(118,23,0,6),(119,26,0,6),(120,34,0,6);
/*!40000 ALTER TABLE `course_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_review`
--

DROP TABLE IF EXISTS `course_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `is_certified` tinyint(1) NOT NULL DEFAULT '0',
  `level` tinyint(4) NOT NULL DEFAULT '0',
  `last_review` datetime NOT NULL,
  `last_reviewer` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_review`
--

LOCK TABLES `course_review` WRITE;
/*!40000 ALTER TABLE `course_review` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_settings`
--

DROP TABLE IF EXISTS `course_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_settings` (
  `setting_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`setting_id`,`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_settings`
--

LOCK TABLES `course_settings` WRITE;
/*!40000 ALTER TABLE `course_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_units`
--

DROP TABLE IF EXISTS `course_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `comments` mediumtext,
  `visible` tinyint(4) DEFAULT NULL,
  `public` tinyint(4) NOT NULL DEFAULT '1',
  `order` int(11) NOT NULL DEFAULT '0',
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `course_units_index` (`course_id`,`order`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_units`
--

LOCK TABLES `course_units` WRITE;
/*!40000 ALTER TABLE `course_units` DISABLE KEYS */;
INSERT INTO `course_units` VALUES (1,'Πληροφορίες Μαθήματος',NULL,0,1,-1,1),(2,'Πληροφορίες Μαθήματος',NULL,0,1,-1,2),(3,'Πληροφορίες Μαθήματος',NULL,0,1,-1,3),(4,'Πληροφορίες Μαθήματος',NULL,0,1,-1,4),(5,'Πληροφορίες Μαθήματος',NULL,0,1,-1,5),(6,'Πληροφορίες Μαθήματος',NULL,0,1,-1,6);
/*!40000 ALTER TABLE `course_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_user`
--

DROP TABLE IF EXISTS `course_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_user` (
  `course_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `tutor` int(11) NOT NULL DEFAULT '0',
  `editor` int(11) NOT NULL DEFAULT '0',
  `reviewer` int(11) NOT NULL DEFAULT '0',
  `reg_date` date NOT NULL,
  `receive_mail` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`course_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_user`
--

LOCK TABLES `course_user` WRITE;
/*!40000 ALTER TABLE `course_user` DISABLE KEYS */;
INSERT INTO `course_user` VALUES (1,1,1,1,0,0,'2014-07-11',1),(2,1,1,1,0,0,'2014-07-14',1),(3,1,1,1,0,0,'2014-07-14',1),(4,1,1,1,0,0,'2014-07-14',1),(5,1,1,1,0,0,'2014-07-14',1),(6,1,1,1,0,0,'2014-07-14',1);
/*!40000 ALTER TABLE `course_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cron_params`
--

DROP TABLE IF EXISTS `cron_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cron_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `last_run` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cron_params`
--

LOCK TABLES `cron_params` WRITE;
/*!40000 ALTER TABLE `cron_params` DISABLE KEYS */;
/*!40000 ALTER TABLE `cron_params` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document`
--

DROP TABLE IF EXISTS `document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL DEFAULT '0',
  `subsystem` tinyint(4) NOT NULL,
  `subsystem_id` int(11) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `extra_path` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `public` tinyint(4) NOT NULL DEFAULT '1',
  `comment` text,
  `category` tinyint(4) NOT NULL DEFAULT '0',
  `title` text,
  `creator` text,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `subject` text,
  `description` text,
  `author` varchar(255) NOT NULL DEFAULT '',
  `format` varchar(32) NOT NULL DEFAULT '',
  `language` varchar(16) NOT NULL DEFAULT 'el',
  `copyrighted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `doc_path_index` (`course_id`,`subsystem`,`path`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document`
--

LOCK TABLES `document` WRITE;
/*!40000 ALTER TABLE `document` DISABLE KEYS */;
INSERT INTO `document` VALUES (1,1,0,NULL,'/53bfbe949G6D','','Αποτελέσματα',1,1,NULL,0,NULL,'','2014-07-11 13:38:12','2014-07-11 13:41:04',NULL,NULL,'','.dir','el',0),(2,1,0,NULL,'/53bfbe949G6D/53bfbefdrjyp.pdf','','focs_2013_14_spring_grades.pdf',1,1,'',0,'Αποτελέσματα κανονικής εξέτασης','Διαχειριστής Πλατφόρμας','2014-07-11 13:39:57','2014-07-11 13:39:57','','','','pdf','el',1),(3,2,0,NULL,'/53c413d6kJla.pdf','','Βαθμολογίες.pdf',1,1,'Βαθμολογίες εαρινού εξαμήνου',0,'Βαθμολογίες','Διαχειριστής Πλατφόρμας','2014-07-14 20:31:02','2014-07-14 20:31:02','','','','pdf','el',0),(4,4,0,NULL,'/53c41694d0aA.pdf','','Βιβλιογραφία.pdf',1,1,'',6,'Βιβλιογραφία','Διαχειριστής Πλατφόρμας','2014-07-14 20:42:44','2014-07-14 20:42:44','','','','pdf','el',2);
/*!40000 ALTER TABLE `document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dropbox_attachment`
--

DROP TABLE IF EXISTS `dropbox_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dropbox_attachment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `msg_id` int(11) unsigned NOT NULL,
  `filename` varchar(250) NOT NULL,
  `real_filename` varchar(255) NOT NULL,
  `filesize` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `msg` (`msg_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dropbox_attachment`
--

LOCK TABLES `dropbox_attachment` WRITE;
/*!40000 ALTER TABLE `dropbox_attachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `dropbox_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dropbox_index`
--

DROP TABLE IF EXISTS `dropbox_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dropbox_index` (
  `msg_id` int(11) unsigned NOT NULL,
  `recipient_id` int(11) unsigned NOT NULL,
  `thread_id` int(11) unsigned NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`msg_id`,`recipient_id`),
  KEY `list` (`recipient_id`,`is_read`),
  KEY `participants` (`thread_id`,`recipient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dropbox_index`
--

LOCK TABLES `dropbox_index` WRITE;
/*!40000 ALTER TABLE `dropbox_index` DISABLE KEYS */;
/*!40000 ALTER TABLE `dropbox_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dropbox_msg`
--

DROP TABLE IF EXISTS `dropbox_msg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dropbox_msg` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `author_id` int(11) unsigned NOT NULL,
  `subject` varchar(250) NOT NULL,
  `body` longtext NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dropbox_msg`
--

LOCK TABLES `dropbox_msg` WRITE;
/*!40000 ALTER TABLE `dropbox_msg` DISABLE KEYS */;
/*!40000 ALTER TABLE `dropbox_msg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ebook`
--

DROP TABLE IF EXISTS `ebook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ebook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `title` text,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebook`
--

LOCK TABLES `ebook` WRITE;
/*!40000 ALTER TABLE `ebook` DISABLE KEYS */;
/*!40000 ALTER TABLE `ebook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ebook_section`
--

DROP TABLE IF EXISTS `ebook_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ebook_section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ebook_id` int(11) NOT NULL,
  `public_id` varchar(11) NOT NULL,
  `file` varchar(128) DEFAULT NULL,
  `title` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebook_section`
--

LOCK TABLES `ebook_section` WRITE;
/*!40000 ALTER TABLE `ebook_section` DISABLE KEYS */;
/*!40000 ALTER TABLE `ebook_section` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ebook_subsection`
--

DROP TABLE IF EXISTS `ebook_subsection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ebook_subsection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_id` varchar(11) NOT NULL,
  `public_id` varchar(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `title` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebook_subsection`
--

LOCK TABLES `ebook_subsection` WRITE;
/*!40000 ALTER TABLE `ebook_subsection` DISABLE KEYS */;
/*!40000 ALTER TABLE `ebook_subsection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercise`
--

DROP TABLE IF EXISTS `exercise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exercise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(250) DEFAULT NULL,
  `description` text,
  `type` tinyint(4) unsigned NOT NULL DEFAULT '1',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `time_constraint` int(11) DEFAULT '0',
  `attempts_allowed` int(11) DEFAULT '0',
  `random` smallint(6) NOT NULL DEFAULT '0',
  `active` tinyint(4) DEFAULT NULL,
  `public` tinyint(4) NOT NULL DEFAULT '1',
  `results` tinyint(1) NOT NULL DEFAULT '1',
  `score` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise`
--

LOCK TABLES `exercise` WRITE;
/*!40000 ALTER TABLE `exercise` DISABLE KEYS */;
/*!40000 ALTER TABLE `exercise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercise_answer`
--

DROP TABLE IF EXISTS `exercise_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exercise_answer` (
  `id` int(11) NOT NULL DEFAULT '0',
  `question_id` int(11) NOT NULL DEFAULT '0',
  `answer` text,
  `correct` int(11) DEFAULT NULL,
  `comment` text,
  `weight` float(5,2) DEFAULT NULL,
  `r_position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`question_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_answer`
--

LOCK TABLES `exercise_answer` WRITE;
/*!40000 ALTER TABLE `exercise_answer` DISABLE KEYS */;
/*!40000 ALTER TABLE `exercise_answer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercise_answer_record`
--

DROP TABLE IF EXISTS `exercise_answer_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exercise_answer_record` (
  `answer_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `eurid` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` text,
  `answer_id` int(11) NOT NULL,
  `weight` float(5,2) DEFAULT NULL,
  `is_answered` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`answer_record_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_answer_record`
--

LOCK TABLES `exercise_answer_record` WRITE;
/*!40000 ALTER TABLE `exercise_answer_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `exercise_answer_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercise_question`
--

DROP TABLE IF EXISTS `exercise_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exercise_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `question` text,
  `description` text,
  `weight` float(11,2) DEFAULT NULL,
  `q_position` int(11) DEFAULT '1',
  `type` int(11) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_question`
--

LOCK TABLES `exercise_question` WRITE;
/*!40000 ALTER TABLE `exercise_question` DISABLE KEYS */;
/*!40000 ALTER TABLE `exercise_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercise_with_questions`
--

DROP TABLE IF EXISTS `exercise_with_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exercise_with_questions` (
  `question_id` int(11) NOT NULL DEFAULT '0',
  `exercise_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`question_id`,`exercise_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_with_questions`
--

LOCK TABLES `exercise_with_questions` WRITE;
/*!40000 ALTER TABLE `exercise_with_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `exercise_with_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum`
--

DROP TABLE IF EXISTS `forum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `desc` mediumtext NOT NULL,
  `num_topics` int(10) NOT NULL DEFAULT '0',
  `num_posts` int(10) NOT NULL DEFAULT '0',
  `last_post_id` int(10) NOT NULL DEFAULT '0',
  `cat_id` int(10) NOT NULL DEFAULT '0',
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum`
--

LOCK TABLES `forum` WRITE;
/*!40000 ALTER TABLE `forum` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_category`
--

DROP TABLE IF EXISTS `forum_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_category` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cat_title` varchar(100) NOT NULL DEFAULT '',
  `cat_order` int(11) NOT NULL DEFAULT '0',
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `forum_category_index` (`id`,`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_category`
--

LOCK TABLES `forum_category` WRITE;
/*!40000 ALTER TABLE `forum_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_notify`
--

DROP TABLE IF EXISTS `forum_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_notify` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `cat_id` int(11) NOT NULL DEFAULT '0',
  `forum_id` int(11) NOT NULL DEFAULT '0',
  `topic_id` int(11) NOT NULL DEFAULT '0',
  `notify_sent` tinyint(1) NOT NULL DEFAULT '0',
  `course_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_notify`
--

LOCK TABLES `forum_notify` WRITE;
/*!40000 ALTER TABLE `forum_notify` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_notify` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_post`
--

DROP TABLE IF EXISTS `forum_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_post` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `topic_id` int(10) NOT NULL DEFAULT '0',
  `post_text` mediumtext NOT NULL,
  `poster_id` int(10) NOT NULL DEFAULT '0',
  `post_time` datetime DEFAULT NULL,
  `poster_ip` varchar(45) NOT NULL DEFAULT '',
  `parent_post_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_post`
--

LOCK TABLES `forum_post` WRITE;
/*!40000 ALTER TABLE `forum_post` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_post` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_topic`
--

DROP TABLE IF EXISTS `forum_topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_topic` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `poster_id` int(10) DEFAULT NULL,
  `topic_time` datetime DEFAULT NULL,
  `num_views` int(10) NOT NULL DEFAULT '0',
  `num_replies` int(10) NOT NULL DEFAULT '0',
  `last_post_id` int(10) NOT NULL DEFAULT '0',
  `forum_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_topic`
--

LOCK TABLES `forum_topic` WRITE;
/*!40000 ALTER TABLE `forum_topic` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_topic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glossary`
--

DROP TABLE IF EXISTS `glossary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossary` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `term` varchar(255) NOT NULL,
  `definition` text NOT NULL,
  `url` text,
  `order` int(11) NOT NULL DEFAULT '0',
  `datestamp` datetime NOT NULL,
  `course_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glossary`
--

LOCK TABLES `glossary` WRITE;
/*!40000 ALTER TABLE `glossary` DISABLE KEYS */;
/*!40000 ALTER TABLE `glossary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glossary_category`
--

DROP TABLE IF EXISTS `glossary_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossary_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glossary_category`
--

LOCK TABLES `glossary_category` WRITE;
/*!40000 ALTER TABLE `glossary_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `glossary_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gradebook`
--

DROP TABLE IF EXISTS `gradebook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gradebook` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `students_semester` tinyint(4) NOT NULL DEFAULT '1',
  `range` tinyint(4) NOT NULL DEFAULT '10',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gradebook`
--

LOCK TABLES `gradebook` WRITE;
/*!40000 ALTER TABLE `gradebook` DISABLE KEYS */;
/*!40000 ALTER TABLE `gradebook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gradebook_activities`
--

DROP TABLE IF EXISTS `gradebook_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gradebook_activities` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `gradebook_id` mediumint(11) NOT NULL,
  `title` varchar(250) DEFAULT NULL,
  `activity_type` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `description` text NOT NULL,
  `weight` mediumint(11) NOT NULL DEFAULT '0',
  `module_auto_id` mediumint(11) NOT NULL DEFAULT '0',
  `module_auto_type` tinyint(4) NOT NULL DEFAULT '0',
  `auto` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gradebook_activities`
--

LOCK TABLES `gradebook_activities` WRITE;
/*!40000 ALTER TABLE `gradebook_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `gradebook_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gradebook_book`
--

DROP TABLE IF EXISTS `gradebook_book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gradebook_book` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `gradebook_activity_id` mediumint(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `grade` float NOT NULL DEFAULT '-1',
  `comments` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gradebook_book`
--

LOCK TABLES `gradebook_book` WRITE;
/*!40000 ALTER TABLE `gradebook_book` DISABLE KEYS */;
/*!40000 ALTER TABLE `gradebook_book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group`
--

DROP TABLE IF EXISTS `group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text,
  `forum_id` int(11) DEFAULT NULL,
  `max_members` int(11) NOT NULL DEFAULT '0',
  `secret_directory` varchar(30) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group`
--

LOCK TABLES `group` WRITE;
/*!40000 ALTER TABLE `group` DISABLE KEYS */;
/*!40000 ALTER TABLE `group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_members`
--

DROP TABLE IF EXISTS `group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_members` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_tutor` int(11) NOT NULL DEFAULT '0',
  `description` text,
  PRIMARY KEY (`group_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_members`
--

LOCK TABLES `group_members` WRITE;
/*!40000 ALTER TABLE `group_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_properties`
--

DROP TABLE IF EXISTS `group_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_properties` (
  `course_id` int(11) NOT NULL,
  `self_registration` tinyint(4) NOT NULL DEFAULT '1',
  `multiple_registration` tinyint(4) NOT NULL DEFAULT '0',
  `allow_unregister` tinyint(4) NOT NULL DEFAULT '0',
  `forum` tinyint(4) NOT NULL DEFAULT '1',
  `private_forum` tinyint(4) NOT NULL DEFAULT '0',
  `documents` tinyint(4) NOT NULL DEFAULT '1',
  `wiki` tinyint(4) NOT NULL DEFAULT '0',
  `agenda` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_properties`
--

LOCK TABLES `group_properties` WRITE;
/*!40000 ALTER TABLE `group_properties` DISABLE KEYS */;
INSERT INTO `group_properties` VALUES (1,1,0,0,1,0,1,0,0),(2,1,0,0,1,0,1,0,0),(3,1,0,0,1,0,1,0,0),(4,1,0,0,1,0,1,0,0),(5,1,0,0,1,0,1,0,0),(6,1,0,0,1,0,1,0,0);
/*!40000 ALTER TABLE `group_properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hierarchy`
--

DROP TABLE IF EXISTS `hierarchy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hierarchy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) DEFAULT NULL,
  `name` text NOT NULL,
  `number` int(11) NOT NULL DEFAULT '1000',
  `generator` int(11) NOT NULL DEFAULT '100',
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `allow_course` tinyint(1) NOT NULL DEFAULT '0',
  `allow_user` tinyint(1) NOT NULL DEFAULT '0',
  `order_priority` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lftindex` (`lft`),
  KEY `rgtindex` (`rgt`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hierarchy`
--

LOCK TABLES `hierarchy` WRITE;
/*!40000 ALTER TABLE `hierarchy` DISABLE KEYS */;
INSERT INTO `hierarchy` VALUES (1,'','Ακαδημαϊκό Διαδίκτυο GUNet',1000,100,1,68,0,0,NULL),(2,'TMA','Τμήμα 1',10,100,2,23,1,1,NULL),(3,'TMAPRE','Προπτυχιακό Πρόγραμμα Σπουδών',10,100,3,20,1,1,NULL),(4,'TMA1','1ο εξάμηνο',10,101,4,5,1,1,NULL),(5,'TMA2','2ο εξάμηνο',10,100,6,7,1,1,NULL),(6,'TMA3','3ο εξάμηνο',10,100,8,9,1,1,NULL),(7,'TMA4','4ο εξάμηνο',10,100,10,11,1,1,NULL),(8,'TMA5','5ο εξάμηνο',10,101,12,13,1,1,NULL),(9,'TMA6','6ο εξάμηνο',10,101,14,15,1,1,NULL),(10,'TMA7','7ο εξάμηνο',10,103,16,17,1,1,NULL),(11,'TMA8','8ο εξάμηνο',10,100,18,19,1,1,NULL),(12,'TMAPOST','Μεταπτυχιακό Πρόγραμμα Σπουδών',10,100,21,22,1,1,NULL),(13,'TMB','Τμήμα 2',20,100,24,45,1,1,NULL),(14,'TMBPRE','Προπτυχιακό Πρόγραμμα Σπουδών',20,100,25,42,1,1,NULL),(15,'TMB1','1ο εξάμηνο',20,100,26,27,1,1,NULL),(16,'TMB2','2ο εξάμηνο',20,100,28,29,1,1,NULL),(17,'TMB3','3ο εξάμηνο',20,100,30,31,1,1,NULL),(18,'TMB4','4ο εξάμηνο',20,100,32,33,1,1,NULL),(19,'TMB5','5ο εξάμηνο',20,100,34,35,1,1,NULL),(20,'TMB6','6ο εξάμηνο',20,100,36,37,1,1,NULL),(21,'TMB7','7ο εξάμηνο',20,100,38,39,1,1,NULL),(22,'TMB8','8ο εξάμηνο',20,100,40,41,1,1,NULL),(23,'TMBPOST','Μεταπτυχιακό Πρόγραμμα Σπουδών',20,100,43,44,1,1,NULL),(24,'TMC','Τμήμα 3',30,100,46,67,1,1,NULL),(25,'TMCPRE','Προπτυχιακό Πρόγραμμα Σπουδών',30,100,47,64,1,1,NULL),(26,'TMC1','1ο εξάμηνο',30,100,48,49,1,1,NULL),(27,'TMC2','2ο εξάμηνο',30,100,50,51,1,1,NULL),(28,'TMC3','3ο εξάμηνο',30,100,52,53,1,1,NULL),(29,'TMC4','4ο εξάμηνο',30,100,54,55,1,1,NULL),(30,'TMC5','5ο εξάμηνο',30,100,56,57,1,1,NULL),(31,'TMC6','6ο εξάμηνο',30,100,58,59,1,1,NULL),(32,'TMC7','7ο εξάμηνο',30,100,60,61,1,1,NULL),(33,'TMC8','8ο εξάμηνο',30,100,62,63,1,1,NULL),(34,'TMCPOST','Μεταπτυχιακό Πρόγραμμα Σπουδών',30,100,65,66,1,1,NULL);
/*!40000 ALTER TABLE `hierarchy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `hierarchy_depth`
--

DROP TABLE IF EXISTS `hierarchy_depth`;
/*!50001 DROP VIEW IF EXISTS `hierarchy_depth`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `hierarchy_depth` (
 `id` tinyint NOT NULL,
  `code` tinyint NOT NULL,
  `name` tinyint NOT NULL,
  `number` tinyint NOT NULL,
  `generator` tinyint NOT NULL,
  `lft` tinyint NOT NULL,
  `rgt` tinyint NOT NULL,
  `allow_course` tinyint NOT NULL,
  `allow_user` tinyint NOT NULL,
  `order_priority` tinyint NOT NULL,
  `depth` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `link`
--

DROP TABLE IF EXISTS `link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `category` int(6) NOT NULL DEFAULT '0',
  `order` int(6) NOT NULL DEFAULT '0',
  `hits` int(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`course_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `link`
--

LOCK TABLES `link` WRITE;
/*!40000 ALTER TABLE `link` DISABLE KEYS */;
INSERT INTO `link` VALUES (1,3,'http://www.openeclass.org','Open eClass Portal','',0,1,0);
/*!40000 ALTER TABLE `link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `link_category`
--

DROP TABLE IF EXISTS `link_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `link_category` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `order` int(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `link_category`
--

LOCK TABLES `link_category` WRITE;
/*!40000 ALTER TABLE `link_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `link_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `course_id` int(11) NOT NULL DEFAULT '0',
  `module_id` int(11) NOT NULL DEFAULT '0',
  `details` text NOT NULL,
  `action_type` int(11) NOT NULL DEFAULT '0',
  `ts` datetime NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cmid` (`course_id`,`module_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
INSERT INTO `log` VALUES (1,0,0,0,'a:2:{s:5:\"uname\";s:5:\"admin\";s:4:\"pass\";s:6:\"fdsfds\";}',8,'2014-07-03 19:40:44','83.212.85.170'),(2,1,0,0,'a:5:{s:2:\"id\";s:1:\"1\";s:4:\"code\";s:7:\"TMA5100\";s:5:\"title\";s:56:\"Θεμελιώδη Θέματα Επιστήμης Η/Υ\";s:8:\"language\";s:2:\"el\";s:7:\"visible\";s:1:\"2\";}',5,'2014-07-11 13:37:29','83.212.85.170'),(3,1,1,3,'a:3:{s:2:\"id\";i:1;s:4:\"path\";s:13:\"/53bfbe949G6D\";s:8:\"filename\";s:24:\"Ανακοινώσεις\";}',1,'2014-07-11 13:38:12','83.212.85.170'),(4,1,1,3,'a:5:{s:2:\"id\";i:2;s:8:\"filepath\";s:30:\"/53bfbe949G6D/53bfbefdrjyp.pdf\";s:8:\"filename\";s:30:\"focs_2013_14_spring_grades.pdf\";s:7:\"comment\";s:0:\"\";s:5:\"title\";s:60:\"Αποτελέσματα κανονικής εξέτασης\";}',1,'2014-07-11 13:39:57','83.212.85.170'),(5,1,1,3,'a:3:{s:4:\"path\";s:13:\"/53bfbe949G6D\";s:8:\"filename\";s:24:\"Ανακοινώσεις\";s:11:\"newfilename\";s:24:\"Αποτελέσματα\";}',2,'2014-07-11 13:41:04','83.212.85.170'),(6,1,0,0,'a:5:{s:2:\"id\";s:1:\"2\";s:4:\"code\";s:7:\"TMA1100\";s:5:\"title\";s:22:\"Αλγοριθμική\";s:8:\"language\";s:2:\"el\";s:7:\"visible\";s:1:\"2\";}',5,'2014-07-14 20:16:57','83.212.85.170'),(7,1,2,3,'a:5:{s:2:\"id\";i:3;s:8:\"filepath\";s:17:\"/53c413d6kJla.pdf\";s:8:\"filename\";s:26:\"Βαθμολογίες.pdf\";s:7:\"comment\";s:54:\"Βαθμολογίες εαρινού εξαμήνου\";s:5:\"title\";s:22:\"Βαθμολογίες\";}',1,'2014-07-14 20:31:02','83.212.85.170'),(8,1,0,0,'a:5:{s:2:\"id\";s:1:\"3\";s:4:\"code\";s:7:\"TMA7100\";s:5:\"title\";s:103:\"Εκπαιδευτική Τεχνολογία και Διδακτική της Πληροφορικής\";s:8:\"language\";s:2:\"el\";s:7:\"visible\";s:1:\"2\";}',5,'2014-07-14 20:33:16','83.212.85.170'),(9,1,3,7,'a:4:{s:2:\"id\";s:1:\"2\";s:5:\"email\";b:0;s:5:\"title\";s:31:\"Έναρξη διαλέξεων\";s:7:\"content\";s:253:\"&Tau;&eta;&nu; &Tau;&rho;&#943;&tau;&eta; 14 &Omicron;&kappa;&tau;&omega;&beta;&rho;&#943;&omicron;&upsilon; &theta;&alpha; &pi;&rho;&alpha;&gamma;&mu;&alpha;&tau;&omicron;&pi;&omicron;&iota;&eta;&theta;&epsilon;&#943; &eta; &pi;&rho;&#974;&tau;&eta;\n+\";}',1,'2014-07-14 20:38:06','83.212.85.170'),(10,1,3,2,'a:5:{s:2:\"id\";i:1;s:3:\"url\";s:25:\"http://www.openeclass.org\";s:5:\"title\";s:18:\"Open eClass Portal\";s:11:\"description\";s:1:\"\n\";s:8:\"category\";N;}',1,'2014-07-14 20:39:25','83.212.85.170'),(11,1,0,0,'a:5:{s:2:\"id\";s:1:\"4\";s:4:\"code\";s:7:\"TMA7101\";s:5:\"title\";s:50:\"Νέες Δικτυακές Τεχνολογίες\";s:8:\"language\";s:2:\"el\";s:7:\"visible\";s:1:\"2\";}',5,'2014-07-14 20:40:55','83.212.85.170'),(12,1,4,3,'a:5:{s:2:\"id\";i:4;s:8:\"filepath\";s:17:\"/53c41694d0aA.pdf\";s:8:\"filename\";s:28:\"Βιβλιογραφία.pdf\";s:7:\"comment\";s:0:\"\";s:5:\"title\";s:24:\"Βιβλιογραφία\";}',1,'2014-07-14 20:42:44','83.212.85.170'),(13,1,0,0,'a:5:{s:2:\"id\";s:1:\"5\";s:4:\"code\";s:7:\"TMA6100\";s:5:\"title\";s:33:\"Τεχνητή Νοημοσύνη\";s:8:\"language\";s:2:\"el\";s:7:\"visible\";s:1:\"2\";}',5,'2014-07-14 20:43:51','83.212.85.170'),(14,1,5,7,'a:4:{s:2:\"id\";s:1:\"3\";s:5:\"email\";b:0;s:5:\"title\";s:39:\"Εγγραφές εργαστηρίων\";s:7:\"content\";s:282:\"&Omicron;&iota; &epsilon;&gamma;&gamma;&rho;&alpha;&phi;&#941;&sigmaf; &sigma;&tau;&alpha; &epsilon;&rho;&gamma;&alpha;&sigma;&tau;&eta;&rho;&iota;&alpha;&kappa;&#940; &mu;&alpha;&theta;&#942;&mu;&alpha;&tau;&alpha; &xi;&epsilon;&kappa;&iota;&nu;&omicron;&#973;&nu; &tau;&eta;&nu;\n+\";}',1,'2014-07-14 20:46:34','83.212.85.170'),(15,1,5,1,'a:5:{s:2:\"id\";s:1:\"1\";s:4:\"date\";s:16:\"2014-10-07 10:00\";s:8:\"duration\";s:1:\"4\";s:5:\"title\";s:63:\"Εγγραφές στα εργαστηριακά τμήματα\";s:7:\"content\";s:0:\"\";}',1,'2014-07-14 20:47:38','83.212.85.170'),(16,1,0,0,'a:5:{s:2:\"id\";s:1:\"6\";s:4:\"code\";s:7:\"TMA7102\";s:5:\"title\";s:43:\"Ενσωματωμένα Συστήματα\";s:8:\"language\";s:2:\"el\";s:7:\"visible\";s:1:\"2\";}',5,'2014-07-14 20:49:44','83.212.85.170'),(17,1,6,7,'a:4:{s:2:\"id\";s:1:\"4\";s:5:\"email\";b:0;s:5:\"title\";s:41:\"Εξετάσεις εργαστηρίου\";s:7:\"content\";s:279:\"&Tau;&eta;&nu; &Delta;&epsilon;&upsilon;&tau;&#941;&rho;&alpha; 23 &Iota;&omicron;&upsilon;&nu;&#943;&omicron;&upsilon; &theta;&alpha; &pi;&rho;&alpha;&gamma;&mu;&alpha;&tau;&omicron;&pi;&omicron;&iota;&eta;&theta;&omicron;&#973;&nu; &omicron;&iota; &epsilon;&xi;&epsilon;&tau;\n+\";}',1,'2014-07-14 20:53:28','83.212.85.170'),(18,1,6,20,'a:3:{s:2:\"id\";i:0;s:5:\"title\";s:41:\"Αντικειμενικοί στόχοι\";s:7:\"content\";s:1658:\"<div class=\"prof_color2\"><span><strong>Σκοπός μαθήματος</strong></span></div>\n<div><span>Η συντριπτική πλειοψηφία των υπαρχόντων ψηφιακών υπολογιστών είναι ενσωματωμένοι σε έξυπνες συσκευές και όχι σε επιτραπέζια συστήματα. Σκοπός του μαθήματος είναι να φέρει τους σπουδαστές σε επαφή με τον επιστημονικό κλάδο που αφορά στο αντικείμενο αυτό.</span></div>\n<div class=\"prof_color2\"> </div>\n<div class=\"prof_color2\"><span><strong>Στόχοι μαθήματος</strong></span></div>\n<div class=\"prof_color2\">Με την ολοκλήρωση του μαθήματος οι σπουδαστές θα μπορούν να:</div>\n<div>\n<ul>\n<li><span>Υλοποιούν ενσωματωμένα ψηφιακά συστήματα σε γλώσσα περιγραφής υλικού VHDL</span></li>\n<li><span>Αξιοποιούν τα λειτουργικά συστήματα πραγματικού χρόνου</span></li>\n<li><span>Αναλύουν την απόδοση του ενσωματωμένου λογισμικού</span></li>\n<li><span>Αξιοποιούν την τεχνολογία FPGA για το σχεδιασμό ενσωματωμένων συστημάτων.</span></li>\n<li><span>Αξιοποιούν το περιβάλλον προγραμματισμού Android για τον προγραμματισμό κινητών ενσωματωμένων συσκευών.</span></li>\n</ul>\n</div>\";}',2,'2014-07-14 20:55:54','83.212.85.170');
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_archive`
--

DROP TABLE IF EXISTS `log_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `course_id` int(11) NOT NULL DEFAULT '0',
  `module_id` int(11) NOT NULL DEFAULT '0',
  `details` text NOT NULL,
  `action_type` int(11) NOT NULL DEFAULT '0',
  `ts` datetime NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_archive`
--

LOCK TABLES `log_archive` WRITE;
/*!40000 ALTER TABLE `log_archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_failure`
--

DROP TABLE IF EXISTS `login_failure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_failure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `count` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `last_fail` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_failure`
--

LOCK TABLES `login_failure` WRITE;
/*!40000 ALTER TABLE `login_failure` DISABLE KEYS */;
INSERT INTO `login_failure` VALUES (1,'195.130.111.190',1,'2014-07-14 13:52:36');
/*!40000 ALTER TABLE `login_failure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loginout`
--

DROP TABLE IF EXISTS `loginout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loginout` (
  `idLog` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
  `id_user` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `ip` char(45) NOT NULL DEFAULT '0.0.0.0',
  `when` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `action` enum('LOGIN','LOGOUT') NOT NULL DEFAULT 'LOGIN',
  PRIMARY KEY (`idLog`),
  KEY `id_user` (`id_user`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loginout`
--

LOCK TABLES `loginout` WRITE;
/*!40000 ALTER TABLE `loginout` DISABLE KEYS */;
INSERT INTO `loginout` VALUES (1,0,'62.103.213.71','2014-07-03 19:34:56','LOGIN'),(2,1,'62.103.213.71','2014-07-03 19:37:26','LOGIN'),(3,1,'62.103.213.71','2014-07-03 19:40:48','LOGIN'),(4,1,'5.55.128.136','2014-07-03 21:14:02','LOGIN'),(5,1,'5.55.128.136','2014-07-03 21:14:08','LOGIN'),(6,1,'5.55.128.136','2014-07-03 21:14:10','LOGIN'),(7,1,'5.55.128.136','2014-07-03 21:14:11','LOGIN'),(8,1,'5.55.120.200','2014-07-11 13:34:19','LOGIN'),(9,1,'5.55.120.200','2014-07-11 13:46:17','LOGOUT'),(10,1,'5.55.120.200','2014-07-11 13:46:28','LOGIN'),(11,1,'62.103.213.71','2014-07-11 13:54:27','LOGIN'),(12,1,'195.130.111.190','2014-07-14 13:53:05','LOGIN'),(13,1,'195.130.111.190','2014-07-14 13:55:46','LOGOUT'),(14,1,'195.130.111.190','2014-07-14 17:15:57','LOGIN'),(15,1,'195.130.111.190','2014-07-14 17:16:12','LOGOUT'),(16,1,'37.6.220.20','2014-07-14 20:10:08','LOGIN');
/*!40000 ALTER TABLE `loginout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loginout_summary`
--

DROP TABLE IF EXISTS `loginout_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loginout_summary` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `login_sum` int(11) unsigned NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loginout_summary`
--

LOCK TABLES `loginout_summary` WRITE;
/*!40000 ALTER TABLE `loginout_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `loginout_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logins`
--

DROP TABLE IF EXISTS `logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip` char(45) NOT NULL DEFAULT '0.0.0.0',
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logins`
--

LOCK TABLES `logins` WRITE;
/*!40000 ALTER TABLE `logins` DISABLE KEYS */;
INSERT INTO `logins` VALUES (1,1,'5.55.120.200','2014-07-11 13:37:36',1),(2,1,'5.55.120.200','2014-07-11 13:43:06',1),(3,1,'5.55.120.200','2014-07-11 13:45:29',1),(4,1,'62.103.213.71','2014-07-11 13:57:20',1),(5,1,'195.130.111.190','2014-07-14 13:53:10',1),(6,0,'195.130.111.190','2014-07-14 13:56:05',1),(7,1,'37.6.220.20','2014-07-14 20:10:16',1),(8,1,'37.6.220.20','2014-07-14 20:10:50',1),(9,1,'37.6.220.20','2014-07-14 20:11:08',1),(10,1,'37.6.220.20','2014-07-14 20:13:41',1),(11,1,'37.6.220.20','2014-07-14 20:17:19',2),(12,1,'37.6.220.20','2014-07-14 20:17:26',2),(13,1,'37.6.220.20','2014-07-14 20:17:55',1),(14,1,'37.6.220.20','2014-07-14 20:33:34',3),(15,1,'37.6.220.20','2014-07-14 20:41:01',4),(16,1,'37.6.220.20','2014-07-14 20:43:54',5),(17,1,'37.6.220.20','2014-07-14 20:49:50',6);
/*!40000 ALTER TABLE `logins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lp_asset`
--

DROP TABLE IF EXISTS `lp_asset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_asset` (
  `asset_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL DEFAULT '0',
  `path` varchar(255) NOT NULL DEFAULT '',
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`asset_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lp_asset`
--

LOCK TABLES `lp_asset` WRITE;
/*!40000 ALTER TABLE `lp_asset` DISABLE KEYS */;
/*!40000 ALTER TABLE `lp_asset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lp_learnPath`
--

DROP TABLE IF EXISTS `lp_learnPath`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_learnPath` (
  `learnPath_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `lock` enum('OPEN','CLOSE') NOT NULL DEFAULT 'OPEN',
  `visible` tinyint(4) NOT NULL DEFAULT '0',
  `rank` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`learnPath_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lp_learnPath`
--

LOCK TABLES `lp_learnPath` WRITE;
/*!40000 ALTER TABLE `lp_learnPath` DISABLE KEYS */;
/*!40000 ALTER TABLE `lp_learnPath` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lp_module`
--

DROP TABLE IF EXISTS `lp_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_module` (
  `module_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `accessibility` enum('PRIVATE','PUBLIC') NOT NULL DEFAULT 'PRIVATE',
  `startAsset_id` int(11) NOT NULL DEFAULT '0',
  `contentType` enum('CLARODOC','DOCUMENT','EXERCISE','HANDMADE','SCORM','SCORM_ASSET','LABEL','COURSE_DESCRIPTION','LINK','MEDIA','MEDIALINK') NOT NULL,
  `launch_data` text NOT NULL,
  PRIMARY KEY (`module_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lp_module`
--

LOCK TABLES `lp_module` WRITE;
/*!40000 ALTER TABLE `lp_module` DISABLE KEYS */;
/*!40000 ALTER TABLE `lp_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lp_rel_learnPath_module`
--

DROP TABLE IF EXISTS `lp_rel_learnPath_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_rel_learnPath_module` (
  `learnPath_module_id` int(11) NOT NULL AUTO_INCREMENT,
  `learnPath_id` int(11) NOT NULL DEFAULT '0',
  `module_id` int(11) NOT NULL DEFAULT '0',
  `lock` enum('OPEN','CLOSE') NOT NULL DEFAULT 'OPEN',
  `visible` tinyint(4) NOT NULL DEFAULT '0',
  `specificComment` text NOT NULL,
  `rank` int(11) NOT NULL DEFAULT '0',
  `parent` int(11) NOT NULL DEFAULT '0',
  `raw_to_pass` tinyint(4) NOT NULL DEFAULT '50',
  PRIMARY KEY (`learnPath_module_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lp_rel_learnPath_module`
--

LOCK TABLES `lp_rel_learnPath_module` WRITE;
/*!40000 ALTER TABLE `lp_rel_learnPath_module` DISABLE KEYS */;
/*!40000 ALTER TABLE `lp_rel_learnPath_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lp_user_module_progress`
--

DROP TABLE IF EXISTS `lp_user_module_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_user_module_progress` (
  `user_module_progress_id` int(22) NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `learnPath_module_id` int(11) NOT NULL DEFAULT '0',
  `learnPath_id` int(11) NOT NULL DEFAULT '0',
  `lesson_location` varchar(255) NOT NULL DEFAULT '',
  `lesson_status` enum('NOT ATTEMPTED','PASSED','FAILED','COMPLETED','BROWSED','INCOMPLETE','UNKNOWN') NOT NULL DEFAULT 'NOT ATTEMPTED',
  `entry` enum('AB-INITIO','RESUME','') NOT NULL DEFAULT 'AB-INITIO',
  `raw` tinyint(4) NOT NULL DEFAULT '-1',
  `scoreMin` tinyint(4) NOT NULL DEFAULT '-1',
  `scoreMax` tinyint(4) NOT NULL DEFAULT '-1',
  `total_time` varchar(13) NOT NULL DEFAULT '0000:00:00.00',
  `session_time` varchar(13) NOT NULL DEFAULT '0000:00:00.00',
  `suspend_data` text NOT NULL,
  `credit` enum('CREDIT','NO-CREDIT') NOT NULL DEFAULT 'NO-CREDIT',
  PRIMARY KEY (`user_module_progress_id`),
  KEY `optimize` (`user_id`,`learnPath_module_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lp_user_module_progress`
--

LOCK TABLES `lp_user_module_progress` WRITE;
/*!40000 ALTER TABLE `lp_user_module_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `lp_user_module_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_summary`
--

DROP TABLE IF EXISTS `monthly_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_summary` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `month` varchar(20) NOT NULL DEFAULT '0',
  `profesNum` int(11) NOT NULL DEFAULT '0',
  `studNum` int(11) NOT NULL DEFAULT '0',
  `visitorsNum` int(11) NOT NULL DEFAULT '0',
  `coursNum` int(11) NOT NULL DEFAULT '0',
  `logins` int(11) NOT NULL DEFAULT '0',
  `details` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_summary`
--

LOCK TABLES `monthly_summary` WRITE;
/*!40000 ALTER TABLE `monthly_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `monthly_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poll`
--

DROP TABLE IF EXISTS `poll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poll` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `creator_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` int(11) NOT NULL DEFAULT '0',
  `anonymized` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poll`
--

LOCK TABLES `poll` WRITE;
/*!40000 ALTER TABLE `poll` DISABLE KEYS */;
/*!40000 ALTER TABLE `poll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poll_answer_record`
--

DROP TABLE IF EXISTS `poll_answer_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poll_answer_record` (
  `arid` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0',
  `qid` int(11) NOT NULL DEFAULT '0',
  `aid` int(11) NOT NULL DEFAULT '0',
  `answer_text` text NOT NULL,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `submit_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`arid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poll_answer_record`
--

LOCK TABLES `poll_answer_record` WRITE;
/*!40000 ALTER TABLE `poll_answer_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `poll_answer_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poll_question`
--

DROP TABLE IF EXISTS `poll_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poll_question` (
  `pqid` bigint(12) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0',
  `question_text` varchar(250) NOT NULL DEFAULT '',
  `qtype` enum('multiple','fill') NOT NULL,
  PRIMARY KEY (`pqid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poll_question`
--

LOCK TABLES `poll_question` WRITE;
/*!40000 ALTER TABLE `poll_question` DISABLE KEYS */;
/*!40000 ALTER TABLE `poll_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poll_question_answer`
--

DROP TABLE IF EXISTS `poll_question_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poll_question_answer` (
  `pqaid` int(11) NOT NULL AUTO_INCREMENT,
  `pqid` int(11) NOT NULL DEFAULT '0',
  `answer_text` text NOT NULL,
  PRIMARY KEY (`pqaid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poll_question_answer`
--

LOCK TABLES `poll_question_answer` WRITE;
/*!40000 ALTER TABLE `poll_question_answer` DISABLE KEYS */;
/*!40000 ALTER TABLE `poll_question_answer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_resources`
--

DROP TABLE IF EXISTS `unit_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unit_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `comments` mediumtext,
  `res_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT '',
  `visible` tinyint(4) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `unit_res_index` (`unit_id`,`visible`,`res_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_resources`
--

LOCK TABLES `unit_resources` WRITE;
/*!40000 ALTER TABLE `unit_resources` DISABLE KEYS */;
INSERT INTO `unit_resources` VALUES (1,1,'Περιγραφή','<p>Εισαγωγή στις βασικές αρχές και έννοιες του υπολογισμού: υπολογιστικά προβλήματα ως τυπικές γλώσσες, μοντέλα υπολογισμού, υπολογισιμότητα, αλγόριθμοι, κλάσεις πολυπλοκότητας, πληρότητα, αυτόματα, τυπικές γραμματικές, αποτελέσματα δυσκολίας, λογική στην επιστήμη των υπολογιστών. Το μάθημα προσφέρει επίσης μια εισαγωγή στο μοντέλο του συναρτησιακού προγραμματισμού με τη γλώσσα Haskell και περιλαμβάνει εργαστηριακές ασκήσεις που βοηθούν στην εμπέδωση των διδασκόμενων θεωρητικών εννοιών.</p>',-1,'description',0,-1,'2014-07-11 13:37:29'),(2,2,'Περιγραφή','<p><span>Τι είναι αλγόριθμος. Παρουσίαση – περιγραφή ενός αλγορίθμου. Φραστική μέθοδος, ψευδοκώδικας, συμβολική μέθοδος, διαγράμματα ροής. Βασικές έννοιες. Δομημένος Προγραμματισμός. Αρχές και έννοιες. Ιεραρχικός και Αρθρωτός προγραμματισμός. Βασικά αντικείμενα. Μεταβλητές, εντολές, (επανάληψης, συνθήκης), δομές (αρχεία και πίνακες). Αναδρομή. Ανάλυση αλγορίθμου. Απόδειξη ορθότητας, ανάλυση αποδοτικότητας. Βασικές έννοιες της ανάλυσης αποδοτικότητας: ασυμπτωτικοί συμβολισμοί Ο, Ω. Αναδρομικές εξισώσεις. Ανάλυση μη-αναδρομικών αλγορίθμων. (παραδείγματα σε: αθροίσματα σειρών, πολλαπλασιασμό πινάκων, εύρεση μέγιστου κοινού διαιρέτη, κλπ). Ανάλυση αναδρομικών αλγόριθμων (υπολογισμός παραγοντικού, πύργοι του Ανόι, αριθμοί Fibonacci κλπ). Ωμή Βία (brute force). Βασικά προβλήματα. Ταξινόμηση (selection sort, bubble sort), σειριακή αναζήτηση, ταίριασμα συμβολοσειρών (με ωμή βία). Προβλήματα εγγύτερου ζεύγους, κυρτού εσωτερικού.</span></p>',-1,'description',0,-1,'2014-07-14 20:16:57'),(3,3,'Περιγραφή','<p><span>Το μάθημα αναφέρεται σε θέματα εκπαιδευτικού σχεδιασμού με έμφαση στην αξιοποίηση των νέων τεχνολογιών της πληροφορικής και των επικοινωνιών ως εργαλείου διδασκαλίας και μάθησης. Στο πλαίσιο αυτό, εξετάζονται παραδοσιακές και σύγχρονες προσεγγίσεις που σχετίζονται με τις θεωρίες μάθησης, τα διδακτικά μοντέλα, τις εκπαιδευτικές τεχνικές και τις μαθησιακές τεχνολογίες. Επίσης, συζητούνται ειδικά θέματα διδακτικών προσεγγίσεων που αφορούν στις ιδιαιτερότητες της διδασκαλίας του αντικείμενου της Πληροφορικής. Ιδιαίτερη βαρύτητα δίνεται στο ρόλο του εκπαιδευτικού λογισμικού και των διαδικτυακών συστημάτων μαθησιακής τεχνολογίας στη μαθησιακή διαδικασία και εξετάζονται οι παιδαγωγικές, διδακτικές, αλλά και τεχνικές προδιαγραφές που πρέπει να διέπουν το σχεδιασμό τους και χρησιμοποιούνται στη διαμόρφωση κριτηρίων για την αξιολόγησή τους. Τέλος, παρουσιάζονται οι Ευρωπαϊκές και διεθνείς δράσεις τυποποίησης για την υλοποίηση διαλειτουργικών συστημάτων μαθησιακής τεχνολογίας.</span></p>',-1,'description',0,-1,'2014-07-14 20:33:16'),(4,4,'Περιγραφή','<p><span>Εισαγωγή στα δίκτυα υψηλών ταχυτήτων (ιστορική αναφορά στο FDDI). Τεχνολογία ΑΤΜ (αρχιτεκτονική, επίπεδα ΑΤΜ, ΑΤΜ κυψελίδες, Virtual channels, virtual paths). Τεχνολογία ΑΤΜ (Διευθυνσιοδότηση ΑΤΜ, ποιότητα υπηρεσίας, διαχείριση κίνησης, μηχανισμοί ελέγχου κίνησης/συμφόρησης, χρήσεις ΑΤΜ, διασύνδεση με κλασσικά δίκτυα). Τεχνολογία Gigabit Ethernet (κύρια χαρακτηριστικά, συστάσεις, αρχιτεκτονική, πλεονεκτήματα). Τεχνολογίες xWDM (DWDM, CWDM, SONET/SDH). Τεχνολογία MPLS. Υπηρεσία VoIP (Voice over IP), ενσωμάτωση κλασσικής τηλεφωνίας με IP δίκτυα. Υπηρεσία QoS (Quality Of Service). Virtual Private Networks (VPN). IP version 6 (IPV6). Εισαγωγή στις Δορυφορικές Επικοινωνίες (Internet over Satellite).</span></p>',-1,'description',0,-1,'2014-07-14 20:40:55'),(5,5,'Περιγραφή','<p><span>Στο πλαίσιο του μαθήματος διδάσκονται τα παρακάτω: Ιστορική Αναδρομή, Βασικές έννοιες, Αναπαράσταση γνώσης, Τυφλοί Αλγόριθμοι αναζήτησης, Ευριστικοί Αλγόριθμοι αναζήτησης, Επίλυση προβλημάτων, Συλλογιστική, Συστήματα Παραγωγής, Τεχνικές εξαγωγής συμπερασμάτων, Έμπειρα Συστήματα, Μάθηση Μηχανής, Νευρωνικά Δίκτυα, Γενετικοί Αλγόριθμοι, Έμπειρα Συστήματα, Νοήμονες Πράκτορες.</span></p>',-1,'description',0,-1,'2014-07-14 20:43:51'),(6,6,'Περιγραφή','<p><span>Έννοιες και τεχνικές ενσωματωμένων συστημάτων με χρήση ων επεξεργαστών. Λειτουργικά συστήματα πραγματικού χρόνου (POSIX, LINUX). Ανάλυση απόδοσης και βελτιστοποίηση ενσωματωμένου λογισμικού. Γλώσσα περιγραφής υλικού VHDL. Σχεδίαση σε FPGA. Περιβάλλον Android και προγραμματισμός κινητών ενσωματωμένων συσκευών.</span></p>',-1,'description',0,-1,'2014-07-14 20:49:44'),(7,6,'Αντικειμενικοί στόχοι','<div class=\"prof_color2\"><span><strong>Σκοπός μαθήματος</strong></span></div>\n<div><span>Η συντριπτική πλειοψηφία των υπαρχόντων ψηφιακών υπολογιστών είναι ενσωματωμένοι σε έξυπνες συσκευές και όχι σε επιτραπέζια συστήματα. Σκοπός του μαθήματος είναι να φέρει τους σπουδαστές σε επαφή με τον επιστημονικό κλάδο που αφορά στο αντικείμενο αυτό.</span></div>\n<div class=\"prof_color2\"> </div>\n<div class=\"prof_color2\"><span><strong>Στόχοι μαθήματος</strong></span></div>\n<div class=\"prof_color2\">Με την ολοκλήρωση του μαθήματος οι σπουδαστές θα μπορούν να:</div>\n<div>\n<ul>\n<li><span>Υλοποιούν ενσωματωμένα ψηφιακά συστήματα σε γλώσσα περιγραφής υλικού VHDL</span></li>\n<li><span>Αξιοποιούν τα λειτουργικά συστήματα πραγματικού χρόνου</span></li>\n<li><span>Αναλύουν την απόδοση του ενσωματωμένου λογισμικού</span></li>\n<li><span>Αξιοποιούν την τεχνολογία FPGA για το σχεδιασμό ενσωματωμένων συστημάτων.</span></li>\n<li><span>Αξιοποιούν το περιβάλλον προγραμματισμού Android για τον προγραμματισμό κινητών ενσωματωμένων συσκευών.</span></li>\n</ul>\n</div>',1,'description',0,1,'2014-07-14 20:55:54');
/*!40000 ALTER TABLE `unit_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surname` varchar(60) NOT NULL DEFAULT '',
  `givenname` varchar(60) NOT NULL DEFAULT '',
  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `password` varchar(60) NOT NULL DEFAULT 'empty',
  `email` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '5',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `am` varchar(20) NOT NULL DEFAULT '',
  `registered_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expires_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lang` varchar(16) NOT NULL DEFAULT 'el',
  `announce_flag` date NOT NULL DEFAULT '1000-01-01',
  `doc_flag` date NOT NULL DEFAULT '1000-01-01',
  `forum_flag` date NOT NULL DEFAULT '1000-01-01',
  `description` text NOT NULL,
  `has_icon` tinyint(1) NOT NULL DEFAULT '0',
  `verified_mail` tinyint(1) NOT NULL DEFAULT '2',
  `receive_mail` tinyint(1) NOT NULL DEFAULT '1',
  `email_public` tinyint(1) NOT NULL DEFAULT '0',
  `phone_public` tinyint(1) NOT NULL DEFAULT '0',
  `am_public` tinyint(1) NOT NULL DEFAULT '0',
  `whitelist` text NOT NULL,
  `last_passreminder` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'','Διαχειριστής Πλατφόρμας','admin','$2a$08$dpKS.07P9.f1cOJwth9VoOhYLC1/3UTl.aRDaK56SzICo0lB2K5D2','root@localhost',1,'','','2014-07-03 19:34:56','2019-07-02 19:34:56','el','1000-01-01','1000-01-01','1000-01-01','Administrator',0,1,1,0,0,0,'*,,',NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_department`
--

DROP TABLE IF EXISTS `user_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` mediumint(8) unsigned NOT NULL,
  `department` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_department`
--

LOCK TABLES `user_department` WRITE;
/*!40000 ALTER TABLE `user_department` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_request`
--

DROP TABLE IF EXISTS `user_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `givenname` varchar(60) NOT NULL DEFAULT '',
  `surname` varchar(60) NOT NULL DEFAULT '',
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `verified_mail` tinyint(1) NOT NULL DEFAULT '2',
  `faculty_id` int(11) NOT NULL DEFAULT '0',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `am` varchar(20) NOT NULL DEFAULT '',
  `state` int(11) NOT NULL DEFAULT '0',
  `date_open` datetime DEFAULT NULL,
  `date_closed` datetime DEFAULT NULL,
  `comment` text NOT NULL,
  `lang` varchar(16) NOT NULL DEFAULT 'el',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `request_ip` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_request`
--

LOCK TABLES `user_request` WRITE;
/*!40000 ALTER TABLE `user_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video`
--

DROP TABLE IF EXISTS `video`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `url` varchar(200) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `creator` varchar(200) NOT NULL,
  `publisher` varchar(200) NOT NULL,
  `date` datetime NOT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `public` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cid` (`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video`
--

LOCK TABLES `video` WRITE;
/*!40000 ALTER TABLE `video` DISABLE KEYS */;
/*!40000 ALTER TABLE `video` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `videolink`
--

DROP TABLE IF EXISTS `videolink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `videolink` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `url` varchar(200) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `creator` varchar(200) NOT NULL DEFAULT '',
  `publisher` varchar(200) NOT NULL DEFAULT '',
  `date` datetime NOT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `public` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cid` (`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `videolink`
--

LOCK TABLES `videolink` WRITE;
/*!40000 ALTER TABLE `videolink` DISABLE KEYS */;
/*!40000 ALTER TABLE `videolink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_acls`
--

DROP TABLE IF EXISTS `wiki_acls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_acls` (
  `wiki_id` int(11) unsigned NOT NULL,
  `flag` varchar(255) NOT NULL,
  `value` enum('false','true') NOT NULL DEFAULT 'false',
  PRIMARY KEY (`wiki_id`,`flag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_acls`
--

LOCK TABLES `wiki_acls` WRITE;
/*!40000 ALTER TABLE `wiki_acls` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_acls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_locks`
--

DROP TABLE IF EXISTS `wiki_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_locks` (
  `ptitle` varchar(255) NOT NULL DEFAULT '',
  `wiki_id` int(11) unsigned NOT NULL,
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ltime_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ltime_alive` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ptitle`,`wiki_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_locks`
--

LOCK TABLES `wiki_locks` WRITE;
/*!40000 ALTER TABLE `wiki_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_pages`
--

DROP TABLE IF EXISTS `wiki_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_pages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wiki_id` int(11) unsigned NOT NULL DEFAULT '0',
  `owner_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `ctime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_version` int(11) unsigned NOT NULL DEFAULT '0',
  `last_mtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_pages`
--

LOCK TABLES `wiki_pages` WRITE;
/*!40000 ALTER TABLE `wiki_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_pages_content`
--

DROP TABLE IF EXISTS `wiki_pages_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_pages_content` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `editor_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `mtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `content` text NOT NULL,
  `changelog` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_pages_content`
--

LOCK TABLES `wiki_pages_content` WRITE;
/*!40000 ALTER TABLE `wiki_pages_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_pages_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_properties`
--

DROP TABLE IF EXISTS `wiki_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_properties` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `group_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_properties`
--

LOCK TABLES `wiki_properties` WRITE;
/*!40000 ALTER TABLE `wiki_properties` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `hierarchy_depth`
--

/*!50001 DROP TABLE IF EXISTS `hierarchy_depth`*/;
/*!50001 DROP VIEW IF EXISTS `hierarchy_depth`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `hierarchy_depth` AS select `node`.`id` AS `id`,`node`.`code` AS `code`,`node`.`name` AS `name`,`node`.`number` AS `number`,`node`.`generator` AS `generator`,`node`.`lft` AS `lft`,`node`.`rgt` AS `rgt`,`node`.`allow_course` AS `allow_course`,`node`.`allow_user` AS `allow_user`,`node`.`order_priority` AS `order_priority`,(count(`parent`.`id`) - 1) AS `depth` from (`hierarchy` `node` join `hierarchy` `parent`) where (`node`.`lft` between `parent`.`lft` and `parent`.`rgt`) group by `node`.`id` order by `node`.`lft` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-07-15 10:05:28
