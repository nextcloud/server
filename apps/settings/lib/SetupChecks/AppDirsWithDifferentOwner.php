<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class AppDirsWithDifferentOwner implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('App directories owner');
	}

	public function getCategory(): string {
		return 'security';
	}

	/**
	 * Iterates through the configured app roots and
	 * tests if the subdirectories are owned by the same user than the current user.
	 *
	 * @return string[]
	 */
	private function getAppDirsWithDifferentOwner(int $currentUser): array {
		$appDirsWithDifferentOwner = [[]];

		foreach (\OC::$APPSROOTS as $appRoot) {
			if ($appRoot['writable'] === true) {
				$appDirsWithDifferentOwner[] = $this->getAppDirsWithDifferentOwnerForAppRoot($currentUser, $appRoot);
			}
		}

		$appDirsWithDifferentOwner = array_merge(...$appDirsWithDifferentOwner);
		sort($appDirsWithDifferentOwner);

		return $appDirsWithDifferentOwner;
	}

	/**
	 * Tests if the directories for one apps directory are writable by the current user.
	 *
	 * @param int $currentUser The current user
	 * @param array $appRoot The app root config
	 * @return string[] The none writable directory paths inside the app root
	 */
	private function getAppDirsWithDifferentOwnerForAppRoot(int $currentUser, array $appRoot): array {
		$appDirsWithDifferentOwner = [];
		$appsPath = $appRoot['path'];
		$appsDir = new \DirectoryIterator($appRoot['path']);

		foreach ($appsDir as $fileInfo) {
			if ($fileInfo->isDir() && !$fileInfo->isDot()) {
				$absAppPath = $appsPath . DIRECTORY_SEPARATOR . $fileInfo->getFilename();
				$appDirUser = fileowner($absAppPath);
				if ($appDirUser !== $currentUser) {
					$appDirsWithDifferentOwner[] = $absAppPath;
				}
			}
		}

		return $appDirsWithDifferentOwner;
	}

	public function run(): SetupResult {
		$currentUser = posix_getuid();
		$currentUserInfos = posix_getpwuid($currentUser) ?: [];
		$appDirsWithDifferentOwner = $this->getAppDirsWithDifferentOwner($currentUser);
		if (count($appDirsWithDifferentOwner) > 0) {
			return SetupResult::warning(
				$this->l10n->t("Some app directories are owned by a different user than the web server one. This may be the case if apps have been installed manually. Check the permissions of the following app directories:\n%s", implode("\n", $appDirsWithDifferentOwner))
			);
		} else {
			return SetupResult::success($this->l10n->t('App directories have the correct owner "%s"', [$currentUserInfos['name'] ?? '']));
		}
	}
}
