<?php
function GetAnn($cid) {
	$flag = 0;	
	$i = 0;	
	$error=array( 'error'=>0);
	for($i=0;$i<strlen($cid);$i++)
		{if($cid[$i] <= '0' || $cid[$i] >='9')
			{$flag = 1;}}
	if($flag == 0)
	{
		$annou=array();
		$database = Database::get();
		$database->queryFunc("SELECT title,content,date FROM announcement WHERE course_id=$cid", function($row)use (&$annou) {$annou[] = 			$row;} );
		if($annou!=null)
		{ 
			echo json_encode($annou);
			echo json_encode($error);
		}
		else 
		{
			$error=array( 'error'=>1);
			echo json_encode($error);
		}
	}
	else
	{
		$error=array( 'error'=>1);
		echo json_encode($error);
	}		
}
?>
