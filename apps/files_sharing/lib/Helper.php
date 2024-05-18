<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Files_Sharing\AppInfo\Application;

class Helper {
	public static function registerHooks() {
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', '\OCA\Files_Sharing\Updater', 'renameHook');
		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', '\OCA\Files_Sharing\Hooks', 'unshareChildren');

		\OCP\Util::connectHook('OC_User', 'post_deleteUser', '\OCA\Files_Sharing\Hooks', 'deleteUser');
	}

	/**
	 * check if file name already exists and generate unique target
	 *
	 * @param string $path
	 * @param array $excludeList
	 * @param View $view
	 * @return string $path
	 */
	public static function generateUniqueTarget($path, $excludeList, $view) {
		$pathinfo = pathinfo($path);
		$ext = isset($pathinfo['extension']) ? '.'.$pathinfo['extension'] : '';
		$name = $pathinfo['filename'];
		$dir = $pathinfo['dirname'];
		$i = 2;
		while ($view->file_exists($path) || in_array($path, $excludeList)) {
			$path = Filesystem::normalizePath($dir . '/' . $name . ' ('.$i.')' . $ext);
			$i++;
		}

		return $path;
	}

	/**
	 * get default share folder
	 *
	 * @param \OC\Files\View|null $view
	 * @param string|null $userId
	 * @return string
	 */
	public static function getShareFolder(?View $view = null, ?string $userId = null): string {
		if ($view === null) {
			$view = Filesystem::getView();
		}

		$config = \OC::$server->getConfig();
		$systemDefault = $config->getSystemValue('share_folder', '/');
		$allowCustomShareFolder = $config->getSystemValueBool('sharing.allow_custom_share_folder', true);

		// Init custom shareFolder
		$shareFolder = $systemDefault;
		if ($userId !== null && $allowCustomShareFolder) {
			$shareFolder = $config->getUserValue($userId, Application::APP_ID, 'share_folder', $systemDefault);
		}

		// Verify and sanitize path
		$shareFolder = Filesystem::normalizePath($shareFolder);

		// Init path if folder doesn't exists
		if (!$view->file_exists($shareFolder)) {
			$dir = '';
			$subdirs = explode('/', $shareFolder);
			foreach ($subdirs as $subdir) {
				$dir = $dir . '/' . $subdir;
				if (!$view->is_dir($dir)) {
					$view->mkdir($dir);
				}
			}
		}

		return $shareFolder;
	}

	/**
	 * set default share folder
	 *
	 * @param string $shareFolder
	 */
	public static function setShareFolder($shareFolder) {
		\OC::$server->getConfig()->setSystemValue('share_folder', $shareFolder);
	}
}
