<?php
// Load other apps for file previews
OC_App::loadApps();

// Compatibility with shared-by-link items from ownCloud 4.0
// requires old Sharing table !
// support will be removed in OC 5.0,a
if (isset($_GET['token'])) {
	unset($_GET['file']);
	$qry = \OC_DB::prepare('SELECT `source` FROM `*PREFIX*sharing` WHERE `target` = ?', 1);
	$filepath = $qry->execute(array($_GET['token']))->fetchOne();
	if (isset($filepath)) {
		$rootView = new \OC\Files\View('');
		$info = $rootView->getFileInfo($filepath, '');
		if (strtolower($info['mimetype']) == 'httpd/unix-directory') {
			$_GET['dir'] = $filepath;
		} else {
			$_GET['file'] = $filepath;
		}
		\OCP\Util::writeLog('files_sharing', 'You have files that are shared by link originating from ownCloud 4.0.'
				.' Redistribute the new links, because backwards compatibility will be removed in ownCloud 5.',
				\OCP\Util::WARN);
	}
}

function getID($path) {
	// use the share table from the db to find the item source if the file was reshared because shared files
	//are not stored in the file cache.
	if (substr(\OC\Files\Filesystem::getMountPoint($path), -7, 6) == "Shared") {
		$path_parts = explode('/', $path, 5);
		$user = $path_parts[1];
		$intPath = '/'.$path_parts[4];
		$query = \OC_DB::prepare('SELECT `item_source`'
								.' FROM `*PREFIX*share`'
								.' WHERE `uid_owner` = ?'
								.' AND `file_target` = ? ');
		$result = $query->execute(array($user, $intPath));
		$row = $result->fetchRow();
		$fileSource = $row['item_source'];
	} else {
		$rootView = new \OC\Files\View('');
		$meta = $rootView->getFileInfo($path);
		$fileSource = $meta['fileid'];
	}

	return $fileSource;
}

// Enf of backward compatibility

/**
 * lookup file path and owner by fetching it from the fscache
 * needed because OC_FileCache::getPath($id, $user) already requires the user
 * @param int $id
 * @return array
 */
function getPathAndUser($id) {
	$query = \OC_DB::prepare('SELECT `user`, `path` FROM `*PREFIX*fscache` WHERE `id` = ?');
	$result = $query->execute(array($id));
	$row = $result->fetchRow();
	return $row;
}


if (isset($_GET['t'])) {
	$token = $_GET['t'];
	$linkItem = OCP\Share::getShareByToken($token);
	if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
		// seems to be a valid share
		$type = $linkItem['item_type'];
		$fileSource = $linkItem['file_source'];
		$shareOwner = $linkItem['uid_owner'];

		if (OCP\User::userExists($shareOwner) && $fileSource != -1 ) {

			$pathAndUser = getPathAndUser($linkItem['file_source']);
			$fileOwner = $pathAndUser['user'];

			//if this is a reshare check the file owner also exists
			if ($shareOwner != $fileOwner && ! OCP\User::userExists($fileOwner)) {
					OCP\Util::writeLog('share', 'original file owner '.$fileOwner
											   .' does not exist for share '.$linkItem['id'], \OCP\Util::ERROR);
					header('HTTP/1.0 404 Not Found');
					$tmpl = new OCP\Template('', '404', 'guest');
					$tmpl->printPage();
					exit();
			}

			//mount filesystem of file owner
			OC_Util::setupFS($fileOwner);
		}
	}
} else {
	if (isset($_GET['file']) || isset($_GET['dir'])) {
		OCP\Util::writeLog('share', 'Missing token, trying fallback file/dir links', \OCP\Util::DEBUG);
		if (isset($_GET['dir'])) {
			$type = 'folder';
			$path = $_GET['dir'];
			if (strlen($path) > 1 and substr($path, -1, 1) === '/') {
				$path = substr($path, 0, -1);
			}
			$baseDir = $path;
			$dir = $baseDir;
		} else {
			$type = 'file';
			$path = $_GET['file'];
			if (strlen($path) > 1 and substr($path, -1, 1) === '/') {
				$path = substr($path, 0, -1);
			}
		}
		$shareOwner = substr($path, 1, strpos($path, '/', 1) - 1);

		if (OCP\User::userExists($shareOwner)) {
			OC_Util::setupFS($shareOwner);
			$fileSource = getId($path);
			if ($fileSource != -1) {
				$linkItem = OCP\Share::getItemSharedWithByLink($type, $fileSource, $shareOwner);
				$pathAndUser['path'] = $path;
				$path_parts = explode('/', $path, 5);
				$pathAndUser['user'] = $path_parts[1];
				$fileOwner = $path_parts[1];
			}
		}
	}
}

if ($linkItem) {
	if (!isset($linkItem['item_type'])) {
		OCP\Util::writeLog('share', 'No item type set for share id: ' . $linkItem['id'], \OCP\Util::ERROR);
		header('HTTP/1.0 404 Not Found');
		$tmpl = new OCP\Template('', '404', 'guest');
		$tmpl->printPage();
		exit();
	}
	if (isset($linkItem['share_with'])) {
		// Authenticate share_with
		$url = OCP\Util::linkToPublic('files') . '&t=' . $token;
		if (isset($_GET['file'])) {
			$url .= '&file=' . urlencode($_GET['file']);
		} else {
			if (isset($_GET['dir'])) {
				$url .= '&dir=' . urlencode($_GET['dir']);
			}
		}
		if (isset($_POST['password'])) {
			$password = $_POST['password'];
			if ($linkItem['share_type'] == OCP\Share::SHARE_TYPE_LINK) {
				// Check Password
				$forcePortable = (CRYPT_BLOWFISH != 1);
				$hasher = new PasswordHash(8, $forcePortable);
				if (!($hasher->CheckPassword($password.OC_Config::getValue('passwordsalt', ''),
											 $linkItem['share_with']))) {
					$tmpl = new OCP\Template('files_sharing', 'authenticate', 'guest');
					$tmpl->assign('URL', $url);
					$tmpl->assign('error', true);
					$tmpl->printPage();
					exit();
				} else {
					// Save item id in session for future requests
					$_SESSION['public_link_authenticated'] = $linkItem['id'];
				}
			} else {
				OCP\Util::writeLog('share', 'Unknown share type '.$linkItem['share_type']
										   .' for share id '.$linkItem['id'], \OCP\Util::ERROR);
				header('HTTP/1.0 404 Not Found');
				$tmpl = new OCP\Template('', '404', 'guest');
				$tmpl->printPage();
				exit();
			}

		} else {
			// Check if item id is set in session
			if (!isset($_SESSION['public_link_authenticated'])
				|| $_SESSION['public_link_authenticated'] !== $linkItem['id']
			) {
				// Prompt for password
				$tmpl = new OCP\Template('files_sharing', 'authenticate', 'guest');
				$tmpl->assign('URL', $url);
				$tmpl->printPage();
				exit();
			}
		}
	}
	$basePath = substr($pathAndUser['path'], strlen('/' . $fileOwner . '/files'));
	$path = $basePath;
	if (isset($_GET['path'])) {
		$path .= $_GET['path'];
	}
	if (!$path || !\OC\Files\Filesystem::isValidPath($path) || !\OC\Files\Filesystem::file_exists($path)) {
		OCP\Util::writeLog('share', 'Invalid path ' . $path . ' for share id ' . $linkItem['id'], \OCP\Util::ERROR);
		header('HTTP/1.0 404 Not Found');
		$tmpl = new OCP\Template('', '404', 'guest');
		$tmpl->printPage();
		exit();
	}
	$dir = dirname($path);
	$file = basename($path);
	// Download the file
	if (isset($_GET['download'])) {
		if (isset($_GET['path']) && $_GET['path'] !== '') {
			if (isset($_GET['files'])) { // download selected files
				OC_Files::get($path, $_GET['files'], $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
			} else {
				if (isset($_GET['path']) && $_GET['path'] != '') { // download a file from a shared directory
					OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
				} else { // download the whole shared directory
					OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
				}
			}
		} else { // download a single shared file
			OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
		}

	} else {
		OCP\Util::addStyle('files_sharing', 'public');
		OCP\Util::addScript('files_sharing', 'public');
		OCP\Util::addScript('files', 'fileactions');
		$tmpl = new OCP\Template('files_sharing', 'public', 'base');
		$tmpl->assign('uidOwner', $shareOwner);
		$tmpl->assign('displayName', \OCP\User::getDisplayName($shareOwner));
		$tmpl->assign('dir', $dir);
		$tmpl->assign('filename', $file);
		$tmpl->assign('mimetype', \OC\Files\Filesystem::getMimeType($path));
		if (isset($_GET['path'])) {
			$getPath = $_GET['path'];
		} else {
			$getPath = '';
		}
		//
		$urlLinkIdentifiers= (isset($token)?'&t='.$token:'')
							.(isset($_GET['dir'])?'&dir='.$_GET['dir']:'')
							.(isset($_GET['file'])?'&file='.$_GET['file']:'');
		// Show file list
		if (\OC\Files\Filesystem::is_dir($path)) {
			OCP\Util::addStyle('files', 'files');
			OCP\Util::addScript('files', 'files');
			OCP\Util::addScript('files', 'filelist');
			$files = array();
			$rootLength = strlen($basePath) + 1;
			foreach (OC_Files::getDirectoryContent($path) as $i) {
				$i['date'] = OCP\Util::formatDate($i['mtime']);
				if ($i['type'] == 'file') {
					$fileinfo = pathinfo($i['name']);
					$i['basename'] = $fileinfo['filename'];
					$i['extension'] = isset($fileinfo['extension']) ? ('.' . $fileinfo['extension']) : '';
				}
				$i['directory'] = '/' . substr($i['directory'], $rootLength);
				if ($i['directory'] == '/') {
					$i['directory'] = '';
				}
				$i['permissions'] = OCP\PERMISSION_READ;
				$files[] = $i;
			}
			// Make breadcrumb
			$breadcrumb = array();
			$pathtohere = '';

			//add base breadcrumb
			$breadcrumb[] = array('dir' => '/', 'name' => basename($basePath));

			//add subdir breadcrumbs
			foreach (explode('/', urldecode($getPath)) as $i) {
				if ($i != '') {
					$pathtohere .= '/' . $i;
					$breadcrumb[] = array('dir' => $pathtohere, 'name' => $i);
					$path = $linkItem['path'];
					if (isset($_GET['path'])) {
						$path .= $_GET['path'];
						$dir .= $_GET['path'];
						if (!\OC\Files\Filesystem::file_exists($path)) {
							header('HTTP/1.0 404 Not Found');
							$tmpl = new OCP\Template('', '404', 'guest');
							$tmpl->printPage();
							exit();
						}
					}

					$list = new OCP\Template('files', 'part.list', '');
					$list->assign('files', $files, false);
					$list->assign('publicListView', true);
					$list->assign('baseURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&path=', false);
					$list->assign('downloadURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download&path=', false);
					$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '');
					$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
					$breadcrumbNav->assign('baseURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&path=', false);
					$folder = new OCP\Template('files', 'index', '');
					$folder->assign('fileList', $list->fetchPage(), false);
					$folder->assign('breadcrumb', $breadcrumbNav->fetchPage(), false);
					$folder->assign('isCreatable', false);
					$folder->assign('permissions', 0);
					$folder->assign('files', $files);
					$folder->assign('uploadMaxFilesize', 0);
					$folder->assign('uploadMaxHumanFilesize', 0);
					$folder->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
					$tmpl->assign('folder', $folder->fetchPage(), false);
					$tmpl->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
					$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download&path=' . urlencode($getPath));
				} else {
					// Show file preview if viewer is available
					if ($type == 'file') {
						$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download');
					} else {
						OCP\Util::addStyle('files_sharing', 'public');
						OCP\Util::addScript('files_sharing', 'public');
						OCP\Util::addScript('files', 'fileactions');
						$tmpl = new OCP\Template('files_sharing', 'public', 'base');
						$tmpl->assign('owner', $uidOwner);
						// Show file list
						if (\OC\Files\Filesystem::is_dir($path)) {
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
									$i['extension'] = isset($fileinfo['extension']) ? ('.' . $fileinfo['extension']) : '';
								}
								$i['directory'] = '/' . substr('/' . $uidOwner . '/files' . $i['directory'], $rootLength);
								if ($i['directory'] == '/') {
									$i['directory'] = '';
								}
								$i['permissions'] = OCP\PERMISSION_READ;
								$files[] = $i;
							}
							// Make breadcrumb
							$breadcrumb = array();
							$pathtohere = '';
							$count = 1;
							foreach (explode('/', $dir) as $i) {
								if ($i != '') {
									if ($i != $baseDir) {
										$pathtohere .= '/' . $i;
									}
									if (strlen($pathtohere) < strlen($_GET['dir'])) {
										continue;
									}
									$breadcrumb[] = array('dir' => str_replace($_GET['dir'], "", $pathtohere, $count), 'name' => $i);
								}
							}
							$list = new OCP\Template('files', 'part.list', '');
							$list->assign('files', $files, false);
							$list->assign('publicListView', true);
							$list->assign('baseURL', OCP\Util::linkToPublic('files') . '&dir=' . urlencode($_GET['dir']) . '&path=', false);
							$list->assign('downloadURL', OCP\Util::linkToPublic('files') . '&download&dir=' . urlencode($_GET['dir']) . '&path=', false);
							$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '');
							$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
							$breadcrumbNav->assign('baseURL', OCP\Util::linkToPublic('files') . '&dir=' . urlencode($_GET['dir']) . '&path=', false);
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
							$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files') . '&download&dir=' . urlencode($_GET['dir']) . '&path=' . urlencode($getPath), false);
						} else {
							// Show file preview if viewer is available
							$tmpl->assign('uidOwner', $uidOwner);
							$tmpl->assign('dir', dirname($path));
							$tmpl->assign('filename', basename($path));
							$tmpl->assign('mimetype', \OC\Files\Filesystem::getMimeType($path));
							if ($type == 'file') {
								$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files') . '&file=' . urlencode($_GET['file']) . '&download', false);
							} else {
								if (isset($_GET['path'])) {
									$getPath = $_GET['path'];
								} else {
									$getPath = '';
								}
								$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files') . '&download&dir=' . urlencode($_GET['dir']) . '&path=' . urlencode($getPath), false);
							}
						}
						$tmpl->printPage();
					}
				}
				$tmpl->printPage();
			}

			$list = new OCP\Template('files', 'part.list', '');
			$list->assign('files', $files, false);
			$list->assign('disableSharing', true);
			$list->assign('baseURL', OCP\Util::linkToPublic('files').$urlLinkIdentifiers.'&path=', false);
			$list->assign('downloadURL', OCP\Util::linkToPublic('files').$urlLinkIdentifiers.'&download&path=', false);
			$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '' );
			$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
			$breadcrumbNav->assign('baseURL', OCP\Util::linkToPublic('files').$urlLinkIdentifiers.'&path=', false);
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
			$tmpl->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
			$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files')
										.$urlLinkIdentifiers.'&download&path='.urlencode($getPath));
		} else {
			OCP\Util::writeLog('share', 'could not resolve linkItem', \OCP\Util::DEBUG);
		}
	}
}
header('HTTP/1.0 404 Not Found');
$tmpl = new OCP\Template('', '404', 'guest');
$tmpl->printPage();

