<?php

// Check if we are a user
OCP\User::checkLoggedIn();

OCP\Util::addScript('files_trashbin', 'trash');
OCP\Util::addScript('files_trashbin', 'disableDefaultActions');
OCP\Util::addScript('files', 'fileactions');
$tmpl = new OCP\Template('files_trashbin', 'index', 'user');

$user = \OCP\User::getUser();
$view = new OC_Filesystemview('/'.$user.'/files_trashbin');

OCP\Util::addStyle('files', 'files');
OCP\Util::addScript('files', 'filelist');

$dir = isset($_GET['dir']) ? stripslashes($_GET['dir']) : '';

if ($dir) {
	$dirlisting = true;
	$view = new \OC_FilesystemView('/'.\OCP\User::getUser().'/files_trashbin');
	$fullpath = \OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath($dir);
	$dirContent = opendir($fullpath);
	$i = 0;
	while($entryName = readdir($dirContent)) {
		if ( $entryName != '.' && $entryName != '..' ) {
			$pos = strpos($dir.'/', '/', 1);
			$tmp = substr($dir, 0, $pos);
			$pos = strrpos($tmp, '.d');
			$timestamp = substr($tmp,$pos+2);
			$result[] = array(
					'id' => $entryName,
					'timestamp' => $timestamp,
					'mime' =>  $view->getMimeType($dir.'/'.$entryName),
					'type' => $view->is_dir($dir.'/'.$entryName) ? 'dir' : 'file',
					'location' => $dir,
					);
		}
	}
	closedir($fullpath);
		
} else {
	$dirlisting = false;
	$query = \OC_DB::prepare('SELECT id,location,timestamp,type,mime FROM *PREFIX*files_trash WHERE user=?');
	$result = $query->execute(array($user))->fetchAll();
}

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

// Make breadcrumb
$breadcrumb = array(array('dir' => '', 'name' => 'Trash'));
$pathtohere = '';
foreach (explode('/', $dir) as $i) {
	if ($i != '') {
		if ( preg_match('/^(.+)\.d[0-9]+$/', $i, $match) ) {
			$name = $match[1];
		} else {
			$name = $i;
		}
		$pathtohere .= '/' . $i;
		$breadcrumb[] = array('dir' => $pathtohere, 'name' => $name);
	}
}

$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '');
$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
$breadcrumbNav->assign('baseURL', OCP\Util::linkTo('files_trashbin', 'index.php') . '?dir=', false);

$list = new OCP\Template('files_trashbin', 'part.list', '');
$list->assign('files', $files, false);
$list->assign('baseURL', OCP\Util::linkTo('files_trashbin', 'index.php'). '?dir='.$dir, false);
$list->assign('downloadURL', OCP\Util::linkTo('files_trashbin', 'download.php') . '?file='.$dir, false);
$list->assign('disableSharing', true);
$list->assign('dirlisting', $dirlisting);
$list->assign('disableDownloadActions', true);
$tmpl->assign('breadcrumb', $breadcrumbNav->fetchPage(), false);
$tmpl->assign('fileList', $list->fetchPage(), false);
$tmpl->assign('files', $files);
$tmpl->assign('dir', OC_Filesystem::normalizePath($view->getAbsolutePath()));

$tmpl->printPage();
