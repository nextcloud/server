<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

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
	
	$breadcrumbNav = new OC_Template( "files", "part.breadcrumb", "" );
	$breadcrumbNav->assign( "breadcrumb", $breadcrumb );
	
	$data['breadcrumb'] = $breadcrumbNav->fetchPage();
}

// make filelist
$files = array();
foreach( OC_Files::getdirectorycontent( $dir ) as $i ){
	$i["date"] = OC_Util::formatDate($i["mtime"] );
	$files[] = $i;
}

$list = new OC_Template( "files", "part.list", "" );
$list->assign( "files", $files );
$data = array('files' => $list->fetchPage());

echo json_encode( array( "status" => "success", "data" => $data));

?>
