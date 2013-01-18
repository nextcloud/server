<?php

// Check if we are a user
OCP\User::checkLoggedIn();

OCP\Util::addStyle('files_trashbin', 'trash');
OCP\Util::addScript('files_trashbin', 'trash');
OCP\Util::addScript('files', 'fileactions');
$tmpl = new OCP\Template('files_trashbin', 'index', 'user');

$user = \OCP\User::getUser();
$view = new OC_Filesystemview('/'.$user.'/files_trashbin');

OCP\Util::addStyle('files', 'files');
OCP\Util::addScript('files', 'filelist');

$query = \OC_DB::prepare('SELECT id,location,timestamp,type,mime FROM *PREFIX*files_trash WHERE user=?');
$result = $query->execute(array($user))->fetchAll();

$files = array();
foreach ($result as $r) {
	$i = array();
	$i['name'] = $r['id'];
	$i['date'] = OCP\Util::formatDate($r['timestamp']);
	$i['timestamp'] = $r['timestamp'];
	$i['mimetype'] = $r['mime'];
	$i['type'] = $r['type'];
	if ($i['type'] == 'file') {
		$fileinfo = pathinfo($r['id']);
		$i['basename'] = $fileinfo['filename'];
		$i['extension'] = isset($fileinfo['extension']) ? ('.'.$fileinfo['extension']) : '';
	}
	$i['directory'] = $r['location'];
	if ($i['directory'] == '/') {
		$i['directory'] = '';
	}
	$i['permissions'] = OCP\PERMISSION_READ;
	$files[] = $i;
}

$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '');
$breadcrumbNav->assign('breadcrumb', array(array('dir' => '', 'name' => 'Trash')), false);
$breadcrumbNav->assign('baseURL', OCP\Util::linkTo('files_trashbin', 'index.php') . '?dir=', false);

$list = new OCP\Template('files_trashbin', 'part.list', '');
$list->assign('files', $files, false);
$list->assign('baseURL', OCP\Util::linkTo('files_trashbin', 'index.php'). '?dir=', false);
$list->assign('downloadURL', OCP\Util::linkTo('files_trashbin', 'download.php') . '?file=', false);
$list->assign('disableSharing', true);
$list->assign('disableDownloadActions', true);
$tmpl->assign('breadcrumb', $breadcrumbNav->fetchPage(), false);
$tmpl->assign('fileList', $list->fetchPage(), false);
$tmpl->assign('dir', OC_Filesystem::normalizePath($view->getAbsolutePath()));

$tmpl->printPage();
