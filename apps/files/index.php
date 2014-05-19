<?php

/**
 * ownCloud - ajax frontend
 *
 * @author Robin Appelman
 * @copyright 2010 Robin Appelman icewind1991@gmail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Check if we are a user
OCP\User::checkLoggedIn();

// Load the files we need
OCP\Util::addStyle('files', 'files');
OCP\Util::addStyle('files', 'upload');
OCP\Util::addStyle('files', 'mobile');
OCP\Util::addscript('files', 'app');
OCP\Util::addscript('files', 'file-upload');
OCP\Util::addscript('files', 'jquery.iframe-transport');
OCP\Util::addscript('files', 'jquery.fileupload');
OCP\Util::addscript('files', 'jquery-visibility');
OCP\Util::addscript('files', 'filesummary');
OCP\Util::addscript('files', 'breadcrumb');
OCP\Util::addscript('files', 'filelist');

OCP\App::setActiveNavigationEntry('files_index');

$isIE8 = false;
preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
if (count($matches) > 0 && $matches[1] <= 9) {
	$isIE8 = true;
}

// if IE8 and "?dir=path&view=someview" was specified, reformat the URL to use a hash like "#?dir=path&view=someview"
if ($isIE8 && (isset($_GET['dir']) || isset($_GET['view']))) {
	$hash = '#?';
	$dir = isset($_GET['dir']) ? $_GET['dir'] : '/';
	$view = isset($_GET['view']) ? $_GET['view'] : 'files';
	$hash = '#?dir=' . \OCP\Util::encodePath($dir);
	if ($view !== 'files') {
		$hash .= '&view=' . urlencode($view);
	}
	header('Location: ' . OCP\Util::linkTo('files', 'index.php') . $hash);
	exit();
}

$user = OC_User::getUser();

$config = \OC::$server->getConfig();

// mostly for the home storage's free space
$dirInfo = \OC\Files\Filesystem::getFileInfo('/', false);
$storageInfo=OC_Helper::getStorageInfo('/', $dirInfo);
// if the encryption app is disabled, than everything is fine (INIT_SUCCESSFUL status code)
$encryptionInitStatus = 2;
if (OC_App::isEnabled('files_encryption')) {
	$session = new \OCA\Encryption\Session(new \OC\Files\View('/'));
	$encryptionInitStatus = $session->getInitialized();
}

$nav = new OCP\Template('files', 'appnavigation', '');

$navItems = \OCA\Files\App::getNavigationManager()->getAll();
$nav->assign('navigationItems', $navItems);

$contentItems = array();

function renderScript($appName, $scriptName) {
	$content = '';
	$appPath = OC_App::getAppPath($appName);
	$scriptPath = $appPath . '/' . $scriptName;
	if (file_exists($scriptPath)) {
		// TODO: sanitize path / script name ?
		ob_start();
		include $scriptPath;
		$content = ob_get_contents();
		@ob_end_clean();
	}
	return $content;
}

// render the container content for every navigation item
foreach ($navItems as $item) {
	$content = '';
	if (isset($item['script'])) {
		$content = renderScript($item['appname'], $item['script']);
	}
	$contentItem = array();
	$contentItem['id'] = $item['id'];
	$contentItem['content'] = $content;
	$contentItems[] = $contentItem;
}

OCP\Util::addscript('files', 'fileactions');
OCP\Util::addscript('files', 'files');
OCP\Util::addscript('files', 'navigation');
OCP\Util::addscript('files', 'keyboardshortcuts');
$tmpl = new OCP\Template('files', 'index', 'user');
$tmpl->assign('usedSpacePercent', (int)$storageInfo['relative']);
$tmpl->assign('isPublic', false);
$tmpl->assign("encryptedFiles", \OCP\Util::encryptedFiles());
$tmpl->assign("mailNotificationEnabled", $config->getAppValue('core', 'shareapi_allow_mail_notification', 'yes'));
$tmpl->assign("allowShareWithLink", $config->getAppValue('core', 'shareapi_allow_links', 'yes'));
$tmpl->assign("encryptionInitStatus", $encryptionInitStatus);
$tmpl->assign('appNavigation', $nav);
$tmpl->assign('appContents', $contentItems);
$tmpl->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));

$tmpl->printPage();
