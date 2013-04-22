<?php
$RUNTIME_NOSETUPFS = true;
// Load other apps for file previews
OC_App::loadApps();

function fileCmp($a, $b) {
	if ($a['type'] == 'dir' and $b['type'] != 'dir') {
		return -1;
	} elseif ($a['type'] != 'dir' and $b['type'] == 'dir') {
		return 1;
	} else {
		return strnatcasecmp($a['name'], $b['name']);
	}
}

if (isset($_GET['t'])) {
	$token = $_GET['t'];
	$linkItem = OCP\Share::getShareByToken($token);
	if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
		// seems to be a valid share
		$type = $linkItem['item_type'];
		$fileSource = $linkItem['file_source'];
		$shareOwner = $linkItem['uid_owner'];
		$fileOwner = null;
		$path = null;
		if (isset($linkItem['parent'])) {
			$parent = $linkItem['parent'];
			while (isset($parent)) {
				$query = \OC_DB::prepare('SELECT `parent`, `uid_owner` FROM `*PREFIX*share` WHERE `id` = ?', 1);
				$item = $query->execute(array($parent))->fetchRow();
				if (isset($item['parent'])) {
					$parent = $item['parent'];
				} else {
					$fileOwner = $item['uid_owner'];
					break;
				}
			}
		} else {
			$fileOwner = $shareOwner;
		}
		if (isset($fileOwner)) {
			OC_Util::setupFS($fileOwner);
			$path = \OC\Files\Filesystem::getPath($linkItem['file_source']);
		}
	}
}
if (isset($path)) {
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
	$basePath = $path;
	if (isset($_GET['path']) && \OC\Files\Filesystem::isReadable($basePath . $_GET['path'])) {
		$getPath = \OC\Files\Filesystem::normalizePath($_GET['path']);
		$path .= $getPath;
	} else {
		$getPath = '';
	}
	$dir = dirname($path);
	$file = basename($path);
	// Download the file
	if (isset($_GET['download'])) {
		if (isset($_GET['files'])) { // download selected files
			$files = urldecode($_GET['files']);
			$files_list = json_decode($files);
			// in case we get only a single file
			if ($files_list === NULL ) {
				$files_list = array($files);
			}
			OC_Files::get($path, $files_list, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
		} else {
			OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
		}
		exit();
	} else {
		OCP\Util::addStyle('files_sharing', 'public');
		OCP\Util::addScript('files_sharing', 'public');
		OCP\Util::addScript('files', 'fileactions');
		$tmpl = new OCP\Template('files_sharing', 'public', 'base');
		$tmpl->assign('uidOwner', $shareOwner);
		$tmpl->assign('displayName', \OCP\User::getDisplayName($shareOwner));
		$tmpl->assign('filename', $file);
		$tmpl->assign('mimetype', \OC\Files\Filesystem::getMimeType($path));
		$tmpl->assign('fileTarget', basename($linkItem['file_target']));
		$urlLinkIdentifiers= (isset($token)?'&t='.$token:'')
							.(isset($_GET['dir'])?'&dir='.$_GET['dir']:'')
							.(isset($_GET['file'])?'&file='.$_GET['file']:'');
		// Show file list
		if (\OC\Files\Filesystem::is_dir($path)) {
			$tmpl->assign('dir', $getPath);

			OCP\Util::addStyle('files', 'files');
			OCP\Util::addScript('files', 'files');
			OCP\Util::addScript('files', 'filelist');
			OCP\Util::addscript('files', 'keyboardshortcuts');
			$files = array();
			$rootLength = strlen($basePath) + 1;
			$totalSize = 0;
			foreach (\OC\Files\Filesystem::getDirectoryContent($path) as $i) {
				$totalSize += $i['size'];
				$i['date'] = OCP\Util::formatDate($i['mtime']);
				if ($i['type'] == 'file') {
					$fileinfo = pathinfo($i['name']);
					$i['basename'] = $fileinfo['filename'];
					if (!empty($fileinfo['extension'])) {
						$i['extension'] = '.' . $fileinfo['extension'];
					} else {
						$i['extension'] = '';
					}
				}
				$i['directory'] = $getPath;
				$i['permissions'] = OCP\PERMISSION_READ;
				$files[] = $i;
			}
			usort($files, "fileCmp");

			// Make breadcrumb
			$breadcrumb = array();
			$pathtohere = '';
			foreach (explode('/', $getPath) as $i) {
				if ($i != '') {
					$pathtohere .= '/' . $i;
					$breadcrumb[] = array('dir' => $pathtohere, 'name' => $i);
				}
			}
			$list = new OCP\Template('files', 'part.list', '');
			$list->assign('files', $files);
			$list->assign('disableSharing', true);
			$list->assign('baseURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&path=');
			$list->assign('downloadURL',
				OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download&path=');
			$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '');
			$breadcrumbNav->assign('breadcrumb', $breadcrumb);
			$breadcrumbNav->assign('baseURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&path=');
			$folder = new OCP\Template('files', 'index', '');
			$folder->assign('fileList', $list->fetchPage());
			$folder->assign('breadcrumb', $breadcrumbNav->fetchPage());
			$folder->assign('dir', $getPath);
			$folder->assign('isCreatable', false);
			$folder->assign('permissions', 0);
			$folder->assign('files', $files);
			$folder->assign('uploadMaxFilesize', 0);
			$folder->assign('uploadMaxHumanFilesize', 0);
			$folder->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
			$folder->assign('usedSpacePercent', 0);
			$tmpl->assign('folder', $folder->fetchPage());
			$allowZip = OCP\Config::getSystemValue('allowZipDownload', true)
						&& $totalSize <= OCP\Config::getSystemValue('maxZipInputSize', OCP\Util::computerFileSize('800 MB'));
			$tmpl->assign('allowZipDownload', intval($allowZip));
			$tmpl->assign('downloadURL',
				OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download&path=' . urlencode($getPath));
		} else {
			$tmpl->assign('dir', $dir);

			// Show file preview if viewer is available
			if ($type == 'file') {
				$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download');
			} else {
				$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files')
										.$urlLinkIdentifiers.'&download&path='.urlencode($getPath));
			}
		}
		$tmpl->printPage();
	}
	exit();
} else {
	OCP\Util::writeLog('share', 'could not resolve linkItem', \OCP\Util::DEBUG);
}
header('HTTP/1.0 404 Not Found');
$tmpl = new OCP\Template('', '404', 'guest');
$tmpl->printPage();
