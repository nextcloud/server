<?php

require_once '../../lib/base.php';

$force=isset($_GET['force']) and $_GET['force']=='true';
$checkOnly=isset($_GET['checkonly']) and $_GET['checkonly']=='true';

//create the file cache if necesary
if($force or !OC_FileCache::inCache('')){
	if(!$checkOnly){
		OC_FileCache::scan('');
	}
	OC_JSON::success(array("data" => array( "done" => true)));
}else{
	OC_JSON::success(array("data" => array( "done" => false)));
}