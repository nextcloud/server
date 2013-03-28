<?php

// Check if we are a user
OCP\User::checkLoggedIn();

OCP\App::setActiveNavigationEntry('files_index');

OCP\Util::addScript('files_trashbin', 'trash');
OCP\Util::addScript('files_trashbin', 'disableDefaultActions');
OCP\Util::addScript('files', 'fileactions');
$tmpl = new OCP\Template('files_trashbin', 'index', 'user');

$user = \OCP\User::getUser();
$view = new OC_Filesystemview('/'.$user.'/files_trashbin/files');

OCP\Util::addStyle('files', 'files');
OCP\Util::addScript('files', 'filelist');

$dir = isset($_GET['dir']) ? stripslashes($_GET['dir']) : '';

$result = array();
if ($dir) {
	$dirlisting = true;
	$fullpath = \OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath($dir);
	$dirContent = opendir($fullpath);
	$i = 0;
	while($entryName = readdir($dirContent)) {
		if ( $entryName != '.' && $entryName != '..' ) {
			$pos = strpos($dir.'/', '/', 1);
			$tmp = substr($dir, 0, $pos);
			$pos = strrpos($tmp, '.d');
			$timestamp = substr($tmp, $pos+2);
			$result[] = array(
					'id' => $entryName,
					'timestamp' => $timestamp,
					'mime' =>  $view->getMimeType($dir.'/'.$entryName),
					'type' => $view->is_dir($dir.'/'.$entryName) ? 'dir' : 'file',
					'location' => $dir,
					);
		}
	}
	closedir($dirContent);

} else {
	$dirlisting = false;
	$query = \OC_DB::prepare('SELECT `id`,`location`,`timestamp`,`type`,`mime` FROM `*PREFIX*files_trash` WHERE `user` = ?');
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

function fileCmp($a, $b) {
	if ($a['type'] == 'dir' and $b['type'] != 'dir') {
		return -1;
	} elseif ($a['type'] != 'dir' and $b['type'] == 'dir') {
		return 1;
	} else {
		return strnatcasecmp($a['name'], $b['name']);
	}
}

usort($files, "fileCmp");

// Make breadcrumb
$pathtohere = '';
$breadcrumb = array();
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

$breadcrumbNav = new OCP\Template('files_trashbin', 'part.breadcrumb', '');
$breadcrumbNav->assign('breadcrumb', $breadcrumb);
$breadcrumbNav->assign('baseURL', OCP\Util::linkTo('files_trashbin', 'index.php') . '?dir=');
$breadcrumbNav->assign('home', OCP\Util::linkTo('files', 'index.php'));

$list = new OCP\Template('files_trashbin', 'part.list', '');
$list->assign('files', $files);
$list->assign('baseURL', OCP\Util::linkTo('files_trashbin', 'index.php'). '?dir='.$dir);
$list->assign('downloadURL', OCP\Util::linkTo('files_trashbin', 'download.php') . '?file='.$dir);
$list->assign('disableSharing', true);
$list->assign('dirlisting', $dirlisting);
$tmpl->assign('dirlisting', $dirlisting);
$list->assign('disableDownloadActions', true);
$tmpl->assign('breadcrumb', $breadcrumbNav->fetchPage());
$tmpl->assign('fileList', $list->fetchPage());
$tmpl->assign('files', $files);
$tmpl->assign('dir', \OC\Files\Filesystem::normalizePath($view->getAbsolutePath()));

$tmpl->printPage();
