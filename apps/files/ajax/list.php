<?php

// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem');

// Init owncloud


OCP\JSON::checkLoggedIn();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$doBreadcrumb = isset( $_GET['breadcrumb'] ) ? true : false;
$data = array();

// Make breadcrumb
if($doBreadcrumb){
	$breadcrumb = array();
	$pathtohere = "/";
	foreach( explode( "/", $dir ) as $i ){
		if( $i != "" ){
			$pathtohere .= "$i/";
			$breadcrumb[] = array( "dir" => $pathtohere, "name" => $i );
		}
	}
	
	$breadcrumbNav = new OCP\Template( "files", "part.breadcrumb", "" );
	$breadcrumbNav->assign( "breadcrumb", $breadcrumb );
	
	$data['breadcrumb'] = $breadcrumbNav->fetchPage();
}

// make filelist
$files = array();
foreach( OC_Files::getdirectorycontent( $dir ) as $i ){
	$i["date"] = OCP\Util::formatDate($i["mtime"] );
	$files[] = $i;
}

$list = new OCP\Template( "files", "part.list", "" );
$list->assign( "files", $files );
$data = array('files' => $list->fetchPage());

OCP\JSON::success(array('data' => $data));

?>
