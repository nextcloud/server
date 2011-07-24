<?php
$RUNTIME_NOAPPS=true; //no need to load the apps
$RUNTIME_NOSETUPFS=true; //don't setup the fs yet

require_once '../../lib/base.php';
require( 'template.php' );

require_once 'lib_public.php';

//get the path of the shared file
$token=$_GET['token'];
$path=OC_PublicLink::getPath($token);
$root=$path;

if($path!==false){
	if(isset($_GET['path']) and !strstr($_GET['path'],'..')){
		$subPath=$_GET['path'];
	}else{
		$subPath='';
	}
	$path.=$subPath;
	if(!OC_FILESYSTEM::file_exists($path)){
		header("HTTP/1.0 404 Not Found");
		$tmpl = new OC_TEMPLATE( '', '404', 'guest' );
		$tmpl->assign('file',$subPath);
		$tmpl->printPage();
		exit;
	}
	if(OC_FILESYSTEM::is_dir($path)){
		$files = array();
		$rootLength=strlen($root);
		foreach( OC_FILES::getdirectorycontent( $path ) as $i ){
			$i['date'] = OC_UTIL::formatDate($i['mtime'] );
			$i['directory']=substr($i['directory'],$rootLength);
			if($i['directory']=='/'){
				$i['directory']='';
			}
			$files[] = $i;
		}
		
		// Make breadcrumb
		$breadcrumb = array();
		$pathtohere = "/";
		foreach( explode( "/", $subPath ) as $i ){
			if( $i != "" ){
				$pathtohere .= "$i/";
				$breadcrumb[] = array( "dir" => $pathtohere, "name" => $i );
			}
		}
		
		$breadcrumbNav = new OC_TEMPLATE( "files_publiclink", "breadcrumb", "" );
		$breadcrumbNav->assign( "breadcrumb", $breadcrumb );
		$breadcrumbNav->assign('token',$token);
		
		$list = new OC_TEMPLATE( 'files_publiclink', 'files', '' );
		$list->assign( 'files', $files );
		$list->assign('token',$token);
		
		$tmpl = new OC_TEMPLATE( 'files_publiclink', 'index', 'user' );
		$tmpl->assign('fileList', $list->fetchPage());
		$tmpl->assign( "breadcrumb", $breadcrumbNav->fetchPage() );
		$tmpl->printPage();
	}else{
		//get time mimetype and set the headers
		$mimetype=OC_FILESYSTEM::getMimeType($path);
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: attachment; filename="'.basename($path).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Type: ' . $mimetype);
		header('Content-Length: ' . OC_FILESYSTEM::filesize($path));
		
		//download the file
		@ob_clean();
		OC_FILESYSTEM::readfile($path);
	}
}else{
	header("HTTP/1.0 404 Not Found");
	$tmpl = new OC_TEMPLATE( '', '404', 'guest' );
	$tmpl->printPage();
	die();
}
?>