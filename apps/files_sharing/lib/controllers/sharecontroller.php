<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing\Controllers;

use OC;
use OC\Files\Filesystem;
use OC_Files;
use OC_Util;
use OCP;
use OCP\Template;
use OCP\Share;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OC\URLGenerator;
use OC\AppConfig;
use OCP\ILogger;
use OCA\Files_Sharing\Helper;
use OCP\User;
use OCP\Util;
use OCA\Files_Sharing\Activity;

/**
 * Class ShareController
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ShareController extends Controller {

	/** @var \OC\User\Session */
	protected $userSession;
	/** @var \OC\AppConfig */
	protected $appConfig;
	/** @var \OCP\IConfig */
	protected $config;
	/** @var \OC\URLGenerator */
	protected $urlGenerator;
	/** @var \OC\User\Manager */
	protected $userManager;
	/** @var \OCP\ILogger */
	protected $logger;
	/** @var OCP\Activity\IManager */
	protected $activityManager;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param OC\User\Session $userSession
	 * @param AppConfig $appConfig
	 * @param OCP\IConfig $config
	 * @param URLGenerator $urlGenerator
	 * @param OCP\IUserManager $userManager
	 * @param ILogger $logger
	 * @param OCP\Activity\IManager $activityManager
	 */
	public function __construct($appName,
								IRequest $request,
								OC\User\Session $userSession,
								AppConfig $appConfig,
								OCP\IConfig $config,
								URLGenerator $urlGenerator,
								OCP\IUserManager $userManager,
								ILogger $logger,
								OCP\Activity\IManager $activityManager) {
		parent::__construct($appName, $request);

		$this->userSession = $userSession;
		$this->appConfig = $appConfig;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->activityManager = $activityManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @return TemplateResponse|RedirectResponse
	 */
	public function showAuthenticate($token) {
		$linkItem = Share::getShareByToken($token, false);

		if(Helper::authenticate($linkItem)) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.showShare', array('token' => $token)));
		}

		return new TemplateResponse($this->appName, 'authenticate', array(), 'guest');
	}

	/**
	 * @PublicPage
	 * @UseSession
	 *
	 * Authenticates against password-protected shares
	 * @param $token
	 * @param string $password
	 * @return RedirectResponse|TemplateResponse
	 */
	public function authenticate($token, $password = '') {
		$linkItem = Share::getShareByToken($token, false);
		if($linkItem === false) {
			return new NotFoundResponse();
		}

		$authenticate = Helper::authenticate($linkItem, $password);

		if($authenticate === true) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.showShare', array('token' => $token)));
		}

		return new TemplateResponse($this->appName, 'authenticate', array('wrongpw' => true), 'guest');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @param string $path
	 * @return TemplateResponse|RedirectResponse
	 */
	public function showShare($token, $path = '') {
		\OC_User::setIncognitoMode(true);

		// Check whether share exists
		$linkItem = Share::getShareByToken($token, false);
		if($linkItem === false) {
			return new NotFoundResponse();
		}

		$shareOwner = $linkItem['uid_owner'];
		$originalSharePath = $this->getPath($token);

		// Share is password protected - check whether the user is permitted to access the share
		if (isset($linkItem['share_with']) && !Helper::authenticate($linkItem)) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.authenticate',
				array('token' => $token)));
		}

		if (Filesystem::isReadable($originalSharePath . $path)) {
			$getPath = Filesystem::normalizePath($path);
			$originalSharePath .= $path;
		} else {
			throw new OCP\Files\NotFoundException();
		}

		$file = basename($originalSharePath);

		$shareTmpl = [];
		$shareTmpl['displayName'] = User::getDisplayName($shareOwner);
		$shareTmpl['filename'] = $file;
		$shareTmpl['directory_path'] = $linkItem['file_target'];
		$shareTmpl['mimetype'] = Filesystem::getMimeType($originalSharePath);
		$shareTmpl['previewSupported'] = \OC::$server->getPreviewManager()->isMimeSupported($shareTmpl['mimetype']);
		$shareTmpl['dirToken'] = $linkItem['token'];
		$shareTmpl['sharingToken'] = $token;
		$shareTmpl['server2serversharing'] = Helper::isOutgoingServer2serverShareEnabled();
		$shareTmpl['protected'] = isset($linkItem['share_with']) ? 'true' : 'false';
		$shareTmpl['dir'] = '';
		$nonHumanFileSize = \OC\Files\Filesystem::filesize($originalSharePath);
		$shareTmpl['nonHumanFileSize'] = $nonHumanFileSize;
		$shareTmpl['fileSize'] = \OCP\Util::humanFileSize($nonHumanFileSize);

		// Show file list
		if (Filesystem::is_dir($originalSharePath)) {
			$shareTmpl['dir'] = $getPath;
			$maxUploadFilesize = Util::maxUploadFilesize($originalSharePath);
			$freeSpace = Util::freeSpace($originalSharePath);
			$uploadLimit = Util::uploadLimit();
			$folder = new Template('files', 'list', '');
			$folder->assign('dir', $getPath);
			$folder->assign('dirToken', $linkItem['token']);
			$folder->assign('permissions', \OCP\Constants::PERMISSION_READ);
			$folder->assign('isPublic', true);
			$folder->assign('publicUploadEnabled', 'no');
			$folder->assign('uploadMaxFilesize', $maxUploadFilesize);
			$folder->assign('uploadMaxHumanFilesize', OCP\Util::humanFileSize($maxUploadFilesize));
			$folder->assign('freeSpace', $freeSpace);
			$folder->assign('uploadLimit', $uploadLimit); // PHP upload limit
			$folder->assign('usedSpacePercent', 0);
			$folder->assign('trash', false);
			$shareTmpl['folder'] = $folder->fetchPage();
		}

		$shareTmpl['downloadURL'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.downloadShare', array('token' => $token));
		$shareTmpl['maxSizeAnimateGif'] = $this->config->getSystemValue('max_filesize_animated_gifs_public_sharing', 10);
		$shareTmpl['previewEnabled'] = $this->config->getSystemValue('enable_previews', true);

		$csp = new OCP\AppFramework\Http\ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('\'self\'');
		$response = new TemplateResponse($this->appName, 'public', $shareTmpl, 'base');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @param string $files
	 * @param string $path
	 * @return void|RedirectResponse
	 */
	public function downloadShare($token, $files = null, $path = '') {
		\OC_User::setIncognitoMode(true);

		$linkItem = OCP\Share::getShareByToken($token, false);

		// Share is password protected - check whether the user is permitted to access the share
		if (isset($linkItem['share_with'])) {
			if(!Helper::authenticate($linkItem)) {
				return new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.authenticate',
					array('token' => $token)));
			}
		}

		$files_list = null;
		if (!is_null($files)) { // download selected files
			$files_list = json_decode($files);
			// in case we get only a single file
			if ($files_list === null) {
				$files_list = array($files);
			}
		}

		$originalSharePath = self::getPath($token);

		// Create the activities
		if (isset($originalSharePath) && Filesystem::isReadable($originalSharePath . $path)) {
			$originalSharePath = Filesystem::normalizePath($originalSharePath . $path);
			$isDir = \OC\Files\Filesystem::is_dir($originalSharePath);

			$activities = [];
			if (!$isDir) {
				// Single file public share
				$activities[$originalSharePath] = Activity::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED;
			} else if (!empty($files_list)) {
				// Only some files are downloaded
				foreach ($files_list as $file) {
					$filePath = Filesystem::normalizePath($originalSharePath . '/' . $file);
					$isDir = \OC\Files\Filesystem::is_dir($filePath);
					$activities[$filePath] = ($isDir) ? Activity::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED : Activity::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED;
				}
			} else {
				// The folder is downloaded
				$activities[$originalSharePath] = Activity::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED;
			}

			foreach ($activities as $filePath => $subject) {
				$this->activityManager->publishActivity(
					'files_sharing', $subject, array($filePath), '', array(),
					$filePath, '', $linkItem['uid_owner'], Activity::TYPE_PUBLIC_LINKS, Activity::PRIORITY_MEDIUM
				);
			}
		}

		// download selected files
		if (!is_null($files)) {
			// FIXME: The exit is required here because otherwise the AppFramework is trying to add headers as well
			// after dispatching the request which results in a "Cannot modify header information" notice.
			OC_Files::get($originalSharePath, $files_list, $_SERVER['REQUEST_METHOD'] == 'HEAD');
			exit();
		} else {
			// FIXME: The exit is required here because otherwise the AppFramework is trying to add headers as well
			// after dispatching the request which results in a "Cannot modify header information" notice.
			OC_Files::get(dirname($originalSharePath), basename($originalSharePath), $_SERVER['REQUEST_METHOD'] == 'HEAD');
			exit();
		}
	}

	/**
	 * @param string $token
	 * @return string Resolved file path of the token
	 * @throws \Exception In case share could not get properly resolved
	 */
	private function getPath($token) {
		$linkItem = Share::getShareByToken($token, false);
		if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
			// seems to be a valid share
			$rootLinkItem = Share::resolveReShare($linkItem);
			if (isset($rootLinkItem['uid_owner'])) {
				if(!$this->userManager->userExists($rootLinkItem['uid_owner'])) {
					throw new \Exception('Owner of the share does not exist anymore');
				}
				OC_Util::tearDownFS();
				OC_Util::setupFS($rootLinkItem['uid_owner']);
				$path = Filesystem::getPath($linkItem['file_source']);

				if(!empty($path) && Filesystem::isReadable($path)) {
					return $path;
				}
			}
		}

		throw new \Exception('No file found belonging to file.');
	}
}
