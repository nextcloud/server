<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Daniel Kesselberg (mail@danielkesselberg.de)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Repair\NC16;

use OC\Files\AppData\AppData;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class CleanupCardDAVPhotoCache
 *
 * This repair step removes "photo." files created by photocache
 *
 * Before https://github.com/nextcloud/server/pull/13843 a "photo." file could be created
 * for unsupported image formats by photocache. Because a file is present but not jpg, png or gif no
 * photo could be returned for this vcard. These invalid files are removed by this migration step.
 */
class CleanupCardDAVPhotoCache implements IRepairStep {

	/** @var IConfig */
	private $config;

	/** @var AppData */
	private $appData;

	public function __construct(IConfig $config, IAppData $appData) {
		$this->config = $config;
		$this->appData = $appData;
	}

	public function getName(): string {
		return 'Cleanup invalid photocache files for carddav';
	}

	private function repair(IOutput $output): void {
		try {
			$folders = $this->appData->getDirectoryListing();
		} catch (NotFoundException $e) {
			return;
		}

		$folders = array_filter($folders, function (ISimpleFolder $folder) {
			return $folder->fileExists('photo.');
		});

		if (empty($folders)) {
			return;
		}

		$output->info('Delete ' . count($folders) . ' "photo." files');

		foreach ($folders as $folder) {
			try {
				/** @var ISimpleFolder $folder */
				$folder->getFile('photo.')->delete();
			} catch (\Exception $e) {
				$output->warning('Could not delete "photo." file in dav-photocache/' . $folder->getName());
			}
		}
	}

	private function shouldRun(): bool {
		return version_compare(
			$this->config->getSystemValue('version', '0.0.0.0'),
			'16.0.0.0',
			'<='
		);
	}

	public function run(IOutput $output): void {
		if ($this->shouldRun()) {
			$this->repair($output);
		}
	}
}
