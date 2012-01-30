<?php

require_once '../../lib/base.php';

$eventSource=new OC_EventSource();

$force=isset($_GET['force']) and $_GET['force']=='true';
$checkOnly=isset($_GET['checkonly']) and $_GET['checkonly']=='true';

//create the file cache if necesary
if($force or !OC_FileCache::inCache('')){
	if(!$checkOnly){
		OC_FileCache::scan('',false,$eventSource);
	}
	$eventSource->send('success',true);
}else{
	$eventSource->send('success',false);
}
$eventSource->close();