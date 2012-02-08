<?php

require_once '../../lib/base.php';

set_time_limit(0);//scanning can take ages

$force=isset($_GET['force']) and $_GET['force']=='true';
$checkOnly=isset($_GET['checkonly']) and $_GET['checkonly']=='true';

if(!$checkOnly){
	$eventSource=new OC_EventSource();
}


//create the file cache if necesary
if($force or !OC_FileCache::inCache('')){
	if(!$checkOnly){
		OC_DB::beginTransaction();
		OC_FileCache::scan('',$eventSource);
		OC_DB::commit();
		$eventSource->send('success',true);
	}else{
		OC_JSON::success(array('data'=>array('done'=>true)));
		exit;
	}
}else{
	if($checkOnly){
		OC_JSON::success(array('data'=>array('done'=>false)));
		exit;
	}
	if(isset($eventSource)){
		$eventSource->send('success',false);
	}else{
		exit;
	}
}
$eventSource->close();