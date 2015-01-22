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

$require_login = TRUE;
if(isset($_GET['course'])) {//course messages
    $require_current_course = TRUE;
} else {//personal messages
    $require_current_course = FALSE;
}
$guest_allowed = FALSE;

include '../../include/baseTheme.php';
include 'include/lib/fileDisplayLib.inc.php';
require_once("class.msg.php");

if (!isset($course_id)) {
    $course_id = 0;
}

if (isset($_GET['mid'])) {
    $personal_msgs_allowed = get_config('dropbox_allow_personal_messages');
    
    $mid = intval($_GET['mid']);
    $msg = new Msg($mid, $uid, 'msg_view');
    if (!$msg->error) {
       
        $urlstr = '';
        if ($course_id != 0) {
            $urlstr = "?course=".$course_code;
        }
        $out = action_bar(array(
                            array('title' => $langBack,
                                  'url' => "inbox.php".$urlstr,
                                  'icon' => 'fa-reply',
                                  'level' => 'primary-label')
                        ));        
        $out .= "<div id='del_msg'></div><div id='msg_area'><table >";
        $out .= "<tr><td>$langSubject:</td><td>".q($msg->subject)."</td></tr>";
        $out .= "<tr id='$msg->id'><td>$langDelete:</td><td><img src=\"".$themeimg.'/delete.png'."\" class=\"delete\"/></td></tr>";
        if ($msg->course_id != 0 && $course_id == 0) {
            $out .= "<tr><td>$langCourse:</td><td><a class=\"outtabs\" href=\"index.php?course=".course_id_to_code($msg->course_id)."\">".course_id_to_title($msg->course_id)."</a></td></tr>";
        }
        $out .= "<tr><td>$langDate:</td><td>".nice_format(date('Y-m-d H:i:s',$msg->timestamp), true)."</td></tr>";
        $out .= "<tr><td>$langSender:</td><td>".display_user($msg->author_id, false, false, "outtabs")."</td></tr>";
        
        $recipients = '';
        foreach ($msg->recipients as $r) {
            if ($r != $msg->author_id) {
                $recipients .= display_user($r, false, false, "outtabs").'<br/>';
            }
        }
        
        $out .= "<tr><td>$langRecipients:</td><td>".$recipients."</td></tr>";
        $out .= "<tr><td>$langMessage:</td><td id='in_msg_body'>".standard_text_escape($msg->body)."</td></tr>";

        if ($msg->filename != '' && $msg->filesize != 0) {
            $out .= "<tr><td>$langAttachedFile</td><td><a href=\"dropbox_download.php?course=".course_id_to_code($msg->course_id)."&amp;id=$msg->id\" class=\"outtabs\" target=\"_blank\">$msg->real_filename
            <img class='outtabs' src='$themeimg/save.png' /></a>&nbsp;&nbsp;(".format_file_size($msg->filesize).")</td></tr>";
        }
        
        $out .= "</table><br/>";
        
        /*****Reply Form****/
        if ($msg->course_id == 0 && !$personal_msgs_allowed) {
            //do not show reply form when personal messages are not allowed
        } else {
            if ($course_id == 0) {
                $out .= "<form method='post' action='dropbox_submit.php' enctype='multipart/form-data' onsubmit='return checkForm(this)'>";
                if ($msg->course_id != 0) {//thread belonging to a course viewed from the central ui
                    $out .= "<input type='hidden' name='course' value='".course_id_to_code($msg->course_id)."' />";
                }
            } else {
                $out .= "<form method='post' action='dropbox_submit.php?course=$course_code' enctype='multipart/form-data' onsubmit='return checkForm(this)'>";
            }
            //hidden variables needed in case of a reply
            foreach ($msg->recipients as $rec) {
                if ($rec != $uid) {
                    $out .= "<input type='hidden' name='recipients[]' value='$rec' />";
                }
            }            
            $out .= "<fieldset>
                       <table width='100%' class='table table-bordered'>
                         <caption><b>$langReply</b></caption>
                         <tr>
                           <th>$langSender:</th>
                           <td>" . q(uid_to_name($uid)) . "</td>
    	                 </tr>
                         <tr>
                           <th>$langSubject:</th>
                           <td><input type='text' name='message_title' value='".$langMsgRe.$msg->subject."' /></td>
    	                 </tr>";
            $out .= "<tr>
                      <th>" . $langMessage . ":</th>
                      <td>".rich_text_editor('body', 4, 20, '')."
                        <small><br/>$langMaxMessageSize</small></td>
                     </tr>";
            if ($course_id != 0) {
                $out .= "<tr>
                       <th width='120'>$langFileName:</th>
                       <td><input type='file' name='file' size='35' />
                       </td>
                     </tr>";
            }            
            $out .= "<tr>
    	               <th>&nbsp;</th>
                       <td class='left'><input class='btn btn-primary' type='submit' name='submit' value='" . q($langSend) . "' />&nbsp;
                          $langMailToUsers<input type='checkbox' name='mailing' value='1' checked /></td>
                     </tr>
                   </table>
                 </fieldset>
               </form>
               <p class='right smaller'>$langMaxFileSize " . ini_get('upload_max_filesize') . "</p>";
    
             $out .= "<script type='text/javascript' src='{$urlAppend}js/select2-3.5.1/select2.min.js'></script>\n
                 <script type='text/javascript'>
                        $(document).ready(function () {
                            $('#select-recipients').select2();       
                            $('#selectAll').click(function(e) {
                                e.preventDefault();
                                var stringVal = [];
                                $('#select-recipients').find('option').each(function(){
                                    stringVal.push($(this).val());
                                });
                                $('#select-recipients').val(stringVal).trigger('change');
                            });
                            $('#removeAll').click(function(e) {
                                e.preventDefault();
                                var stringVal = [];
                                $('#select-recipients').val(stringVal).trigger('change');
                            });         
                        });

                        </script>";
        }
        /******End of Reply Form ********/
        
        $out .= "</div>"; 
         
        $out .= '<script>
                  $(function() {
                    $("#in_msg_body").find("a").addClass("outtabs");
                
                    $(".delete").click(function() {
                      if (confirm("' . $langConfirmDelete . '")) {
                        var rowContainer = $(this).parent().parent();
                        var id = rowContainer.attr("id");
                        var string = \'mid=\'+ id ;
            
                        $.ajax({
                          type: "POST",
                          url: "ajax_handler.php",
                          data: string,
                          cache: false,
                          success: function(){
                            $("#msg_area").slideUp(\'fast\', function() {
                              $(this).remove();
                              $("#del_msg").html("<p class=\'success\'>'.$langMessageDeleteSuccess.'</p>");
                            });
                          }
                       });
                       return false;
                     }
                   });
                 });
                 </script>';
        //head content has the scripts necessary for tinymce as a result of calling rich_text_editor
        $out .= $head_content;
    }
} else {
    
    $out = "<div id='del_msg'></div><div id='inbox'>";
    
    $out .= "<table id='inbox_table' class='table-default'>
                  <thead>
                    <tr>
                      <th>$langSubject</th>";
    if ($course_id == 0) {        
        $out .= "    <th>$langCourse</th>";
    }
    $out .= "         <th>$langSender</th>
                      <th>$langDate</th>
                      <th class='text-center option-btn-cell'><i class='fa fa-cogs'></i></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
              </table></div>";
    
    $out .= "<script type='text/javascript'>
               $(document).ready(function() {
                 var oTable = $('#inbox_table').dataTable({
                   'aoColumnDefs':[{'sClass':'option-btn-cell text-center', 'aTargets':[-1]}],
                   'bStateSave' : true,
                   'bProcessing': true,
                   'sDom': '<\"top\"fl<\"clear\">>rt<\"bottom\"ip<\"clear\">>',
                   'bServerSide': true,
                   'sAjaxSource': 'ajax_handler.php?mbox_type=inbox&course_id=$course_id',                   
                   'aLengthMenu': [
                       [10, 15, 20 , -1],
                       [10, 15, 20, '$langAllOfThem'] // change per page values here
                    ],
                   'sPaginationType': 'full_numbers',
                   'bSort': false,
                   'bAutoWidth' : false,
                   'fnDrawCallback': function( oSettings ) {
                        $('#inbox_table_filter label input').attr({
                          class : 'form-control input-sm',
                          placeholder : '$langSearch...'
                        });
                    },
                   'oLanguage': {                       
                        'sLengthMenu':   '$langDisplay _MENU_ $langResults2',
                        'sZeroRecords':  '".$langNoResult."',
                        'sInfo':         '$langDisplayed _START_ $langTill _END_ $langFrom2 _TOTAL_ $langTotalResults',
                        'sInfoEmpty':    '$langDisplayed 0 $langTill 0 $langFrom2 0 $langResults2',
                        'sInfoFiltered': '',
                        'sInfoPostFix':  '',
                        'sSearch':       '',
                        'sUrl':          '',
                        'oPaginate': {
                             'sFirst':    '&laquo;',
                             'sPrevious': '&lsaquo;',
                             'sNext':     '&rsaquo;',
                             'sLast':     '&raquo;'
                        }
                    }
                 }).fnSetFilteringDelay(1000);
                 
                 $(document).on( 'click','.delete_in', function (e) {
                     e.preventDefault();
                     var rowContainer = $(this).parent().parent();
                     var id = rowContainer.attr('id');
                     var string = 'mid='+id;
                     bootbox.confirm('$langConfirmDelete', function(result) {                       
                     if(result) {
                         $.ajax({
                          type: 'POST',
                          url: 'ajax_handler.php',
                          datatype: 'json',
                          data: string,
                          success: function(data){
                             var num_page_records = oTable.fnGetData().length;
                             var per_page = oTable.fnPagingInfo().iLength;
                             var page_number = oTable.fnPagingInfo().iPage;
                             if(num_page_records==1){
                                 if(page_number!=0) {
                                     page_number--;
                                 }
                             }
                             $('#del_msg').html('<p class=\'alert alert-success\'>$langMessageDeleteSuccess</p>');
                             $('.alert-success').delay(3000).fadeOut(1500);
                             oTable.fnPageChange(page_number);
                          },
                          error: function(xhr, textStatus, error){
                              console.log(xhr.statusText);
                              console.log(textStatus);
                              console.log(error);
                          }
                        });
                    }              
                    });
                  });
                 
                 $('.delete_all_in').click(function() {
                     bootbox.confirm('$langConfirmDeleteAllMsgs', function(result) {
                         if(result) {
                             var string = 'all_inbox=1';
                             $.ajax({
                                 type: 'POST',
                                 url: 'ajax_handler.php?course_id=$course_id',
                                 data: string,
                                 cache: false,
                                 success: function(){
                                     var num_page_records = oTable.fnGetData().length;
                                     var per_page = oTable.fnPagingInfo().iLength;
                                     var page_number = oTable.fnPagingInfo().iPage;
                                     if(num_page_records==1){
                                         if(page_number!=0) {
                                             page_number--;
                                         }
                                     }    
                                     $('#del_msg').html('<p class=\'alert alert-success\'>$langMessageDeleteAllSuccess</p>');
                                     $('.alert-success').delay(3000).fadeOut(1500);
                                     oTable.fnPageChange(page_number);
                                 }
                             });
                         }
                     })
                 });
                 
               });
             </script>";
}
echo $out;
    