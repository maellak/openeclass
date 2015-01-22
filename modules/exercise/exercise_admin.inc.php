<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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
 * @file exercise_admin.inc.php
 * @brief Create new exercise or modify an existing one
 */
require_once 'modules/search/indexer.class.php';

// the exercise form has been submitted
if (isset($_POST['submitExercise'])) {
    $v = new Valitron\Validator($_POST);
    $v->rule('required', array('exerciseTitle'));
    $v->rule('numeric', array('exerciseTimeConstraint', 'exerciseAttemptsAllowed'));
    $v->labels(array(
        'exerciseTitle' => "$langTheField $langExerciseName",
        'exerciseTimeConstraint' => "$langTheField $langExerciseConstrain",
        'exerciseAttemptsAllowed' => "$langTheField $langExerciseAttemptsAllowed"
    ));
    if($v->validate()) {
        $exerciseTitle = trim($exerciseTitle);
        $exerciseDescription = purify($exerciseDescription);
        $randomQuestions = (isset($_POST['questionDrawn'])) ? intval($_POST['questionDrawn']) : 0;
        $objExercise->updateTitle($exerciseTitle);
        $objExercise->updateDescription($exerciseDescription);
        $objExercise->updateType($exerciseType);
        $startDateTime_obj = DateTime::createFromFormat('d-m-Y H:i',$exerciseStartDate);
        $objExercise->updateStartDate($startDateTime_obj->format('Y-m-d H:i:s'));
        $endDateTime_obj = DateTime::createFromFormat('d-m-Y H:i',$exerciseEndDate);
        $objExercise->updateEndDate($endDateTime_obj->format('Y-m-d H:i:s'));
        $objExercise->updateTempSave($exerciseTempSave);
        $objExercise->updateTimeConstraint($exerciseTimeConstraint);
        $objExercise->updateAttemptsAllowed($exerciseAttemptsAllowed);
        $objExercise->setRandom($randomQuestions);
        $objExercise->updateResults($dispresults);
        $objExercise->updateScore($dispscore);
        $objExercise->save();
        // reads the exercise ID (only useful for a new exercise)
        $exerciseId = $objExercise->selectId();
        Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
        redirect_to_home_page('modules/exercise/admin.php?course='.$course_code.'&exerciseId='.$exerciseId);        
    } else {
        $new_or_modify = isset($_GET['NewExercise']) ? "&NewExercise=Yes" : "&exerciseId=$_GET[exerciseId]&modifyExercise=yes";
        Session::flashPost()->Messages($langFormErrors)->Errors($v->errors());
        redirect_to_home_page('modules/exercise/admin.php?course='.$course_code.$new_or_modify);
    }    
} else {
    $exerciseId = $objExercise->selectId();
    $exerciseTitle = $objExercise->selectTitle();
    $exerciseDescription = $objExercise->selectDescription();
    $exerciseType = $objExercise->selectType();
    $startDateTime_obj = DateTime::createFromFormat('Y-m-d H:i:s', $objExercise->selectStartDate());
    $exerciseStartDate = $startDateTime_obj->format('d-m-Y H:i');
    $exerciseEndDate = $objExercise->selectEndDate();
    if ($exerciseEndDate == '') {
        $endDateTime_obj = new DateTime;
        $endDateTime_obj->add(new DateInterval('P1Y'));
        $exerciseEndDate = $endDateTime_obj->format('d-m-Y H:i');
    } else {
        $endDateTime_obj = DateTime::createFromFormat('Y-m-d H:i:s', $objExercise->selectEndDate());
        $exerciseEndDate = $endDateTime_obj->format('d-m-Y H:i'); 
    }
    $exerciseTempSave = $objExercise->selectTempSave();
    $exerciseTimeConstraint = $objExercise->selectTimeConstraint();
    $exerciseAttemptsAllowed = $objExercise->selectAttemptsAllowed();
    $randomQuestions = $objExercise->isRandom();
    $displayResults = $objExercise->selectResults();
    $displayScore = $objExercise->selectScore();
}

// shows the form to modify the exercise
if (isset($_GET['modifyExercise']) or isset($_GET['NewExercise'])) {
    load_js('bootstrap-datetimepicker');
    $head_content .= "<script type='text/javascript'>
        $(function() {
            $('#startdatepicker, #enddatepicker').datetimepicker({
                format: 'dd-mm-yyyy hh:ii', 
                pickerPosition: 'bottom-left', 
                language: '".$language."',
                autoclose: true    
            });
            $('.questionDrawnRadio').change(function() {
                if($(this).val()==0){
                    $('#questionDrawnInput').val(''); 
                    $('#questionDrawnInput').prop('disabled', true);
                    $('#questionDrawnInput').closest('div.form-group').addClass('hidden');
                } else {
                    $('#questionDrawnInput').prop('disabled', true);
                    $('#questionDrawnInput').closest('div.form-group').removeClass('hidden');
                }
            });            
            $('#randomDrawnSubset').change(function() {
                if($(this).prop('checked')){                   
                    $('#questionDrawnInput').prop('disabled', false);   
                    $('.questionDrawnRadio').prop('disabled', true); 
                } else {
                    $('#questionDrawnInput').prop('disabled', true);
                    $('.questionDrawnRadio').prop('disabled', false); 
                }
            });
        });
    </script>";
    $tool_content .= action_bar(array(
        array('title' => $langBack,
            'url' => $exerciseId ? "admin.php?course=$course_code&exerciseId=$exerciseId" : "index.php?course=$course_code",
            'icon' => 'fa-reply',
            'level' => 'primary-label'
        )
    ));    
   $tool_content .= "
        <div class='form-wrapper'>
            <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code".(isset($_GET['modifyExercise']) ? "&amp;exerciseId=$exerciseId" : "&amp;NewExercise=Yes")."'>
             <fieldset>       
                 <div class='form-group ".(Session::getError('exerciseTitle') ? "has-error" : "")."'>
                   <label for='exerciseTitle' class='col-sm-2 control-label'>$langExerciseName :</label>
                   <div class='col-sm-10'>
                     <input name='exerciseTitle' type='text' class='form-control' id='exerciseTitle' value='" . q($exerciseTitle) . "' placeholder='$langExerciseName'>
                     <span class='help-block'>".Session::getError('exerciseTitle')."</span>
                   </div>
                 </div>
                 <div class='form-group'>
                   <label for='exerciseDescription' class='col-sm-2 control-label'>$langExerciseDescription:</label>
                   <div class='col-sm-10'>
                   " . rich_text_editor('exerciseDescription', 4, 30, $exerciseDescription) . "
                   </div>
                 </div>
                 <div class='form-group'>
                     <label for='exerciseDescription' class='col-sm-2 control-label'>$langExerciseType:</label>
                     <div class='col-sm-10'>            
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseType' value='1' ".(($exerciseType <= 1)? 'checked' : '').">
                             $langSimpleExercise
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseType' value='2' ".(($exerciseType >= 2)? 'checked' : '').">
                             $langSequentialExercise
                           </label>
                         </div>
                     </div>
                 </div>              
                 <div class='input-append date form-group' id='startdatepicker' data-date='$exerciseStartDate' data-date-format='dd-mm-yyyy'>
                     <label for='exerciseStartDate' class='col-sm-2 control-label'>$langExerciseStart :</label>
                     <div class='col-xs-10 col-sm-9'>        
                         <input class='form-control' name='exerciseStartDate' id='exerciseStartDate' type='text' value='$exerciseStartDate'>
                     </div>
                     <div class='col-xs-2 col-sm-1'>  
                         <span class='add-on'><i class='fa fa-times'></i></span>
                         <span class='add-on'><i class='fa fa-calendar'></i></span>
                     </div>
                 </div>            
                 <div class='input-append date form-group' id='enddatepicker' data-date='$exerciseEndDate' data-date-format='dd-mm-yyyy'>
                     <label for='exerciseEndDate' class='col-sm-2 control-label'>$langExerciseEnd :</label>
                     <div class='col-xs-10 col-sm-9'>        
                         <input class='form-control' name='exerciseEndDate' id='exerciseEndDate' type='text' value='$exerciseEndDate'>
                     </div>
                     <div class='col-xs-2 col-sm-1'>  
                         <span class='add-on'><i class='fa fa-times'></i></span>
                         <span class='add-on'><i class='fa fa-calendar'></i></span>
                     </div>
                 </div>
                 <div class='form-group'>
                     <label for='exerciseTempSave' class='col-sm-2 control-label'>$langTemporarySave:</label>
                     <div class='col-sm-10'>            
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseTempSave' value='0' ".(($exerciseTempSave==0)? 'checked' : '').">
                             $langDeactivate
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseTempSave' value='1' ".(($exerciseTempSave==1)? 'checked' : '').">
                             $langActivate
                           </label>
                         </div>
                     </div>
                 </div>
                 <div class='form-group ".(Session::getError('exerciseTimeConstraint') ? "has-error" : "")."'>
                   <label for='exerciseTimeConstraint' class='col-sm-2 control-label'>$langExerciseConstrain:</label>
                   <div class='col-sm-10'>
                     <input type='text' class='form-control' name='exerciseTimeConstraint' id='exerciseTimeConstraint' value='$exerciseTimeConstraint' placeholder='$langExerciseConstrain'>
                     <span class='help-block'>".(Session::getError('exerciseTimeConstraint') ? Session::getError('exerciseTimeConstraint') : "$langExerciseConstrainUnit ($langExerciseConstrainExplanation)")."</span>
                   </div>
                 </div>
                 <div class='form-group ".(Session::getError('exerciseAttemptsAllowed') ? "has-error" : "")."'>
                   <label for='exerciseAttemptsAllowed' class='col-sm-2 control-label'>$langExerciseAttemptsAllowed:</label>
                   <div class='col-sm-10'>
                     <input type='text' class='form-control' name='exerciseAttemptsAllowed' id='exerciseAttemptsAllowed' value='$exerciseAttemptsAllowed' placeholder='$langExerciseConstrain'>
                     <span class='help-block'>".(Session::getError('exerciseAttemptsAllowed') ? Session::getError('exerciseAttemptsAllowed') : "$langExerciseAttemptsAllowedUnit ($langExerciseAttemptsAllowedExplanation)")."</span>
                   </div>
                 </div>
                 <div class='form-group'>
                     <label for='exerciseDescription' class='col-sm-2 control-label'>$langRandomQuestions:</label>
                     <div class='col-sm-10'>            
                         <div class='radio'>
                           <label>
                             <input type='radio' name='questionDrawn' class='questionDrawnRadio' value='0' ".(($randomQuestions == 0)? 'checked' : '').(($randomQuestions > 0 && $randomQuestions < 32767)? ' disabled' : '').">
                             $langDeactivate
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='questionDrawn' class='questionDrawnRadio' value='32767'".(($randomQuestions > 0)? ' checked' : '').(($randomQuestions > 0 && $randomQuestions < 32767)? ' disabled' : '').">
                             $langActivate
                           </label>
                         </div>
                     </div>
                 </div>                
                 <div class='form-group ".(($randomQuestions > 0)? '' : 'hidden')."'>
                    <div class='col-sm-5 col-sm-offset-2'>                 
                        <input type='text' class='form-control' name='questionDrawn' id='questionDrawnInput' value='".(($randomQuestions < 32767) ? $randomQuestions : null)."'".(($randomQuestions > 0 && $randomQuestions < 32767)? '' : 'disabled').">
                    </div>
                    <div class='col-sm-5'>                 
                        <div class='checkbox'>
                          <label>
                            <input id='randomDrawnSubset' value='1' type='checkbox' ".(($randomQuestions > 0 && $randomQuestions < 32767)? 'checked' : '').">
                            $langFromRandomQuestions
                          </label>
                        </div> 
                    </div>                   
                 </div>                    
                 <div class='form-group'>
                     <label for='dispresults' class='col-sm-2 control-label'>$langAnswers:</label>
                     <div class='col-sm-10'>            
                         <div class='radio'>
                           <label>
                             <input type='radio' name='dispresults' value='1' ".(($displayResults == 1)? 'checked' : '').">
                             $langAnswersDisp
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='dispresults' value='0' ".(($displayResults == 0)? 'checked' : '').">
                             $langAnswersNotDisp
                           </label>
                         </div>
                     </div>
                 </div>
                 <div class='form-group'>
                     <label for='dispresults' class='col-sm-2 control-label'>$langScore:</label>
                     <div class='col-sm-10'>            
                         <div class='radio'>
                           <label>
                             <input type='radio' name='dispscore' value='1' ".(($displayScore == 1)? 'checked' : '').">
                             $langScoreDisp
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='dispscore' value='0' ".(($displayScore == 0)? 'checked' : '').">
                             $langScoreNotDisp
                           </label>
                         </div>
                     </div>
                 </div>
                 <div class='form-group'>
                   <div class='col-sm-offset-2 col-sm-10'>
                     <input type='submit' class='btn btn-primary' name='submitExercise' value='".(isset($_GET['NewExercise']) ? $langCreate : $langModify)."'>
                     <a href='".(($exerciseId) ? "admin.php?course=$course_code&exerciseId=$exerciseId" : "index.php?course=$course_code")."' class='btn btn-default'>$langCancel</a>    
                   </div>
                 </div>
             </fieldset>
             </form>
        </div>";    
} else {
    
    $disp_results_message = ($displayResults == 1) ? $langAnswersDisp : $langAnswersNotDisp;
    $disp_score_message = ($displayScore == 1) ? $langScoreDisp : $langScoreNotDisp;
    $exerciseDescription = standard_text_escape($exerciseDescription);
    $exerciseStartDate = nice_format(date("Y-m-d H:i", strtotime($exerciseStartDate)), true);
    
    $exerciseEndDate = nice_format(date("Y-m-d H:i", strtotime($exerciseEndDate)), true);
    $exerciseType = ($exerciseType == 1) ? $langSimpleExercise : $langSequentialExercise ;
    $exerciseTempSave = ($exerciseTempSave ==1) ? $langActive : $langDeactivate;
    $tool_content .= action_bar(array(
        array('title' => $langBack,
            'url' => "index.php?course=$course_code",
            'icon' => 'fa-reply',
            'level' => 'primary-label'
        )
    ));    
    $tool_content .= "
    <div class='panel panel-primary'>
        <div class='panel-heading'>
            <h3 class='panel-title'>$langInfoExercise &nbsp;". icon('fa-edit', $langModify, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;exerciseId=$exerciseId&amp;modifyExercise=yes") ."</h3>
        </div>
        <div class='panel-body'>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langExerciseName:</strong>
                </div>
                <div class='col-sm-9'>
                    $exerciseTitle
                </div>                
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langExerciseDescription:</strong>
                </div>
                <div class='col-sm-9'>
                    $exerciseDescription
                </div>                
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langExerciseType:</strong>
                </div>
                <div class='col-sm-9'>
                    $exerciseType
                </div>                
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langExerciseStart:</strong>
                </div>
                <div class='col-sm-9'>
                    $exerciseStartDate
                </div>                
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langExerciseEnd:</strong>
                </div>
                <div class='col-sm-9'>
                    $exerciseEndDate
                </div>                
            </div>  
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langTemporarySave:</strong>
                </div>
                <div class='col-sm-9'>
                    $exerciseTempSave
                </div>                
            </div> 
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langExerciseConstrain:</strong>
                </div>
                <div class='col-sm-9'>
                    $exerciseTimeConstraint $langExerciseConstrainUnit
                </div>                
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langExerciseAttemptsAllowed:</strong>
                </div>
                <div class='col-sm-9'>
                    $exerciseAttemptsAllowed $langExerciseAttemptsAllowedUnit
                </div>                
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langRandomQuestions:</strong>
                </div>
                <div class='col-sm-9'>
                    $langSelection $randomQuestions $langFromRandomQuestions
                </div>                
            </div> 
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langAnswers:</strong>
                </div>
                <div class='col-sm-9'>
                    $disp_results_message
                </div>                
            </div>
            <div class='row  margin-bottom-fat'>
                <div class='col-sm-3'>
                    <strong>$langScore:</strong>
                </div>
                <div class='col-sm-9'>
                    $disp_score_message
                </div>                
            </div>              
        </div>
    </div>";
}
