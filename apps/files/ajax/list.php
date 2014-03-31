<?php

OCP\JSON::checkLoggedIn();
\OC::$session->close();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$dir = \OC\Files\Filesystem::normalizePath($dir);
$dirInfo = \OC\Files\Filesystem::getFileInfo($dir);
if (!$dirInfo->getType() === 'dir') {
	header("HTTP/1.0 404 Not Found");
	exit();
}

$doBreadcrumb = isset($_GET['breadcrumb']);
$data = array();
$baseUrl = OCP\Util::linkTo('files', 'index.php') . '?dir=';

$permissions = $dirInfo->getPermissions();

// Make breadcrumb
if($doBreadcrumb) {
	$breadcrumb = \OCA\Files\Helper::makeBreadcrumb($dir);

	$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '');
	$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
	$breadcrumbNav->assign('baseURL', $baseUrl);

	$data['breadcrumb'] = $breadcrumbNav->fetchPage();
}

// make filelist
$files = \OCA\Files\Helper::getFiles($dir);

$list = new OCP\Template("files", "part.list", "");
$list->assign('files', $files, false);
$list->assign('baseURL', $baseUrl, false);
$list->assign('downloadURL', OCP\Util::linkToRoute('download', array('file' => '/')));
$list->assign('isPublic', false);
$data['files'] = $list->fetchPage();
$data['permissions'] = $permissions;

OCP\JSON::success(array('data' => $data));
