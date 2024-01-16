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
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class TempSpaceAvailableIfS3PrimaryStorage implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Temporary space available');
	}

	public function getCategory(): string {
		return 'system';
	}

	public function run(): SetupResult {
		$objectStore = $this->config->getSystemValue('objectstore', null);
		$objectStoreMultibucket = $this->config->getSystemValue('objectstore_multibucket', null);

		// TODO we should check and display temp space available even if not s3
		if (!isset($objectStoreMultibucket) && !isset($objectStore)) {
			return SetupResult::success($this->l10n->t('This instance does not use an S3 based object store as primary storage'));
		}

		if (isset($objectStoreMultibucket['class']) && $objectStoreMultibucket['class'] !== 'OC\\Files\\ObjectStore\\S3') {
			return SetupResult::success($this->l10n->t('This instance does not use an S3 based object store as primary storage'));
		}

		if (isset($objectStore['class']) && $objectStore['class'] !== 'OC\\Files\\ObjectStore\\S3') {
			return SetupResult::success($this->l10n->t('This instance does not use an S3 based object store as primary storage'));
		}

		$tempPath = sys_get_temp_dir();
		if (!is_dir($tempPath)) {
			return SetupResult::error($this->l10n->t('Error while checking the temporary PHP path - it was not properly set to a directory. Returned value: %s', [$tempPath]));
		}
		$freeSpaceInTemp = function_exists('disk_free_space') ? disk_free_space($tempPath) : false;
		if ($freeSpaceInTemp === false) {
			return SetupResult::error($this->l10n->t('Error while checking the available disk space of temporary PHP path or no free disk space returned. Temporary path: %s', [$tempPath]));
		}

		$freeSpaceInTempInGB = $freeSpaceInTemp / 1024 / 1024 / 1024;
		if ($freeSpaceInTempInGB > 50) {
			return SetupResult::success(
				$this->l10n->t(
					"This instance uses an S3 based object store as primary storage, and has enough space in the temporary directory.\nAvailable: %.1f GiB\nPath: %s",
					[round($freeSpaceInTempInGB, 1),$tempPath]
				)
			);
		}

		return SetupResult::warning(
			$this->l10n->t(
				"This instance uses an S3 based object store as primary storage. The uploaded files are stored temporarily on the server and thus it is recommended to have 50 GiB of free space available in the temp directory of PHP. To improve this please change the temporary directory in the php.ini or make more space available in that path. \nChecking the available space in the temporary path resulted in %.1f GiB instead of the recommended 50 GiB. Path: %s",
				[round($freeSpaceInTempInGB, 1),$tempPath]
			)
		);
	}
}
