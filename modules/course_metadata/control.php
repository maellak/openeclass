<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2013  Greek Universities Network - GUnet
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

$require_current_course = true;
$require_course_admin = true;
define('STATIC_MODULE', 1);
require_once '../../include/baseTheme.php';
$pageName = $langCourseMetadataControlPanel;
require_once 'CourseXML.php';

// exit if feature disabled or is not reviewer
if (!get_config('opencourses_enable') || !$is_opencourses_reviewer) {
    header("Location: {$urlServer}courses/$course_code/index.php");
    exit();
}

// initialize data from xml and db
$xml = CourseXMLElement::init($course_id, $course_code);
$xmlData = $xml->asFlatArray();
$visible = Database::get()->querySingle("SELECT visible FROM course WHERE id = ?d", $course_id)->visible;
$hasOpenAccess = ($visible == 2 || $visible == 1);
$hasMandatoryMetadata = $xml->hasMandatoryMetadata();
$clang = langname_to_code($currentCourseLanguage);
$hasLicense = (isset($xmlData['course_license_' . $clang]) && !empty($xmlData['course_license_' . $clang]));
$hasTeacherConfirm = (isset($xmlData['course_confirmCurriculum']) && $xmlData['course_confirmCurriculum'] == 'true');
$numDocs = Database::get()->querySingle("SELECT count(id) as count FROM document WHERE course_id = ?d", $course_id)->count;
$numUnits = Database::get()->querySingle("SELECT count(id) as count FROM course_units WHERE course_id = ?d AND `order` >= 1 AND visible = 1", $course_id)->count;
$numVideo = Database::get()->querySingle("SELECT count(id) as count FROM video WHERE course_id = ?d", $course_id)->count;
$numVideoLinks = Database::get()->querySingle("SELECT count(id) as count FROM videolink WHERE course_id = ?d", $course_id)->count;
$numMedia = $numVideo + $numVideoLinks;
$hasTeacherConfirmVideo = (isset($xmlData['course_confirmVideolectures']) && $xmlData['course_confirmVideolectures'] == 'true');

// auto detect level
$looksAMinus = false;
if ($hasOpenAccess && $hasMandatoryMetadata && $hasLicense &&
        $hasTeacherConfirm && ($numDocs > 0) && ($numUnits > 0)) {
    $looksAMinus = true;
}
$looksA = false;
if ($looksAMinus && ($numMedia > 0)) {
    $looksA = true;
}
$looksAPlus = false;
if ($looksA && $hasTeacherConfirmVideo) {
    $looksAPlus = true;
}

if (isset($_POST['submit'])) {
    // default fallback is false
    if (!isset($_POST['course_confirmAMinusLevel'])) {
        $_POST['course_confirmAMinusLevel'] = 'false';
    }
    if (!isset($_POST['course_confirmALevel'])) {
        $_POST['course_confirmALevel'] = 'false';
    }
    if (!isset($_POST['course_confirmAPlusLevel'])) {
        $_POST['course_confirmAPlusLevel'] = 'false';
    }

    // validation
    if ($_POST['course_confirmAMinusLevel'] == 'true') {
        if (!$looksAMinus) {
            $_POST['course_confirmAMinusLevel'] = 'false';
            $_POST['course_confirmALevel'] = 'false';
            $_POST['course_confirmAPlusLevel'] = 'false';
        }
    }

    if ($_POST['course_confirmALevel'] == 'true') {
        if (!$looksA) {
            $_POST['course_confirmALevel'] = 'false';
            $_POST['course_confirmAPlusLevel'] = 'false';
        } else {
            $_POST['course_confirmAMinusLevel'] = 'true';
        }
    }

    if ($_POST['course_confirmAPlusLevel'] == 'true') {
        if (!$looksAPlus) {
            $_POST['course_confirmAPlusLevel'] = 'false';
        } else {
            $_POST['course_confirmAMinusLevel'] = 'true';
            $_POST['course_confirmALevel'] = 'true';
        }
    }

    // success messageand values for storage
    $is_certified = 1;
    $level = CourseXMLElement::NO_LEVEL;
    if ($_POST['course_confirmAPlusLevel'] == 'true') {
        $tool_content .= "<div class='alert alert-success'>$langOpenCoursesWasSet $langOpenCoursesIsAPlusLevel</div>";
        $level = CourseXMLElement::A_PLUS_LEVEL;
    } else if ($_POST['course_confirmALevel'] == 'true') {
        $tool_content .= "<div class='alert alert-success'>$langOpenCoursesWasSet $langOpenCoursesIsALevel</div>";
        $level = CourseXMLElement::A_LEVEL;
    } else if ($_POST['course_confirmAMinusLevel'] == 'true') {
        $tool_content .= "<div class='alert alert-success'>$langOpenCoursesWasSet $langOpenCoursesIsAMinusLevel</div>";
        $level = CourseXMLElement::A_MINUS_LEVEL;
    } else {
        $tool_content .= "<div class='alert alert-danger'>$langOpenCoursesWasNotSet</div>";
        $is_certified = 0;
    }

    $_POST['course_lastLevelConfirmation'] = date("Y-m-d\TH:i:sP");
    $last_review = date('Y-m-d H:i:s');
    $xml->populate($_POST);
    CourseXMLElement::save($course_id, $course_code, $xml);
    $xmlData = $xml->asFlatArray(); // reload data
    // insert or update db
    $exists = ($exres = Database::get()->querySingle("SELECT 1 as `exists` FROM course_review WHERE course_id = ?d", $course_id)) ? $exres->exists : false;
    if ($exists) {
        Database::get()->query("UPDATE course_review SET is_certified = ?d, level = ?d, 
            last_review = ?t, last_reviewer = ?d WHERE course_id = ?d", $is_certified, $level, $last_review, $uid, $course_id);
    } else {
        Database::get()->query("INSERT INTO course_review (course_id, is_certified, level, last_review, last_reviewer)
            VALUES (?d, ?d, ?d, ?s, ?d)", $course_id, $is_certified, $level, $last_review, $uid);
    }
}

// checkboxes
$checkedAMinusLevel = ($xmlData['course_confirmAMinusLevel'] == 'true') ? "checked='checked'" : '';
$checkedALevel = ($xmlData['course_confirmALevel'] == 'true') ? "checked='checked'" : '';
$checkedAPlusLevel = ($xmlData['course_confirmAPlusLevel'] == 'true') ? "checked='checked'" : '';
$disabledAMinusLevel = (!$looksAMinus && !$checkedAMinusLevel) ? "disabled='disabled'" : '';
$disabledALevel = (!$looksA && !$checkedALevel) ? "disabled='disabled'" : '';
$disabledAPlusLevel = (!$looksAPlus && !$checkedAPlusLevel) ? "disabled='disabled'" : '';

// images
$openAccessImg = ($hasOpenAccess) ? 'tick' : 'delete';
$mandatoryMetadataImg = ($hasMandatoryMetadata) ? 'tick' : 'delete';
$licenseImg = ($hasLicense) ? 'tick' : 'delete';
$teacherConfirmImg = ($hasTeacherConfirm) ? 'tick' : 'delete';
$docsImg = ($numDocs > 0) ? 'tick' : 'delete';
$unitsImg = ($numUnits > 0) ? 'tick' : 'delete';
$mediaImg = ($numMedia > 0) ? 'tick' : 'delete';
$teacherConfirmVideoImg = ($hasTeacherConfirmVideo) ? 'tick' : 'delete';

// parse last submission date
$lastSubmission = '';
if (isset($xmlData['course_lastLevelConfirmation']) &&
        strlen($xmlData['course_lastLevelConfirmation']) > 0 &&
        ($ts = strtotime($xmlData['course_lastLevelConfirmation'])) > 0) {
    $lastSubmission = '<p><small>' . $langLastSubmission . ': ' . date('j/n/Y H:i:s', $ts) . '</small></p>';
}

$tool_content .= "<form method='post' action='" . $_SERVER['SCRIPT_NAME'] . "?course=$course_code'>";
$tool_content .= <<<EOF
    <table class="tbl_courseid" width="100%">
    <tbody><tr>
      <td class="title1" colspan="2">$langOpenCoursesCharacteristics</td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesOpenAccess</td> 
      <td align="right"><img src="$themeimg/$openAccessImg.png" alt=""></td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesMandatoryMetadata</td>
      <td align="right"><img src="$themeimg/$mandatoryMetadataImg.png" alt=""></td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesLicense</td>
      <td align="right"><img src="$themeimg/$licenseImg.png" alt=""></td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesTeacherConfirm</td>
      <td align="right"><img src="$themeimg/$teacherConfirmImg.png" alt=""></td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesHasDocuments ($numDocs $langDoc)</td>
      <td align="right"><img src="$themeimg/$docsImg.png" alt=""></td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesHasUnits ($numUnits $langSections)</td>
      <td align="right"><img src="$themeimg/$unitsImg.png" alt=""></td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesHasMediaFiles ($numMedia $langOpenCoursesFiles)</td>
      <td align="right"><img src="$themeimg/$mediaImg.png" alt=""></td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesTeacherConfirmVideo</td>
      <td align="right"><img src="$themeimg/$teacherConfirmVideoImg.png" alt=""></td>
    </tr>
    </tbody></table>
    <br/>
    <table class="tbl_courseid" width="100%">
    <tbody><tr>
      <td class="title1" colspan="2">$langOpenCoursesCharacter</td>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesType <strong>$langOpenCoursesIsAMinusLevel</strong></td>
      <td align="right"><input type="checkbox" id="check_AMinus" name="course_confirmAMinusLevel" value="true" $checkedAMinusLevel $disabledAMinusLevel/>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesType <strong>$langOpenCoursesIsALevel</strong></td>
      <td align="right"><input type="checkbox" id="check_A" name="course_confirmALevel" value="true" $checkedALevel $disabledALevel/>
    </tr>
    <tr>
      <td class="smaller"><img src="$themeimg/arrow.png" alt="">&nbsp;$langOpenCoursesType <strong>$langOpenCoursesIsAPlusLevel</strong></td>
      <td align="right"><input type="checkbox" id="check_APlus" name="course_confirmAPlusLevel" value="true" $checkedAPlusLevel $disabledAPlusLevel/>
    </tr>
    </tbody></table>
    <br/>
    <p class='right'><input class='btn btn-primary' type='submit' name='submit' value='$langSubmit'></p>
    </form>
    <br/><br/>
    $lastSubmission
    <p>&laquo; <a href='{$urlServer}modules/course_info/index.php?course=$course_code'>$langBack</a></p>
    <p>&laquo; <a href='{$urlServer}courses/$course_code/index.php'>$langBackCourse</a></p>
EOF;

$head_content .= <<<EOF
    <script type='text/javascript'>
        $(document).ready(function() {

            $('#check_AMinus').click(function(event) {
                if (!$('#check_AMinus').is(":checked") && $('#check_A').is(":checked")) {
                    $('#check_A').attr('checked', false);
                }
                if (!$('#check_AMinus').is(":checked") && $('#check_APlus').is(":checked")) {
                    $('#check_APlus').attr('checked', false);
                }
            });
        
            $('#check_A').click(function(event) {
                if (!$('#check_A').is(":checked") && $('#check_APlus').is(":checked")) {
                    $('#check_APlus').attr('checked', false);
                }
            });

        });
    </script>
EOF;

draw($tool_content, 2, null, $head_content);
