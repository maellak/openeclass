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

$is_in_tinymce = (isset($_REQUEST['embedtype']) && $_REQUEST['embedtype'] == 'tinymce') ? true : false;

if (!defined('COMMON_DOCUMENTS')) {
    $require_current_course = TRUE;
    $menuTypeID = ($is_in_tinymce) ? 5 : 2;
} else {
    if ($is_in_tinymce) {
        $menuTypeID = 5;
    } else {
        $require_admin = TRUE;
        $menuTypeID = 3;
    }
}
$guest_allowed = true;
require_once '../../include/baseTheme.php';
/* * ** The following is added for statistics purposes ** */
require_once 'include/action.php';
$action = new action();
require_once 'doc_init.php';
require_once 'doc_metadata.php';
require_once 'include/lib/forcedownload.php';
require_once 'include/lib/fileDisplayLib.inc.php';
require_once 'include/lib/fileManageLib.inc.php';
require_once 'include/lib/fileUploadLib.inc.php';
require_once 'include/pclzip/pclzip.lib.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
require_once 'include/lib/mediaresource.factory.php';
require_once 'modules/search/indexer.class.php';
require_once 'include/log.php';

if ($is_in_tinymce) {
    $_SESSION['embedonce'] = true; // necessary for baseTheme
    $docsfilter = (isset($_REQUEST['docsfilter'])) ? 'docsfilter=' . $_REQUEST['docsfilter'] . '&amp;' : '';
    $base_url .= 'embedtype=tinymce&amp;' . $docsfilter;
    load_js('jquery-' . JQUERY_VERSION . '.min');
    load_js('tinymce.popup.urlgrabber.min.js');
}

load_js('tools.js');
ModalBoxHelper::loadModalBox(true);
copyright_info_init();

$require_help = TRUE;
$helpTopic = 'Doc';
$toolName = $langDoc;
$pageName = '';
// check for quotas
$diskUsed = dir_total_space($basedir);
if (defined('COMMON_DOCUMENTS')) {
    $diskQuotaDocument = $diskUsed + ini_get('upload_max_filesize') * 1024 * 1024;
} else {
    $type = ($subsystem == GROUP) ? 'group_quota' : 'doc_quota';
    $d = Database::get()->querySingle("SELECT $type as quotatype FROM course WHERE id = ?d", $course_id);
    $diskQuotaDocument = $d->quotatype;
}


if (isset($_GET['showQuota'])) {    
    if ($subsystem == GROUP) {
        $navigation[] = array('url' => 'index.php?course=' . $course_code . '&amp;group_id=' . $group_id, 'name' => $langDoc);
    } elseif ($subsystem == EBOOK) {
        $navigation[] = array('url' => 'index.php?course=' . $course_code . '&amp;ebook_id=' . $ebook_id, 'name' => $langDoc);
    } elseif ($subsystem == COMMON) {
        $navigation[] = array('url' => 'commondocs.php', 'name' => $langCommonDocs);
    } else {
        $navigation[] = array('url' => 'index.php?course=' . $course_code, 'name' => $langDoc);
    }
    $tool_content .= showquota($diskQuotaDocument, $diskUsed);
    draw($tool_content, $menuTypeID);
    exit;
}

// ---------------------------
// download directory or file
// ---------------------------
if (isset($_GET['download'])) {
    $downloadDir = $_GET['download'];

    if ($downloadDir == '/') {
        $format = '.dir';
        $real_filename = remove_filename_unsafe_chars($langDoc . ' ' . $public_code);
    } else {
        $q = Database::get()->querySingle("SELECT filename, format, visible, extra_path, public FROM document
                        WHERE $group_sql AND
                        path = ?s", $downloadDir);
        if (!$q) {
            not_found($downloadDir);
        }
        $real_filename = $q->filename;
        $format = $q->format;
        $visible = $q->visible;
        $extra_path = $q->extra_path;
        $public = $q->public;
        if (!(resource_access($visible, $public) or (isset($status) and $status == USER_TEACHER))) {        
            not_found($downloadDir);
        }
    }
    // Allow unlimited time for creating the archive
    set_time_limit(0);

    if ($format == '.dir') {
        $real_filename = $real_filename . '.zip';
        $dload_filename = $webDir . '/courses/temp/' . safe_filename('zip');
        zip_documents_directory($dload_filename, $downloadDir, $can_upload);
        $delete = true;
    } elseif ($extra_path) {
        if ($real_path = common_doc_path($extra_path, true)) {
            // Common document
            if (!$common_doc_visible) {
                forbidden($downloadDir);
            }
            $dload_filename = $real_path;
            $delete = false;
        } else {
            // External document - redirect to URL
            redirect($extra_path);
        }
    } else {
        $dload_filename = $basedir . $downloadDir;
        $delete = false;
    }

    send_file_to_client($dload_filename, $real_filename, null, true, $delete);
    exit;
}


if ($can_upload) {
    $error = false;
    $uploaded = false;
    if (isset($_POST['uploadPath'])) {
        $uploadPath = str_replace('\'', '', $_POST['uploadPath']);
    } else {
        $uploadPath = '';
    }
    // Check if upload path exists
    if (!empty($uploadPath)) {
        $result = Database::get()->querySingle("SELECT count(*) as total FROM document
                        WHERE $group_sql AND
                        path = ?s", $uploadPath);
        if (!$result || !$result->total) {
            $error = $langImpossible;
        }
    }

    /* ******************************************************************** *
      UPLOAD FILE
     * ******************************************************************** */

    $action_message = $dialogBox = '';
    if (isset($_FILES['userFile']) and is_uploaded_file($_FILES['userFile']['tmp_name'])) {
        validateUploadedFile($_FILES['userFile']['name'], $menuTypeID);
        $extra_path = '';
        $userFile = $_FILES['userFile']['tmp_name'];
        // check for disk quotas
        $diskUsed = dir_total_space($basedir);
        if ($diskUsed + @$_FILES['userFile']['size'] > $diskQuotaDocument) {
            $action_message .= "<div class='alert alert-danger'>$langNoSpace</div>";
        } else {
            if (unwanted_file($_FILES['userFile']['name'])) {
                $action_message .= "<div class='alert alert-danger'>$langUnwantedFiletype: " .
                        q($_FILES['userFile']['name']) . "</div>";
            } elseif (isset($_POST['uncompress']) and $_POST['uncompress'] == 1 and preg_match('/\.zip$/i', $_FILES['userFile']['name'])) {
                /* ** Unzipping stage ** */
                $zipFile = new pclZip($userFile);
                validateUploadedZipFile($zipFile->listContent(), $menuTypeID);
                $realFileSize = 0;
                $zipFile->extract(PCLZIP_CB_PRE_EXTRACT, 'process_extracted_file');
                if ($diskUsed + $realFileSize > $diskQuotaDocument) {
                    $action_message .= "<div class='alert alert-danger'>$langNoSpace</div>";
                } else {
                    $action_message .= "<div class='alert alert-success'>$langDownloadAndZipEnd</div><br />";
                }
            } else {
                $fileName = canonicalize_whitespace($_FILES['userFile']['name']);
                $uploaded = true;
            }
        }
    } elseif (isset($_POST['fileURL']) and ( $fileURL = trim($_POST['fileURL']))) {
        $extra_path = canonicalize_url($fileURL);
        if (preg_match('/^javascript/', $extra_path)) {
            $action_message .= "<div class='alert alert-danger'>$langUnwantedFiletype: " .
                    q($extra_path) . "</div>";
        } else {
            $uploaded = true;
        }
        $components = explode('/', $extra_path);
        $fileName = end($components);
    } elseif (isset($_POST['file_content'])) {
        $extra_path = '';
        $diskUsed = dir_total_space($basedir);
        if ($diskUsed + strlen($_POST['file_content']) > $diskQuotaDocument) {
            $action_message .= "<div class='alert alert-danger'>$langNoSpace</div>";
        } else {
            if (isset($_POST['file_name'])) {
                $fileName = $_POST['file_name'];
                if (!preg_match('/\.html?$/i', $fileName)) {
                    $fileName .= '.html';
                }
            }
            $uploaded = true;
        }
    }
    if ($uploaded and !isset($_POST['editPath'])) {
        // Check if file already exists
        $result = Database::get()->querySingle("SELECT path, visible FROM document WHERE
                                           $group_sql AND
                                           path REGEXP ?s AND
                                           filename = ?s LIMIT 1",
                                        "^$uploadPath/[^/]+$", $fileName);
        if ($result) {
            if (isset($_POST['replace'])) {
                // Delete old file record when replacing file
                $file_path = $result->path;
                $vis = $result->visible;
                Database::get()->query("DELETE FROM document WHERE
                                                 $group_sql AND
                                                 path = ?s", $file_path);
            } else {
                $error = $langFileExists;
            }
        }
    }
    if ($error) {
        $action_message .= "<div class='alert alert-danger'>$error</div><br>";
    } elseif ($uploaded) {
        // No errors, so proceed with upload
        // File date is current date
        $file_date = date("Y\-m\-d G\:i\:s");
        // Try to add an extension to files witout extension,
        // change extension of PHP files
        $fileName = php2phps(add_ext_on_mime($fileName));
        // File name used in file system and path field
        $safe_fileName = safe_filename(get_file_extension($fileName));
        if ($uploadPath == '.') {
            $file_path = '/' . $safe_fileName;
        } else {
            $file_path = $uploadPath . '/' . $safe_fileName;
        }
        if ($extra_path or (isset($userFile) and @copy($userFile, $basedir . $file_path))) {
            $vis = 1;
            $file_format = get_file_extension($fileName);
            $id = Database::get()->query("INSERT INTO document SET
                                        course_id = ?d,
                                        subsystem = ?d,
                                        subsystem_id = ?d,
                                        path = ?s,
                                        extra_path = ?s,
                                        filename = ?s,
                                        visible = ?d,
                                        comment = ?s,
                                        category = ?d,
                                        title = ?s,
                                        creator = ?s,
                                        date = ?t,
                                        date_modified = ?t,
                                        subject = ?s,
                                        description = ?s,
                                        author = ?s,
                                        format = ?s,
                                        language = ?s,
                                        copyrighted = ?d"
                            , $course_id, $subsystem, $subsystem_id, $file_path, $extra_path, $fileName, $vis
                            , $_POST['file_comment'], $_POST['file_category'], $_POST['file_title'], $_POST['file_creator']
                            , $file_date, $file_date, $_POST['file_subject'], $_POST['file_description'], $_POST['file_author']
                            , $file_format, $_POST['file_language'], $_POST['file_copyrighted'])->lastInsertID;
            Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_DOCUMENT, $id);
            // Logging
            Log::record($course_id, MODULE_ID_DOCS, LOG_INSERT, array('id' => $id,
                'filepath' => $file_path,
                'filename' => $fileName,
                'comment' => $_POST['file_comment'],
                'title' => $_POST['file_title']));
            Session::Messages($langDownloadEnd, 'alert-success');
            redirect($redirect_base_url);
        } elseif (isset($_POST['file_content'])) {
            $q = false;
            if (isset($_POST['editPath'])) {
                $fileInfo = Database::get()->querySingle("SELECT * FROM document
                    WHERE $group_sql AND path = ?s", $_POST['editPath']);
                if ($fileInfo->editable) {
                    $file_path = $fileInfo->path;
                    $q = Database::get()->query("UPDATE document
                            SET date_modified = NOW(), title = ?s
                            WHERE $group_sql AND path = ?s",
                            $_POST['file_title'], $_POST['editPath']);
                    $id = $fileInfo->id;
                    $fileName = $fileInfo->filename;
                }
            } else {
                $safe_fileName = safe_filename(get_file_extension($fileName));
                $file_path = $uploadPath . '/' . $safe_fileName;
                $file_date = date("Y\-m\-d G\:i\:s");
                $file_format = get_file_extension($fileName);
                $file_creator = "$_SESSION[givenname] $_SESSION[surname]";
                $q = Database::get()->query("INSERT INTO document SET
                            course_id = ?d,
                            subsystem = ?d,
                            subsystem_id = ?d,
                            path = ?s,
                            extra_path = '',
                            filename = ?s,
                            visible = 1,
                            comment = '',
                            category = 0,
                            title = ?s,
                            creator = ?s,
                            date = ?s,
                            date_modified = ?s,
                            subject = '',
                            description = '',
                            author = ?s,
                            format = ?s,
                            language = ?s,
                            copyrighted = 0,
                            editable = 1",
                            $course_id, $subsystem, $subsystem_id, $file_path,
                            $fileName, $_POST['file_title'], $file_creator,
                            $file_date, $file_date, $file_creator, $file_format,
                            $language);
            }
            if ($q) {
                if (!isset($id)) {
                    $id = $q->lastInsertID;
                    $log_action = LOG_INSERT;
                } else {
                    $log_action = LOG_MODIFY;
                }
                Log::record($course_id, MODULE_ID_DOCS, $log_action,
                        array('id' => $id,
                              'filepath' => $file_path,
                              'filename' => $fileName,
                              'title' => $_POST['file_title']));
                $action_message .= "<div class='alert alert-success'>$langDownloadEnd</div><br />";
                $title = $_POST['file_title']? $_POST['file_title']: $fileName;
                file_put_contents($basedir . $file_path,
                    '<!DOCTYPE html><head><meta charset="utf-8">' .
                    '<title>' . q($title) . '</title><body>' .
                    purify($_POST['file_content']) .
                    "</body></html>\n");
                Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_DOCUMENT, $id);
            }
            $curDirPath = dirname($file_path);
        }
    }

    /*     * ************************************
      MOVE FILE OR DIRECTORY
     * ************************************ */
    /* -------------------------------------
      MOVE FILE OR DIRECTORY : STEP 2
      -------------------------------------- */
    if (isset($_POST['moveTo'])) {
        $moveTo = $_POST['moveTo'];
        $source = $_POST['source'];
        $sourceXml = $source . '.xml';
        //check if source and destination are the same
        if ($basedir . $source != $basedir . $moveTo or $basedir . $source != $basedir . $moveTo) {
            $r = Database::get()->querySingle("SELECT filename, extra_path FROM document WHERE $group_sql AND path=?s", $source);
            $filename = $r->filename;
            $extra_path = $r->extra_path;
            if (empty($extra_path)) {
                if (move($basedir . $source, $basedir . $moveTo)) {
                    if (hasMetaData($source, $basedir, $group_sql)) {
                        move($basedir . $sourceXml, $basedir . $moveTo);
                    }
                    update_db_info('document', 'update', $source, $filename, $moveTo . '/' . my_basename($source));
                }
            } else {
                update_db_info('document', 'update', $source, $filename, $moveTo . '/' . my_basename($source));
            }
            $action_message = "<div class='alert alert-success'>$langDirMv</div><br>";
        } else {
            $action_message = "<div class='alert alert-danger'>$langImpossible</div><br>";
            /*             * * return to step 1 ** */
            $move = $source;
            unset($moveTo);
        }
    }

    /* -------------------------------------
      MOVE FILE OR DIRECTORY : STEP 1
      -------------------------------------- */
    if (isset($_GET['move'])) {
        $move = $_GET['move'];
        // h $move periexei to onoma tou arxeiou. anazhthsh onomatos arxeiou sth vash
        $moveFileNameAlias = Database::get()->querySingle("SELECT filename FROM document
                                                WHERE $group_sql AND path=?s", $move)->filename;
        $dialogBox .= directory_selection($move, 'moveTo', dirname($move));
    }

    /*     * ************************************
      DELETE FILE OR DIRECTORY
     * ************************************ */
    if (isset($_GET['delete']) and isset($_GET['filePath']) and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $filePath = $_GET['filePath'];
        // Check if file actually exists
        $r = Database::get()->querySingle("SELECT path, extra_path, format, filename FROM document
                                        WHERE $group_sql AND path = ?s", $filePath);
        $delete_ok = true;
        if ($r) {
            // remove from index if relevant (except non-main sysbsystems and metadata)
            Database::get()->queryFunc("SELECT id FROM document WHERE course_id >= 1 AND subsystem = 0
                                            AND format <> '.meta' AND path LIKE ?s",
                function ($r2) {
                    Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_DOCUMENT, $r2->id);
                },
                $filePath . '%');

            if (empty($r->extra_path)) {
                if ($delete_ok = my_delete($basedir . $filePath) && $delete_ok) {
                    if (hasMetaData($filePath, $basedir, $group_sql)) {
                        $delete_ok = my_delete($basedir . $filePath . ".xml") && $delete_ok;
                    }
                    update_db_info('document', 'delete', $filePath, $r->filename);
                }
            } else {
                update_db_info('document', 'delete', $filePath, $r->filename);
            }
            if ($delete_ok) {
                Session::Messages($langDocDeleted, 'alert-success');
            } else {
                Session::Messages($langGeneralError, 'alert-danger');
            }
            redirect($redirect_base_url);
        }
    }

    /*     * ***************************************
      RENAME
     * **************************************** */
    // Step 2: Rename file by updating record in database
    if (isset($_POST['renameTo'])) {

        $r = Database::get()->querySingle("SELECT id, filename, format FROM document WHERE $group_sql AND path = ?s", $_POST['sourceFile']);

        if ($r->format != '.dir') {
            validateRenamedFile($_POST['renameTo'], $menuTypeID);
        }

        Database::get()->query("UPDATE document SET filename= ?s, date_modified=NOW()
                          WHERE $group_sql AND path=?s"
                , $_POST['renameTo'], $_POST['sourceFile']);
        Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_DOCUMENT, $r->id);
        Log::record($course_id, MODULE_ID_DOCS, LOG_MODIFY, array('path' => $_POST['sourceFile'],
            'filename' => $r->filename,
            'newfilename' => $_POST['renameTo']));
        if (hasMetaData($_POST['sourceFile'], $basedir, $group_sql)) {
            if (Database::get()->query("UPDATE document SET filename=?s WHERE $group_sql AND path = ?s"
                            , ($_POST['renameTo'] . '.xml'), ($_POST['sourceFile'] . '.xml'))->affectedRows > 0) {
                metaRenameDomDocument($basedir . $_POST['sourceFile'] . '.xml', $_POST['renameTo']);
            }
        }
        Session::Messages($langElRen, 'alert-success');
        redirect_to_home_page($redirect_base_url, true);
    }

    // Step 1: Show rename dialog box
    if (isset($_GET['rename'])) {
        $fileName = Database::get()->querySingle("SELECT filename FROM document
                                             WHERE $group_sql AND
                                                   path = ?s", $_GET['rename'])->filename;
        $dialogBox .= "
            
            <div id='rename_doc_file' class='row'>
                <div class='col-xs-12'>
                    <div class='form-wrapper'>
                        <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code'>
                            <fieldset>                                
                                    <input type='hidden' name='sourceFile' value='" . q($_GET['rename']) . "' />
                                    $group_hidden_input
                                    <div class='form-group'>
                                        <label for='renameTo' class='col-sm-2 control-label word-wrapping' >" . q($fileName) . "</label>
                                        <div class='col-sm-10'>
                                            <input class='form-control' type='text' name='renameTo' value='" . q($fileName) . "' />
                                        </div>
                                    </div>
                                    <div class='form-group'>
                                        <div class='col-sm-offset-2 col-sm-10'>
                                            <input class='btn btn-primary' type='submit' value='$langRename' >
                                        </div>
                                    </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>";
    }

    // create directory
    // step 2: create the new directory
    if (isset($_POST['newDirPath'])) {
        $newDirName = canonicalize_whitespace($_POST['newDirName']);
        if (!empty($newDirName)) {
            $newDirPath = make_path($_POST['newDirPath'], array($newDirName));
            // $path_already_exists: global variable set by make_path()
            if ($path_already_exists) {
                $action_message = "<div class='alert alert-danger'>$langFileExists</div>";
            } else {
                $r = Database::get()->querySingle("SELECT id FROM document WHERE $group_sql AND path = ?s", $newDirPath);
                Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_DOCUMENT, $r->id);
                $action_message = "<div class='alert alert-success'>$langDirCr</div>";
            }
        }
    }

    // step 1: display a field to enter the new dir name
    if (isset($_GET['createDir'])) {
        $createDir = q($_GET['createDir']);
        $dialogBox .= "
        <div class='row'>        
        <div class='col-md-12'>            
                <div class='panel padding-thin focused'>
                    <form action='$_SERVER[SCRIPT_NAME]?course=$course_code' method='post' class='form-inline' role='form'>
                        $group_hidden_input
                        <input type='hidden' name='newDirPath' value='$createDir' />
                        <div class='form-group'>
                            <input type='text' class='form-control' id='newDirName' name='newDirName' placeholder='$langNameDir'>
                        </div>
                        <button type='submit' class='btn btn-primary'>
                            <i class='fa fa-plus space-after-icon'></i>
                            $langCreateDir
                        </button>
                    </form>
                </div>
            </div>
        </div>";
    }

    // add/update/remove comment
    if (isset($_POST['commentPath'])) {
        $commentPath = $_POST['commentPath'];
        // check if file exists
        $res = Database::get()->querySingle("SELECT * FROM document
                                             WHERE $group_sql AND
                                                   path=?s", $commentPath);
        if ($res) {
            $file_language = validate_language_code($_POST['file_language'], $language);
            Database::get()->query("UPDATE document SET
                                                comment = ?s,
                                                category = ?d,
                                                title = ?s,
                                                date_modified = NOW(),
                                                subject = ?s,
                                                description = ?s,
                                                author = ?s,
                                                language = ?s,
                                                copyrighted = ?d
                                        WHERE $group_sql AND
                                              path = ?s"
                    , $_POST['file_comment'], $_POST['file_category'], $_POST['file_title'], $_POST['file_subject']
                    , $_POST['file_description'], $_POST['file_author'], $file_language, $_POST['file_copyrighted'], $commentPath);
            Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_DOCUMENT, $res->id);
            Log::record($course_id, MODULE_ID_DOCS, LOG_MODIFY, array('path' => $commentPath,
                'filename' => $res->filename,
                'comment' => $_POST['file_comment'],
                'title' => $_POST['file_title']));
            $action_message = "<div class='alert alert-success'>$langComMod</div>";
        }
    }

    // add/update/remove metadata
    // h $metadataPath periexei to path tou arxeiou gia to opoio tha epikyrwthoun ta metadata
    if (isset($_POST['metadataPath'])) {

        $metadataPath = $_POST['metadataPath'] . ".xml";
        $oldFilename = $_POST['meta_filename'] . ".xml";
        $xml_filename = $basedir . str_replace('/..', '', $metadataPath);
        $xml_date = date("Y\-m\-d G\:i\:s");
        $file_format = ".meta";

        metaCreateDomDocument($xml_filename);

        $result = Database::get()->querySingle("SELECT * FROM document WHERE $group_sql AND path = ?s", $metadataPath);
        if ($result) {
            Database::get()->query("UPDATE document SET
                                creator = ?s,
                                date_modified = NOW(),
                                format = ?s,
                                language = ?s
                                WHERE $group_sql AND path = ?s"
                    , ($_SESSION['givenname'] . " " . $_SESSION['surname']), $file_format, $_POST['meta_language'], $metadataPath);
        } else {
            Database::get()->query("INSERT INTO document SET
                                course_id = ?d ,
                                subsystem = ?d ,
                                subsystem_id = ?d ,
                                path = ?s,
                                filename = ?s ,
                                visible = 0,
                                creator = ?s,
                                date = ?t ,
                                date_modified = ?t ,
                                format = ?s,
                                language = ?s"
                    , $course_id, $subsystem, $subsystem_id, $metadataPath, $oldFilename
                    , ($_SESSION['givenname'] . " " . $_SESSION['surname']), $xml_date, $xml_date, $file_format, $_POST['meta_language']);
        }

        $action_message = "<div class='alert alert-success'>$langMetadataMod</div>";
    }

    if (isset($_POST['replacePath']) and
            isset($_FILES['newFile']) and
            is_uploaded_file($_FILES['newFile']['tmp_name'])) {
        validateUploadedFile($_FILES['newFile']['name'], $menuTypeID);
        $replacePath = $_POST['replacePath'];
        // Check if file actually exists
        $result = Database::get()->querySingle("SELECT id, path, format FROM document WHERE
                                        $group_sql AND
                                        format <> '.dir' AND
                                        path=?s", $replacePath);
        if ($result) {
            $docId = $result->id;
            $oldpath = $result->path;
            $oldformat = $result->format;
            // check for disk quota
            $diskUsed = dir_total_space($basedir);
            if ($diskUsed - filesize($basedir . $oldpath) + $_FILES['newFile']['size'] > $diskQuotaDocument) {
                $action_message = "<div class='alert alert-danger'>$langNoSpace</div>";
            } elseif (unwanted_file($_FILES['newFile']['name'])) {
                $action_message = "<div class='alert alert-danger'>$langUnwantedFiletype: " .
                        q($_FILES['newFile']['name']) . "</div>";
            } else {
                $newformat = get_file_extension($_FILES['newFile']['name']);
                $newpath = preg_replace("/\\.$oldformat$/", '', $oldpath) .
                        (empty($newformat) ? '' : '.' . $newformat);
                my_delete($basedir . $oldpath);
                $affectedRows = Database::get()->query("UPDATE document SET path = ?s, format = ?s, filename = ?s, date_modified = NOW()
                          WHERE $group_sql AND path = ?s"
                                , $newpath, $newformat, ($_FILES['newFile']['name']), $oldpath)->affectedRows;
                if (!copy($_FILES['newFile']['tmp_name'], $basedir . $newpath) or $affectedRows == 0) {
                    $action_message = "<div class='alert alert-danger'>$langGeneralError</div>";
                } else {
                    if (hasMetaData($oldpath, $basedir, $group_sql)) {
                        rename($basedir . $oldpath . ".xml", $basedir . $newpath . ".xml");
                        Database::get()->query("UPDATE document SET path = ?s, filename=?s WHERE $group_sql AND path = ?s"
                                , ($newpath . ".xml"), ($_FILES['newFile']['name'] . ".xml"), ($oldpath . ".xml"));
                    }
                    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_DOCUMENT, $docId);
                    Log::record($course_id, MODULE_ID_DOCS, LOG_MODIFY, array('oldpath' => $oldpath,
                        'newpath' => $newpath,
                        'filename' => $_FILES['newFile']['name']));
                    $action_message = "<div class='alert alert-success'>$langReplaceOK</div>";
                }
            }
        }
    }

    // Display form to add external file link
    if (isset($_GET['link'])) {
        $comment = $_GET['comment'];
        $oldComment = '';
        /*         * * Retrieve the old comment and metadata ** */
        $row = Database::get()->querySingle("SELECT * FROM document WHERE $group_sql AND path = ?s", $comment);
        if ($row) {
            $oldFilename = q($row->filename);
            $oldComment = q($row->comment);
            $oldCategory = $row->category;
            $oldTitle = q($row->title);
            $oldCreator = q($row->creator);
            $oldDate = q($row->date);
            $oldSubject = q($row->subject);
            $oldDescription = q($row->description);
            $oldAuthor = q($row->author);
            $oldLanguage = q($row->language);
            $oldCopyrighted = $row->copyrighted;

            // filsystem compability: ean gia to arxeio den yparxoun dedomena sto pedio filename
            // (ara to arxeio den exei safe_filename (=alfarithmitiko onoma)) xrhsimopoihse to
            // $fileName gia thn provolh tou onomatos arxeiou
            $fileName = my_basename($comment);
            if (empty($oldFilename))
                $oldFilename = $fileName;
            $dialogBox .= "
                        <form method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code'>
                        <fieldset>
                          <legend>$langAddComment</legend>
                          <input type='hidden' name='commentPath' value='" . q($comment) . "' />
                          <input type='hidden' size='80' name='file_filename' value='$oldFilename' />
                          $group_hidden_input
                          <table class='table-default'>
                          <tr>
                            <th>$langWorkFile:</th>
                            <td>$oldFilename</td>
                          </tr>
                          <tr>
                            <th>$langTitle:</th>
                            <td><input type='text' name='file_title' value='$oldTitle' /></td>
                          </tr>
                          <tr>
                            <th>$langComment:</th>
                            <td><input type='text' size='60' name='file_comment' value='$oldComment' /></td>
                          </tr>
                          <tr>
                            <th>$langCategory:</th>
                            <td>" .
                    selection(array('0' => $langCategoryOther,
                        '1' => $langCategoryExcercise,
                        '2' => $langCategoryLecture,
                        '3' => $langCategoryEssay,
                        '4' => $langCategoryDescription,
                        '5' => $langCategoryExample,
                        '6' => $langCategoryTheory), 'file_category', $oldCategory) . "</td>
                          </tr>
                          <tr>
                            <th>$langSubject : </th>
                            <td><input type='text' size='60' name='file_subject' value='$oldSubject' /></td>
                          </tr>
                          <tr>
                            <th>$langDescription : </th>
                            <td><input type='text' size='60' name='file_description' value='$oldDescription' /></td>
                          </tr>
                          <tr>
                            <th>$langAuthor : </th>
                            <td><input type='text' size='60' name='file_author' value='$oldAuthor' /></td>
                          </tr>";
            $dialogBox .= "<tr><th>$langCopyrighted : </th><td>";
            $dialogBox .= selection($copyright_titles, 'file_copyrighted', $oldCopyrighted) . "</td></tr>";

            // display combo box for language selection
            $dialogBox .= "
                                <tr>
                                <th>$langLanguage :</th>
                                <td>" .
                    selection(array('en' => $langEnglish,
                        'fr' => $langFrench,
                        'de' => $langGerman,
                        'el' => $langGreek,
                        'it' => $langItalian,
                        'es' => $langSpanish), 'file_language', $oldLanguage) .
                    "</td>
                        </tr>
                        <tr>
                        <th>&nbsp;</th>
                        <td class='right'><input class='btn btn-primary' type='submit' value='$langOkComment' /></td>
                        </tr>
                        <tr>
                        <th>&nbsp;</th>
                        <td class='right'>$langNotRequired</td>
                        </tr>
                        </table>
                        <input type='hidden' size='80' name='file_creator' value='$oldCreator' />
                        <input type='hidden' size='80' name='file_date' value='$oldDate' />
                        <input type='hidden' size='80' name='file_oldLanguage' value='$oldLanguage' />
                        </fieldset>
                        </form>";
        } else {
            $action_message = "<div class='alert alert-danger'>$langFileNotFound</div>";
        }
    }

    // Display form to replace/overwrite an existing file
    if (isset($_GET['replace'])) {
        $result = Database::get()->querySingle("SELECT filename FROM document
                                        WHERE $group_sql AND
                                                format <> '.dir' AND
                                                path = ?s", $_GET['replace']);
        if ($result) {
            $filename = q($result->filename);
            $replacemessage = sprintf($langReplaceFile, '<b>' . $filename . '</b>');
            $dialogBox = "<div class='form-wrapper'>
                        <form class='form-horizontal' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code' enctype='multipart/form-data'>
                        <fieldset>
                        <input type='hidden' name='replacePath' value='" . q($_GET['replace']) . "' />
                        $group_hidden_input
                        <div class='form-group'>
                            <label class='col-sm-5 control-label'>$replacemessage</label>
                            <div class='col-sm-7'><input type='file' name='newFile' size='35' /></div>
                        </div>
                        <div class='form-group'>
                            <div class='col-sm-offset-3 col-sm-9'>
                                <input class='btn btn-primary' type='submit' value='$langReplace' />
                            </div>
                        </div>
                        </fieldset>
                        </form></div>";
        }
    }

    // Add comment form
    if (isset($_GET['comment'])) {
        $comment = $_GET['comment'];
        $oldComment = '';
        /*         * * Retrieve the old comment and metadata ** */
        $row = Database::get()->querySingle("SELECT * FROM document WHERE $group_sql AND path = ?s", $comment);
        if ($row) {
            $oldFilename = q($row->filename);
            $oldComment = q($row->comment);
            $oldCategory = $row->category;
            $oldTitle = q($row->title);
            $oldCreator = q($row->creator);
            $oldDate = q($row->date);
            $oldSubject = q($row->subject);
            $oldDescription = q($row->description);
            $oldAuthor = q($row->author);
            $oldLanguage = q($row->language);
            $oldCopyrighted = $row->copyrighted;

            // filsystem compability: ean gia to arxeio den yparxoun dedomena sto pedio filename
            // (ara to arxeio den exei safe_filename (=alfarithmitiko onoma)) xrhsimopoihse to
            // $fileName gia thn provolh tou onomatos arxeiou
            $fileName = my_basename($comment);
            if (empty($oldFilename))
                $oldFilename = $fileName;
                $dialogBox .= "<div class='form-wrapper'>
                        <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code'>
                        <fieldset>
                          <input type='hidden' name='commentPath' value='" . q($comment) . "' />
                          <input type='hidden' size='80' name='file_filename' value='$oldFilename' />
                          $group_hidden_input
                          <div class='form-group'>
                          <label class='col-sm-2 control-label'>$langWorkFile:</label>
                              <span>$oldFilename</span>
                          </div>
                          <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langTitle:</label>
                            <div class='col-sm-10'><input class='form-control' type='text' name='file_title' value='$oldTitle'></div>
                          </div>
                          <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langComment:</label>
                            <div class='col-sm-10'><input class='form-control' type='text' name='file_comment' value='$oldComment'></div>
                          </div>
                          <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langCategory:</label>
                            <div class='col-sm-10'>" .
                                selection(array('0' => $langCategoryOther,
                                    '1' => $langCategoryExcercise,
                                    '2' => $langCategoryLecture,
                                    '3' => $langCategoryEssay,
                                    '4' => $langCategoryDescription,
                                    '5' => $langCategoryExample,
                                    '6' => $langCategoryTheory), 'file_category', $oldCategory, "class='form-control'") . "</div>
                          </div>
                          <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langSubject:</label>
                            <div class='col-sm-10'><input class='form-control' type='text' name='file_subject' value='$oldSubject'></div>
                          </div>
                          <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langDescription:</label>
                            <div class='col-sm-10'><input class='form-control' type='text' name='file_description' value='$oldDescription'></div>
                          </div>
                          <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langAuthor:</label>
                            <div class='col-sm-10'><input class='form-control' type='text' name='file_author' value='$oldAuthor'></div>
                          </div>
                        <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langCopyrighted:</label>
                            <div class='col-sm-10'>"
                                 .selection($copyright_titles, 'file_copyrighted', $oldCopyrighted, "class='form-control'") . 
                            "</div>
                        </div>
                        <div class='form-group'>
                                <label class='col-sm-2 control-label'>$langLanguage:</label>
                                <div class='col-sm-10'>" .
                                    selection(array('en' => $langEnglish,
                                        'fr' => $langFrench,
                                        'de' => $langGerman,
                                        'el' => $langGreek,
                                        'it' => $langItalian,
                                        'es' => $langSpanish), 'file_language', $oldLanguage, "class='form-control'") .
                                "</div>
                        </div>
                        <div class='form-group'>
                            <div class='col-sm-offset-2 col-sm-10'>
                                <input class='btn btn-primary' type='submit' value='$langOkComment'>
                            </div>
                        </div>
                        <span class='help-block'>$langNotRequired</span>                       
                        <input type='hidden' size='80' name='file_creator' value='$oldCreator'>
                        <input type='hidden' size='80' name='file_date' value='$oldDate'>
                        <input type='hidden' size='80' name='file_oldLanguage' value='$oldLanguage'>
                        </fieldset>
                        </form></div>";
        } else {
            $action_message = "<div class='alert alert-danger'>$langFileNotFound</div>";
        }
    }

    // Emfanish ths formas gia tropopoihsh metadata
    if (isset($_GET['metadata'])) {

        $metadata = $_GET['metadata'];
        $row = Database::get()->querySingle("SELECT filename FROM document WHERE $group_sql AND path = ?s", $metadata);
        if ($row) {
            $oldFilename = q($row->filename);

            // filesystem compability: ean gia to arxeio den yparxoun dedomena sto pedio filename
            // (ara to arxeio den exei safe_filename (=alfarithmitiko onoma)) xrhsimopoihse to
            // $fileName gia thn provolh tou onomatos arxeiou
            $fileName = my_basename($metadata);
            if (empty($oldFilename))
                $oldFilename = $fileName;
            $real_filename = $basedir . str_replace('/..', '', q($metadata));

            $dialogBox .= metaCreateForm($metadata, $oldFilename, $real_filename);
        } else {
            $action_message = "<div class='alert alert-danger'>$langFileNotFound</div>";
        }
    }

    // Visibility commands
    if (isset($_GET['mkVisibl']) || isset($_GET['mkInvisibl'])) {
        if (isset($_GET['mkVisibl'])) {
            $newVisibilityStatus = 1;
            $visibilityPath = $_GET['mkVisibl'];
        } else {
            $newVisibilityStatus = 0;
            $visibilityPath = $_GET['mkInvisibl'];
        }
        Database::get()->query("UPDATE document SET visible=?d
                                          WHERE $group_sql AND
                                                path = ?s", $newVisibilityStatus, $visibilityPath);
        $r = Database::get()->querySingle("SELECT id FROM document WHERE $group_sql AND path = ?s", $visibilityPath);
        Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_DOCUMENT, $r->id);
        Session::Messages($langViMod, 'alert-success');
        redirect_to_home_page("modules/document/index.php?course=$course_code");
    }

    // Public accessibility commands
    if (isset($_GET['public']) || isset($_GET['limited'])) {
        $new_public_status = intval(isset($_GET['public']));
        $path = isset($_GET['public']) ? $_GET['public'] : $_GET['limited'];
        Database::get()->query("UPDATE document SET public = ?d
                                          WHERE $group_sql AND
                                                path = ?s", $new_public_status, $path);
        $r = Database::get()->querySingle("SELECT id FROM document WHERE $group_sql AND path = ?s", $path);
        Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_DOCUMENT, $r->id);
        $action_message = "<div class='alert alert-success'>$langViMod</div>";
    }
} // teacher only
// Common for teachers and students
// define current directory
// Check if $var is set and return it - if $is_file, then return only dirname part

function pathvar(&$var, $is_file = false) {
    static $found = false;
    if ($found) {
        return '';
    }
    if (isset($var)) {
        $found = true;
        $var = str_replace('..', '', $var);
        if ($is_file) {
            return dirname($var);
        } else {
            return $var;
        }
    }
    return '';
}

if (!isset($curDirPath)) {
    $curDirPath = pathvar($_GET['openDir'], false) .
        pathvar($_GET['createDir'], false) .
        pathvar($_POST['moveTo'], false) .
        pathvar($_POST['newDirPath'], false) .
        pathvar($_POST['uploadPath'], false) .
        pathvar($_POST['filePath'], true) .
        pathvar($_GET['move'], true) .
        pathvar($_GET['rename'], true) .
        pathvar($_GET['replace'], true) .
        pathvar($_GET['comment'], true) .
        pathvar($_GET['metadata'], true) .
        pathvar($_GET['mkInvisibl'], true) .
        pathvar($_GET['mkVisibl'], true) .
        pathvar($_GET['public'], true) .
        pathvar($_GET['limited'], true) .
        pathvar($_POST['sourceFile'], true) .
        pathvar($_POST['replacePath'], true) .
        pathvar($_POST['commentPath'], true) .
        pathvar($_POST['metadataPath'], true);
}

if ($curDirPath == '/' or $curDirPath == '\\') {
    $curDirPath = '';
}
$curDirName = my_basename($curDirPath);
$parentDir = dirname($curDirPath);
if ($parentDir == '\\') {
    $parentDir = '/';
}

if (strpos($curDirName, '/../') !== false or ! is_dir(realpath($basedir . $curDirPath))) {
    $tool_content .= $langInvalidDir;
    draw($tool_content, $menuTypeID);
    exit;
}

$order = 'ORDER BY filename';
$sort = 'name';
$reverse = false;
if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'type') {
        $order = 'ORDER BY format';
        $sort = 'type';
    } elseif ($_GET['sort'] == 'date') {
        $order = 'ORDER BY date_modified';
        $sort = 'date';
    }
}
if (isset($_GET['rev'])) {
    $order .= ' DESC';
    $reverse = true;
}

list($filter, $compatiblePlugin) = (isset($_REQUEST['docsfilter'])) ? select_proper_filters($_REQUEST['docsfilter']) : array('', true);

/* * * Retrieve file info for current directory from database and disk ** */
$result = Database::get()->queryArray("SELECT * FROM document
                        WHERE $group_sql AND
                                path LIKE '$curDirPath/%' AND
                                path NOT LIKE '$curDirPath/%/%' $filter $order");

$fileinfo = array();
foreach ($result as $row) {
    if ($real_path = common_doc_path($row->extra_path, true)) {
        // common docs
        if (!$common_doc_visible and !$is_admin) {
            // hide links to invisible common docs to non-admins
            continue;
        }
        $path = $real_path;
    } else {
        $path = $basedir . $row->path;
    }
    if (!$real_path and $row->extra_path) {
        // external file
        $size = 0;
    } else {
        $size = file_exists($path)? filesize($path): 0;
    }
    $fileinfo[] = array(
        'is_dir' => ($row->format == '.dir'),
        'size' => $size,
        'title' => $row->title,
        'filename' => $row->filename,
        'format' => $row->format,
        'path' => $row->path,
        'extra_path' => $row->extra_path,
        'visible' => ($row->visible == 1),
        'public' => $row->public,
        'comment' => $row->comment,
        'copyrighted' => $row->copyrighted,
        'date' => $row->date_modified,
        'object' => MediaResourceFactory::initFromDocument($row),
        'editable' => $row->editable);
}
// end of common to teachers and students
// ----------------------------------------------
// Display
// ----------------------------------------------

$cmdCurDirPath = rawurlencode($curDirPath);
$cmdParentDir = rawurlencode($parentDir);

if ($can_upload) {
    // Action result message
    if (!empty($action_message)) {
        $tool_content .= $action_message;
    }
    // available actions
    if (!$is_in_tinymce) {
        if (isset($_GET['rename'])) {
            $pageName = $langRename;
        }
        if (isset($_GET['move'])) {
            $pageName = $langMove;
        }
        if (isset($_GET['createDir'])) {
            $pageName = $langCreateDir;
        }
        if (isset($_GET['comment'])) {
            $pageName = $langAddComment;
        }
        if (isset($_GET['replace'])) {
            $pageName = $langReplace;
        }
        $diskQuotaDocument = $diskQuotaDocument * 1024 / 1024;
        $tool_content .= action_bar(array(
            array('title' => $langDownloadFile,
                  'url' => "upload.php?course=$course_code&amp;{$groupset}uploadPath=$curDirPath",
                  'icon' => 'fa-plus-circle',
                  'level' => 'primary-label',
                  'button-class' => 'btn-success'),
            array('title' => $langCreateDir,
                  'url' => "{$base_url}createDir=$cmdCurDirPath",
                  'icon' => 'fa-folder',
                  'level' => 'primary'),
            array('title' => $langQuotaBar,
                  'url' => "{$base_url}showQuota=true",
                  'icon' => 'fa-pie-chart'),
            array('title' => $langExternalFile,
                  'url' => "upload.php?course=$course_code&amp;{$groupset}uploadPath=$curDirPath&amp;ext=true",
                  'icon' => 'fa-external-link'),
            array('title' => $langCommonDocs,
                  'url' => "../units/insert.php?course=$course_code&amp;dir=$curDirPath&amp;type=doc&amp;id=-1",
                  'icon' => 'fa-plus-circle',
                  'show' => !defined('COMMON_DOCUMENTS') && get_config('enable_common_docs')),
            ));
    }
    // Dialog Box
    if (!empty($dialogBox)) {
        $tool_content .= $dialogBox;
    }
}

// check if there are documents
$doc_count = Database::get()->querySingle("SELECT COUNT(*) as count FROM document WHERE $group_sql $filter" .
                ($can_upload ? '' : " AND visible=1"))->count;
if ($doc_count == 0) {
    $tool_content .= "<div class='alert alert-warning'>$langNoDocuments</div>";
} else {
    // Current Directory Line
    $tool_content .= "
    <div class='row'>
        <div class='col-md-12'>
            <div class='panel'>
                <div class='panel-body'>";
    if ($can_upload) {
        $cols = 4;
    } else {
        $cols = 3;
    }

    $download_path = empty($curDirPath) ? '/' : $curDirPath;
    $download_dir = ($is_in_tinymce) ? '' : "<a href='{$base_url}download=$download_path'><img src='$themeimg/save_s.png' width='16' height='16' align='middle' alt='$langDownloadDir' title='$langDownloadDir'></a>";
    $tool_content .= "
        <div class='pull-left'><b>$langDirectory:</b> " . make_clickable_path($curDirPath) .
            "&nbsp;$download_dir</div>
        ";


    /*     * * go to parent directory ** */
    if ($curDirName) { // if the $curDirName is empty, we're in the root point and we can't go to a parent dir
        $parentlink = $base_url . 'openDir=' . $cmdParentDir;
        $tool_content.=" <div class='pull-right'>
                            <a href='$parentlink' type='button' class='btn btn-success'><i class='fa fa-level-up'></i> $langUp</a>
                        </div>";

    }
    $tool_content .= "</div>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class='col-md-12'>
                <div class='table-responsive'>
                <table class='table-default'>
                    <tr>";
    $tool_content .= "<th class='center'><b>" . headlink($langType, 'type') . '</b></th>' .
                     "<th><div class='text-left'>" . headlink($langName, 'name') . '</div></th>' .
                     "<th class='center'><b>$langSize</b></th>" .
                     "<th class='text-center'><b>" . headlink($langDate, 'date') . '</b></th>';
    if (!$is_in_tinymce) {
        $tool_content .= "<th class='text-center'>".icon('fa-gears', $langCommands)."</th>";
    }
    $tool_content .= "</tr>";

    // -------------------------------------
    // Display directories first, then files
    // -------------------------------------
    foreach (array(true, false) as $is_dir) {
        foreach ($fileinfo as $entry) {
            $link_title_extra = '';
            if (($entry['is_dir'] != $is_dir) or ( !$can_upload and ( !resource_access($entry['visible'], $entry['public'])))) {
                continue;
            }
            $cmdDirName = $entry['path'];
            if (!$entry['visible']) {
                $style = ' class="not_visible"';
            } else {
                $style = '';
            }
            if ($is_dir) {
                $img_href = icon('fa-folder-o');
                $file_url = $base_url . "openDir=$cmdDirName";
                $link_title = q($entry['filename']);
                $dload_msg = $langDownloadDir;
                $link_href = "<a href='$file_url'>$link_title</a>";
            } else {
                $img_href = icon(choose_image('.' . $entry['format']));
                $file_url = file_url($cmdDirName, $entry['filename']);
                if ($entry['extra_path']) {
                    $cdpath = common_doc_path($entry['extra_path']);
                    if ($cdpath) {
                        if ($can_upload) {
                            if ($common_doc_visible) {
                                $link_title_extra .= '&nbsp;' .
                                    icon('common', $langCommonDocLink);
                            } else {
                                $link_title_extra .= '&nbsp;' .
                                    icon('common-invisible', $langCommonDocLinkInvisible);
                                $style = ' class="invisible"';
                            }
                        }
                    } else {
                        // External file URL
                        $file_url = $entry['extra_path'];
                        if ($is_editor) {
                            $link_title_extra .= '&nbsp;external';
                        }
                    }
                }
                if ($can_upload and $entry['editable']) {
                    $edit_url = "new.php?course=$course_code&amp;editPath=$entry[path]" .
                        ($groupset? "&amp;$groupset": '');
                    $link_title_extra .= '&nbsp;' .
                        icon('edit', $langEdit, $edit_url);
                }
                if ($copyid = $entry['copyrighted'] and
                    $copyicon = $copyright_icons[$copyid]) {
                    $link_title_extra .= "&nbsp;" .
                        icon($copyicon, $copyright_titles[$copyid], $copyright_links[$copyid], null, 'png', 'target="_blank"');
                }
                $dload_msg = $langSave;

                $dObj = $entry['object'];
                $dObj->setAccessURL($file_url);
                $dObj->setPlayURL(file_playurl($cmdDirName, $entry['filename']));
                if ($is_in_tinymce && !$compatiblePlugin) // use Access/DL URL for non-modable tinymce plugins
                    $dObj->setPlayURL($dObj->getAccessURL());

                $link_href = MultimediaHelper::chooseMediaAhref($dObj);
            }
            if (!$entry['extra_path'] or common_doc_path($entry['extra_path'])) {
                // Normal or common document
                $download_url = $base_url . "download=$cmdDirName";
            } else {
                // External document
                $download_url = $entry['extra_path'];
            }
            $tool_content .= "<tr $style><td class='text-center'>$img_href</td>
                              <td>$link_href $link_title_extra";
            // comments
            if (!empty($entry['comment'])) {
                $tool_content .= "<br><span class='comment'>" .
                        nl2br(htmlspecialchars($entry['comment'])) .
                        "</span>";
            }
            $tool_content .= "</td>";
            $date = nice_format($entry['date'], true, true);
            $date_with_time = nice_format($entry['date'], true);
            if ($is_dir) {
                $tool_content .= "<td>&nbsp;</td><td class='center'>$date</td>";
            } else if ($entry['format'] == ".meta") {
                $size = format_file_size($entry['size']);
                $tool_content .= "<td class='center'>$size</td><td class='center'>$date</td>";
            } else {
                $size = format_file_size($entry['size']);
                $tool_content .= "<td class='center'>$size</td><td class='center' title='$date_with_time'>$date</td>";
            }
            if (!$is_in_tinymce) {
                if ($can_upload) {
                    $tool_content .= "<td class='option-btn-cell'>";

                    $xmlCmdDirName = ($entry['format'] == ".meta" && get_file_extension($cmdDirName) == "xml") ? substr($cmdDirName, 0, -4) : $cmdDirName;
                    $tool_content .= action_button(array(
                                    array('title' => $langGroupSubmit,
                                          'url' => "{$urlAppend}modules/work/group_work.php?course=$course_code&amp;group_id=$group_id&amp;submit=$cmdDirName",
                                          'icon' => 'fa-book',
                                          'show' => $subsystem == GROUP and isset($is_member) and $is_member),
                                    array('title' => $dload_msg,
                                          'url' => $download_url,
                                          'icon' => 'fa-save'),
                                    array('title' => $langVisible,
                                          'url' => "{$base_url}" . ($entry['visible']? "mkInvisibl=$cmdDirName" : "mkVisibl=$cmdDirName"),
                                          'icon' => $entry['visible'] ? 'fa-eye' : 'fa-eye-slash'),
                                    array('title' => $langResourceAccess,
                                          'url' => "{$base_url}limited=$cmdDirName",
                                          'icon' => 'fa-unlock',
                                          'show' => $course_id > 0 and course_status($course_id) == COURSE_OPEN and $entry['public']),
                                    array('title' => $langMove,
                                          'url' => "{$base_url}move=$cmdDirName",
                                          'icon' => 'fa-arrows',
                                          'show' => $entry['format'] != '.meta'),
                                    array('title' => $langRename,
                                          'url' => "{$base_url}rename=$cmdDirName",
                                          'icon' => 'fa-repeat',
                                          'show' => $entry['format'] != '.meta'),
                                    array('title' => $langComments,
                                          'url' => "{$base_url}comment=$cmdDirName",
                                          'icon' => 'fa-comment-o',
                                          'show' => $entry['format'] != '.meta'),
                                    array('title' => $langReplace,
                                          'url' => "{$base_url}replace=$cmdDirName",
                                          'icon' => 'fa-reply',
                                          'show' => !$is_dir && $entry['format'] != '.meta'),
                                    array('title' => $langMetadata,
                                          'url' =>  "{$base_url}metadata=$xmlCmdDirName",
                                          'icon' => 'fa-tags',
                                          'show' => get_config("insert_xml_metadata")),
                                    array('title' => $langResourceAccess,
                                          'url' => "{$base_url}public=$cmdDirName",
                                          'icon' => 'fa-lock',
                                          'show' => $course_id > 0 and course_status($course_id) == COURSE_OPEN and !$entry['public']),
                                    array('title' => $langDelete,
                                          'url' => "{$base_url}filePath=$cmdDirName&amp;delete=1",
                                          'icon' => 'fa-times',
                                          'class' => 'delete',
                                          'confirm' => "$langConfirmDelete $entry[filename]")));
                    $tool_content .= "</td>";
                } else { // student view
                    $tool_content .= "<td class='text-center'>" . icon('fa-save', $dload_msg, $download_url) . "</td>";
                }
            }
            $tool_content .= "</tr>";
        }
    }
    $tool_content .= "</table>
            </div>
        </div>
    </div>";
    if ($can_upload && !$is_in_tinymce) {
        $tool_content .= "<br><div class='text-right'>$langMaxFileSize " . ini_get('upload_max_filesize') . "</div>";
    }
}
if (defined('SAVED_COURSE_CODE')) {
    $course_code = SAVED_COURSE_CODE;
    $course_id = SAVED_COURSE_ID;
}
add_units_navigation(TRUE);
draw($tool_content, $menuTypeID, null, $head_content);

function select_proper_filters($requestDocsFilter) {
    $filter = '';
    $compatiblePlugin = true;

    switch ($requestDocsFilter) {
        case 'image':
            $ors = '';
            foreach (MultimediaHelper::getSupportedImages() as $imgfmt)
                $ors .= " OR format LIKE '$imgfmt'";
            $filter = "AND (format LIKE '.dir' $ors)";
            break;
        case 'eclmedia':
            $ors = '';
            foreach (MultimediaHelper::getSupportedMedia() as $mediafmt)
                $ors .= " OR format LIKE '$mediafmt'";
            $filter = "AND (format LIKE '.dir' $ors)";
            break;
        case 'media':
            $compatiblePlugin = false;
            $ors = '';
            foreach (MultimediaHelper::getSupportedMedia() as $mediafmt)
                $ors .= " OR format LIKE '$mediafmt'";
            $filter = "AND (format LIKE '.dir' $ors)";
            break;
        case 'zip':
            $filter = "AND (format LIKE '.dir' OR FORMAT LIKE 'zip')";
            break;
        case 'file':
            $filter = '';
            break;
        default:
            break;
    }

    return array($filter, $compatiblePlugin);
}


/**
 * @brief Link for sortable table headings
 * @global type $sort
 * @global type $reverse
 * @global type $curDirPath
 * @global type $base_url
 * @global type $themeimg
 * @global type $langUp
 * @global type $langDown
 * @param type $label
 * @param type $this_sort
 * @return type
 */
function headlink($label, $this_sort) {
    global $sort, $reverse, $curDirPath, $base_url, $themeimg, $langUp, $langDown;

    if (empty($curDirPath)) {
        $path = '/';
    } else {
        $path = $curDirPath;
    }
    if ($sort == $this_sort) {
        $this_reverse = !$reverse;
        $indicator = " <img src='$themeimg/arrow_" .
                ($reverse ? 'up' : 'down') . ".png' alt='" .
                ($reverse ? $langUp : $langDown) . "'>";
    } else {
        $this_reverse = $reverse;
        $indicator = '';
    }
    return '<a href="' . $base_url . 'openDir=' . $path .
            '&amp;sort=' . $this_sort . ($this_reverse ? '&amp;rev=1' : '') .
            '">' . $label . $indicator . '</a>';
}


/**
 * Used in documents path navigation bar
 * @global type $langRoot
 * @global type $base_url
 * @global type $group_sql
 * @param type $path
 * @return type
 */
function make_clickable_path($path) {
    global $langRoot, $base_url, $group_sql;

    $cur = $out = '';
    foreach (explode('/', $path) as $component) {
        if (empty($component)) {
            $out = "<a href='{$base_url}openDir=/'>$langRoot</a>";
        } else {
            $cur .= rawurlencode("/$component");
            $row = Database::get()->querySingle("SELECT filename FROM document
                                        WHERE path LIKE '%/$component' AND $group_sql");
            $dirname = $row->filename;
            $out .= " &raquo; <a href='{$base_url}openDir=$cur'>".q($dirname)."</a>";
        }
    }
    return $out;
}
