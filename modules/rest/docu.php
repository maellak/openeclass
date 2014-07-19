<?php
function GetDoc($cid) { 
	$flag = 0;	
	$i = 0;	
	$error=array( 'error'=>0);
	$tempcode=0;
	for($i=0;$i<strlen($cid);$i++)
		{if($cid[$i] <= '0' || $cid[$i] >='9')
			{$flag = 1;}}
	if($flag == 0)
	{
		$document=array();
		$database = Database::get();
		$database->queryFunc("SELECT id,filename,path,date_modified,title,creator,format,comment,visible FROM document WHERE course_id=$cid", function($row)use (&$document) {$document[] = $row;} );
		$database->queryFunc("SELECT code FROM course WHERE id=$cid",function($row)use (&$temp_code){$temp_code = $row->code;});
		for($i=0;$i<count($document);$i++)
		{
			$document[$i]=(array)$document[$i];
			$tvis=$document[$i]['visible'];
			if( $tvis == 1 )
			{
			$tid=$document[$i]['id'];
			$tfilename=$document[$i]['filename'];
			$tpath=$document[$i]['path'];
			$tdate=$document[$i]['date_modified'];
			$tit=$document[$i]['title'];
			$tcreator=$document[$i]['creator'];
			$tformat=$document[$i]['format'];
			$tcom=$document[$i]['comment'];	
			$document[$i]=array('id'=>$tid,'title'=>$tit,'date'=>$tdate,'filename'=>$tfilename,'path'=>$temp_code.'/'.$tpath,'creator'=>$tcreator,'format'=>$tformat,'comment'=>$tcom);
			}
		}
		if($document!=null)
		{ 
			
			echo json_encode($document);
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
