<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class TempSpaceAvailable implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private ITempManager $tempManager,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Temporary space available');
	}

	public function getCategory(): string {
		return 'system';
	}

	private function isPrimaryStorageS3(): bool {
		$objectStore = $this->config->getSystemValue('objectstore', null);
		$objectStoreMultibucket = $this->config->getSystemValue('objectstore_multibucket', null);

		if (!isset($objectStoreMultibucket) && !isset($objectStore)) {
			return false;
		}

		if (isset($objectStoreMultibucket['class']) && $objectStoreMultibucket['class'] !== 'OC\\Files\\ObjectStore\\S3') {
			return false;
		}

		if (isset($objectStore['class']) && $objectStore['class'] !== 'OC\\Files\\ObjectStore\\S3') {
			return false;
		}

		return true;
	}

	public function run(): SetupResult {
		$phpTempPath = sys_get_temp_dir();
		$nextcloudTempPath = '';
		try {
			$nextcloudTempPath = $this->tempManager->getTempBaseDir();
		} catch (\Exception $e) {
		}

		if (empty($nextcloudTempPath)) {
			return SetupResult::error('The temporary directory of this instance points to an either non-existing or non-writable directory.');
		}

		if (!is_dir($phpTempPath)) {
			return SetupResult::error($this->l10n->t('Error while checking the temporary PHP path - it was not properly set to a directory. Returned value: %s', [$phpTempPath]));
		}

		if (!function_exists('disk_free_space')) {
			return SetupResult::info($this->l10n->t('The PHP function "disk_free_space" is disabled, which prevents the check for enough space in the temporary directories.'));
		}

		$freeSpaceInTemp = disk_free_space($phpTempPath);
		if ($freeSpaceInTemp === false) {
			return SetupResult::error($this->l10n->t('Error while checking the available disk space of temporary PHP path or no free disk space returned. Temporary path: %s', [$phpTempPath]));
		}

		/** Build details data about temporary directory, either one or two of them */
		$freeSpaceInTempInGB = $freeSpaceInTemp / 1024 / 1024 / 1024;
		$spaceDetail = $this->l10n->t('- %.1f GiB available in %s (PHP temporary directory)', [round($freeSpaceInTempInGB, 1),$phpTempPath]);
		if ($nextcloudTempPath !== $phpTempPath) {
			$freeSpaceInNextcloudTemp = disk_free_space($nextcloudTempPath);
			if ($freeSpaceInNextcloudTemp === false) {
				return SetupResult::error($this->l10n->t('Error while checking the available disk space of temporary PHP path or no free disk space returned. Temporary path: %s', [$nextcloudTempPath]));
			}
			$freeSpaceInNextcloudTempInGB = $freeSpaceInNextcloudTemp / 1024 / 1024 / 1024;
			$spaceDetail .= "\n".$this->l10n->t('- %.1f GiB available in %s (Nextcloud temporary directory)', [round($freeSpaceInNextcloudTempInGB, 1),$nextcloudTempPath]);
		}

		if (!$this->isPrimaryStorageS3()) {
			return SetupResult::success(
				$this->l10n->t("Temporary directory is correctly configured:\n%s", [$spaceDetail])
			);
		}

		if ($freeSpaceInTempInGB > 50) {
			return SetupResult::success(
				$this->l10n->t(
					"This instance uses an S3 based object store as primary storage, and has enough space in the temporary directory.\n%s",
					[$spaceDetail]
				)
			);
		}

		return SetupResult::warning(
			$this->l10n->t(
				"This instance uses an S3 based object store as primary storage. The uploaded files are stored temporarily on the server and thus it is recommended to have 50 GiB of free space available in the temp directory of PHP. To improve this please change the temporary directory in the php.ini or make more space available in that path. \nChecking the available space in the temporary path resulted in %.1f GiB instead of the recommended 50 GiB. Path: %s",
				[round($freeSpaceInTempInGB, 1),$phpTempPath]
			)
		);
	}
}
