<?php

// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem');

// Init owncloud


OCP\JSON::checkLoggedIn();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';

if (!\OC\Files\Filesystem::is_dir($dir . '/')) {
	header("HTTP/1.0 404 Not Found");
	exit();
}

$doBreadcrumb = isset($_GET['breadcrumb']);
$data = array();
$baseUrl = OCP\Util::linkTo('files', 'index.php') . '?dir=';

// Make breadcrumb
if($doBreadcrumb) {
	$breadcrumb = \OCA\files\lib\Helper::makeBreadcrumb($dir);

	$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '');
	$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
	$breadcrumbNav->assign('baseURL', $baseUrl);

	$data['breadcrumb'] = $breadcrumbNav->fetchPage();
}

// make filelist
$files = \OCA\files\lib\Helper::getFiles($dir);

$list = new OCP\Template("files", "part.list", "");
$list->assign('files', $files, false);
$list->assign('baseURL', $baseUrl, false);
$list->assign('downloadURL', OCP\Util::linkToRoute('download', array('file' => '/')));
$data['files'] = $list->fetchPage();

OCP\JSON::success(array('data' => $data));
