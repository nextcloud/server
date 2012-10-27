<?php
// Load other apps for file previews
OC_App::loadApps();

// Compatibility with shared-by-link items from ownCloud 4.0
// requires old Sharing table !
// support will be removed in OC 5.0,a
if (isset($_GET['token'])) {
	unset($_GET['file']);
	$qry = \OC_DB::prepare('SELECT `source` FROM `*PREFIX*sharing` WHERE `target` = ? LIMIT 1');
	$filepath = $qry->execute(array($_GET['token']))->fetchOne();
	if(isset($filepath)) {
		$info = OC_FileCache_Cached::get($filepath, '');
		if(strtolower($info['mimetype']) == 'httpd/unix-directory') {
			$_GET['dir'] = $filepath;
		} else {
			$_GET['file'] = $filepath;
		}
		\OCP\Util::writeLog('files_sharing', 'You have files that are shared by link originating from ownCloud 4.0. Redistribute the new links, because backwards compatibility will be removed in ownCloud 5.', \OCP\Util::WARN);
	}
}
// Enf of backward compatibility

function getID($path) {
	// use the share table from the db to find the item source if the file was reshared because shared files 
	//are not stored in the file cache.
	if (substr(OC_Filesystem::getMountPoint($path), -7, 6) == "Shared") {
		$path_parts = explode('/', $path, 5);
		$user = $path_parts[1];
		$intPath = '/'.$path_parts[4];
		$query = \OC_DB::prepare('SELECT item_source FROM *PREFIX*share WHERE uid_owner = ? AND file_target = ? ');
		$result = $query->execute(array($user, $intPath));
		$row = $result->fetchRow();
		$fileSource = $row['item_source'];
	} else {
		$fileSource = OC_Filecache::getId($path, '');
	}

	return $fileSource;
}

if (isset($_GET['file']) || isset($_GET['dir'])) {
	if (isset($_GET['dir'])) {
		$type = 'folder';
		$path = $_GET['dir'];
		if(strlen($path)>1 and substr($path, -1, 1)==='/') {
			$path=substr($path, 0, -1);
		}
		$baseDir = $path;
		$dir = $baseDir;
	} else {
		$type = 'file';
		$path = $_GET['file'];
		if(strlen($path)>1 and substr($path, -1, 1)==='/') {
			$path=substr($path, 0, -1);
		}
	}
	$uidOwner = substr($path, 1, strpos($path, '/', 1) - 1);
	if (OCP\User::userExists($uidOwner)) {
		OC_Util::setupFS($uidOwner);
		$fileSource = getId($path);
		if ($fileSource != -1 && ($linkItem = OCP\Share::getItemSharedWithByLink($type, $fileSource, $uidOwner))) {
			// TODO Fix in the getItems
			if (!isset($linkItem['item_type']) || $linkItem['item_type'] != $type) {
				header('HTTP/1.0 404 Not Found');
				$tmpl = new OCP\Template('', '404', 'guest');
				$tmpl->printPage();
				exit();
			}
			if (isset($linkItem['share_with'])) {
				// Check password
				if (isset($_GET['file'])) {
					$url = OCP\Util::linkToPublic('files').'&file='.$_GET['file'];
				} else {
					$url = OCP\Util::linkToPublic('files').'&dir='.$_GET['dir'];
				}
				if (isset($_POST['password'])) {
					$password = $_POST['password'];
					$storedHash = $linkItem['share_with'];
					$forcePortable = (CRYPT_BLOWFISH != 1);
					$hasher = new PasswordHash(8, $forcePortable);
					if (!($hasher->CheckPassword($password.OC_Config::getValue('passwordsalt', ''), $storedHash))) {
						$tmpl = new OCP\Template('files_sharing', 'authenticate', 'guest');
						$tmpl->assign('URL', $url);
						$tmpl->assign('error', true);
						$tmpl->printPage();
						exit();
					} else {
						// Save item id in session for future requests
						$_SESSION['public_link_authenticated'] = $linkItem['id'];
					}
				// Check if item id is set in session
				} else if (!isset($_SESSION['public_link_authenticated']) || $_SESSION['public_link_authenticated'] !== $linkItem['id']) {
					// Prompt for password
					$tmpl = new OCP\Template('files_sharing', 'authenticate', 'guest');
					$tmpl->assign('URL', $url);
					$tmpl->printPage();
					exit();
				}
			}
			$path = $linkItem['path'];
			if (isset($_GET['path'])) {
				$path .= $_GET['path'];
				$dir .= $_GET['path'];
				if (!OC_Filesystem::file_exists($path)) {
					header('HTTP/1.0 404 Not Found');
					$tmpl = new OCP\Template('', '404', 'guest');
					$tmpl->printPage();
					exit();
				}
			}
			// Download the file
			if (isset($_GET['download'])) {
				if (isset($_GET['dir'])) {
					if ( isset($_GET['files']) ) { // download selected files
						OC_Files::get($path, $_GET['files'], $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
					} else 	if (isset($_GET['path']) &&  $_GET['path'] != '' ) { // download a file from a shared directory
						OC_Files::get('', $path, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
					} else { // download the whole shared directory
						OC_Files::get($path, '', $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
					}
				} else { // download a single shared file
					OC_Files::get("", $path, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
				}

			} else {
				OCP\Util::addStyle('files_sharing', 'public');
				OCP\Util::addScript('files_sharing', 'public');
				OCP\Util::addScript('files', 'fileactions');
				$tmpl = new OCP\Template('files_sharing', 'public', 'base');
				$tmpl->assign('owner', $uidOwner);
				// Show file list
				if (OC_Filesystem::is_dir($path)) {
					OCP\Util::addStyle('files', 'files');
					OCP\Util::addScript('files', 'files');
					OCP\Util::addScript('files', 'filelist');
					$files = array();
					$rootLength = strlen($baseDir) + 1;
					foreach (OC_Files::getDirectoryContent($path) as $i) {
						$i['date'] = OCP\Util::formatDate($i['mtime']);
						if ($i['type'] == 'file') {
							$fileinfo = pathinfo($i['name']);
							$i['basename'] = $fileinfo['filename'];
							$i['extension'] = isset($fileinfo['extension']) ? ('.'.$fileinfo['extension']) : '';
						}
						$i['directory'] = '/'.substr('/'.$uidOwner.'/files'.$i['directory'], $rootLength);
						if ($i['directory'] == '/') {
							$i['directory'] = '';
						}
						$i['permissions'] = OCP\Share::PERMISSION_READ;
						$files[] = $i;
					}
					// Make breadcrumb
					$breadcrumb = array();
					$pathtohere = '';
					$count = 1;
					foreach (explode('/', $dir) as $i) {
						if ($i != '') {
							if ($i != $baseDir) {
								$pathtohere .= '/'.$i;
							}
							if ( strlen($pathtohere) <  strlen($_GET['dir'])) {
								continue;
							}
							$breadcrumb[] = array('dir' => str_replace($_GET['dir'], "", $pathtohere, $count), 'name' => $i);
						}
					}
					$list = new OCP\Template('files', 'part.list', '');
					$list->assign('files', $files, false);
					$list->assign('publicListView', true);
					$list->assign('baseURL', OCP\Util::linkToPublic('files').'&dir='.urlencode($_GET['dir']).'&path=', false);
					$list->assign('downloadURL', OCP\Util::linkToPublic('files').'&download&dir='.urlencode($_GET['dir']).'&path=', false);
					$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '' );
					$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
					$breadcrumbNav->assign('baseURL', OCP\Util::linkToPublic('files').'&dir='.urlencode($_GET['dir']).'&path=', false);
					$folder = new OCP\Template('files', 'index', '');
					$folder->assign('fileList', $list->fetchPage(), false);
					$folder->assign('breadcrumb', $breadcrumbNav->fetchPage(), false);
					$folder->assign('dir', basename($dir));
					$folder->assign('isCreatable', false);
					$folder->assign('permissions', 0);
					$folder->assign('files', $files);
					$folder->assign('uploadMaxFilesize', 0);
					$folder->assign('uploadMaxHumanFilesize', 0);
					$folder->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
					$tmpl->assign('folder', $folder->fetchPage(), false);
					$tmpl->assign('uidOwner', $uidOwner);
					$tmpl->assign('dir', basename($dir));
					$tmpl->assign('filename', basename($path));
					$tmpl->assign('mimetype', \OC\Files\Filesystem::getMimeType($path));
					$tmpl->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
					if (isset($_GET['path'])) {
						$getPath = $_GET['path'];
					} else {
						$getPath = '';
					}
					$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files').'&download&dir='.urlencode($_GET['dir']).'&path='.urlencode($getPath), false);
				} else {
					// Show file preview if viewer is available
					$tmpl->assign('uidOwner', $uidOwner);
					$tmpl->assign('dir', dirname($path));
					$tmpl->assign('filename', basename($path));
					$tmpl->assign('mimetype', \OC\Files\Filesystem::getMimeType($path));
					if ($type == 'file') {
						$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files').'&file='.urlencode($_GET['file']).'&download', false);
					} else {
						if (isset($_GET['path'])) {
							$getPath = $_GET['path'];
						} else {
							$getPath = '';
						}
						$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files').'&download&dir='.urlencode($_GET['dir']).'&path='.urlencode($getPath), false);
					}
				}
				$tmpl->printPage();
			}
			exit();
		}
	}
}
header('HTTP/1.0 404 Not Found');
$tmpl = new OCP\Template('', '404', 'guest');
$tmpl->printPage();
