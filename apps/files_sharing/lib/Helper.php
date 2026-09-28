<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\IConfig;
use OCP\Server;
use OCP\Util;

class Helper {
	public static function registerHooks() {
		Util::connectHook('OC_Filesystem', 'post_rename', '\OCA\Files_Sharing\Updater', 'renameHook');
		Util::connectHook('OC_Filesystem', 'post_delete', '\OCA\Files_Sharing\Hooks', 'unshareChildren');

		Util::connectHook('OC_User', 'post_deleteUser', '\OCA\Files_Sharing\Hooks', 'deleteUser');
	}

	/**
	 * check if file name already exists and generate unique target
	 *
	 * @param string $path
	 * @param View $view
	 * @return string $path
	 */
	public static function generateUniqueTarget($path, $view) {
		$pathinfo = pathinfo($path);
		$ext = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
		$name = $pathinfo['filename'];
		$dir = $pathinfo['dirname'];
		$i = 2;
		while ($view->file_exists($path)) {
			$path = Filesystem::normalizePath($dir . '/' . $name . ' (' . $i . ')' . $ext);
			$i++;
		}

		return $path;
	}

	/**
	 * get default share folder
	 *
	 * @param View|null $view
	 * @param string|null $userId
	 * @return string
	 */
	public static function getShareFolder(?View $view = null, ?string $userId = null): string {
		if ($view === null) {
			$view = Filesystem::getView();
		}

		$config = Server::get(IConfig::class);
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
		Server::get(IConfig::class)->setSystemValue('share_folder', $shareFolder);
	}
}
