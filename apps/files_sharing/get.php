<?php
$RUNTIME_NOSETUPFS=true; //don't setup the fs yet

require_once '../../lib/base.php';
OC_JSON::checkAppEnabled('files_sharing');
require_once 'lib_share.php';

//get the path of the shared file
$token = $_GET['token'];
$source = OC_Share::getSource($token);
if ($source !== false) {
	// TODO Manipulating the string may not be the best choice. Is there an alternative?
	$user = substr($source, 1, strpos($source, "/", 1) - 1);
	OC_Util::setupFS($user);
	$source = substr($source, strlen("/".$user."/files"));
	$subPath = isset( $_GET['path'] ) ? $_GET['path'] : '';
	$root = $source;
	$source .= $subPath;
	if (!OC_Filesystem::file_exists($source)) {
		header("HTTP/1.0 404 Not Found");
		$tmpl = new OC_Template("", "404", "guest");
		$tmpl->assign("file", $subPath);
		$tmpl->printPage();
		exit;
	}
	if (OC_Filesystem::is_dir($source)) {
		$files = array();
		$rootLength = strlen($root);
		foreach (OC_Files::getdirectorycontent($source) as $i) {
			$i['date'] = OC_Util::formatDate($i['mtime'] );
			if ($i['type'] == 'file') {
				$fileinfo = pathinfo($i['name']);
				$i['basename'] = $fileinfo['filename'];
				$i['extention'] = isset($fileinfo['extension']) ? ('.'.$fileinfo['extension']) : '';
			}
			$i['directory'] = substr($i['directory'], $rootLength);
			if ($i['directory'] == "/") {
				$i['directory'] = "";
			}
			$files[] = $i;
		}
		// Make breadcrumb
		$breadcrumb = array();
		$pathtohere = "";
		foreach (explode("/", $subPath) as $i) {
			if ($i != "") {
				$pathtohere .= "/$i";
				$breadcrumb[] = array("dir" => $pathtohere, "name" => $i);
			}
		}
		// Load the files we need
		OC_Util::addStyle("files", "files");
		$breadcrumbNav = new OC_Template("files", "part.breadcrumb", "");
		$breadcrumbNav->assign("breadcrumb", $breadcrumb);
		$breadcrumbNav->assign("baseURL", OC_Helper::linkTo("files_sharing", "get.php")."?token=".$token."&path=");
		$list = new OC_Template("files", "part.list", "");
		$list->assign("files", $files);
		$list->assign("baseURL", OC_Helper::linkTo("files_sharing", "get.php")."?token=".$token."&path=");
		$list->assign("downloadURL", OC_Helper::linkTo("files_sharing", "get.php")."?token=".$token."&path=");
		$list->assign("readonly", true);
		$tmpl = new OC_Template("files", "index", "user");
		$tmpl->assign("fileList", $list->fetchPage());
		$tmpl->assign("breadcrumb", $breadcrumbNav->fetchPage());
		$tmpl->assign("readonly", true);
		$tmpl->printPage();
	} else {
		//get time mimetype and set the headers
		$mimetype = OC_Filesystem::getMimeType($source);
		header("Content-Transfer-Encoding: binary");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: public");
		header('Content-Disposition: filename="'.basename($source).'"');
		header("Content-Type: " . $mimetype);
		header("Content-Length: " . OC_Filesystem::filesize($source));
		//download the file
		@ob_clean();
		OC_Filesystem::readfile($source);
	}
} else {
	header("HTTP/1.0 404 Not Found");
	$tmpl = new OC_Template("", "404", "guest");
	$tmpl->printPage();
	die();
}
?>
