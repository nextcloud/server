<?php
// Load other apps for file previews
OC_App::loadApps();
if (isset($_GET['file']) || isset($_GET['dir'])) {
	if (isset($_GET['dir'])) {
		$type = 'folder';
		$path = $_GET['dir'];
		$baseDir = basename($path);
		$dir = $baseDir;
	} else {
		$type = 'file';
		$path = $_GET['file'];
	}
	$uidOwner = substr($path, 1, strpos($path, '/', 1) - 1);
	if (OCP\User::userExists($uidOwner)) {
		OC_Util::setupFS($uidOwner);
		$fileSource = OC_Filecache::getId($path, '');
		if ($fileSource != -1 && ($linkItem = OCP\Share::getItemSharedWithByLink($type, $fileSource, $uidOwner))) {
			if (isset($linkItem['share_with'])) {
				// Check password
				if (isset($_POST['password'])) {
					$password = $_POST['password'];
					$storedHash = $linkItem['share_with'];
					$forcePortable = (CRYPT_BLOWFISH != 1);
					$hasher = new PasswordHash(8, $forcePortable);
					if (!($hasher->CheckPassword($password.OC_Config::getValue('passwordsalt', ''), $storedHash))) {
						$tmpl = new OCP\Template('files_sharing', 'authenticate', 'guest');
						$tmpl->assign('URL', OCP\Util::linkToPublic('files').'&file='.$_GET['file']);
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
					$tmpl->assign('URL', OCP\Util::linkToPublic('files').'&file='.$_GET['file']);
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
				$mimetype = OC_Filesystem::getMimeType($path);
				header('Content-Transfer-Encoding: binary');
				header('Content-Disposition: attachment; filename="'.basename($path).'"');
				header('Content-Type: '.$mimetype);
				header('Content-Length: '.OC_Filesystem::filesize($path));
				OCP\Response::disableCaching();
				@ob_clean();
				OC_Filesystem::readfile($path);
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
						$i['directory'] = substr($i['directory'], $rootLength);
						if ($i['directory'] == '/') {
							$i['directory'] = '';
						}
						$i['permissions'] = OCP\Share::PERMISSION_READ;
						$files[] = $i;
					}
					// Make breadcrumb
					$breadcrumb = array();
					$pathtohere = '';
					foreach (explode('/', $dir) as $i) {
						if ($i != '') {
							if ($i != $baseDir) {
								$pathtohere .= '/'.$i;
							}
							$breadcrumb[] = array('dir' => $pathtohere, 'name' => $i);
						}
					}
					$list = new OCP\Template('files', 'part.list', '');
					$list->assign('files', $files, false);
					$list->assign('baseURL', OCP\Util::linkToPublic('files').'&dir='.$_GET['dir'].'&path=', false);
					$list->assign('downloadURL', OCP\Util::linkToPublic('files').'&download&dir='.$_GET['dir'].'&path=', false);
					$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '' );
					$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
					$breadcrumbNav->assign('baseURL', OCP\Util::linkToPublic('files').'&dir='.$_GET['dir'].'&path=', false);
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
					$tmpl->assign('mimetype', OC_Filesystem::getMimeType($path));
					$tmpl->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
					if (isset($_GET['path'])) {
						$getPath = $_GET['path'];
					} else {
						$getPath = '';
					}
					$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files').'&download&dir='.$_GET['dir'].'&path='.$getPath);
				} else {
					// Show file preview if viewer is available
					$tmpl->assign('uidOwner', $uidOwner);
					$tmpl->assign('dir', dirname($path));
					$tmpl->assign('filename', basename($path));
					$tmpl->assign('mimetype', OC_Filesystem::getMimeType($path));
					if ($type == 'file') {
						$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files').'&file='.$_GET['file'].'&download');
					} else {
						if (isset($_GET['path'])) {
							$getPath = $_GET['path'];
						} else {
							$getPath = '';
						}
						$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files').'&download&dir='.$_GET['dir'].'&path='.$getPath);
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
