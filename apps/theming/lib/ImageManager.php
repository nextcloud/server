<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Haertl <jus@bitgrid.net>
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


namespace OCA\Theming;

use OCP\IConfig;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class ImageManager {

	/** @var IConfig */
	private $config;
	/** @var IAppData */
	private $appData;

	/**
	 * ImageManager constructor.
	 *
	 * @param IConfig $config
	 * @param IAppData $appData
	 */
	public function __construct(IConfig $config,
								IAppData $appData
	) {
		$this->config = $config;
		$this->appData = $appData;
	}

	/**
	 * Get folder for current theming files
	 *
	 * @return \OCP\Files\SimpleFS\ISimpleFolder
	 * @throws NotPermittedException
	 * @throws \RuntimeException
	 */
	public function getCacheFolder() {
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');
		try {
			$folder = $this->appData->getFolder($cacheBusterValue);
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder($cacheBusterValue);
			$this->cleanup();
		}
		return $folder;
	}

	/**
	 * Get a file from AppData
	 *
	 * @param string $filename
	 * @throws NotFoundException
	 * @return \OCP\Files\SimpleFS\ISimpleFile
	 */
	public function getCachedImage($filename) {
		$currentFolder = $this->getCacheFolder();
		return $currentFolder->getFile($filename);
	}

	/**
	 * Store a file for theming in AppData
	 *
	 * @param string $filename
	 * @param string $data
	 * @return \OCP\Files\SimpleFS\ISimpleFile
	 */
	public function setCachedImage($filename, $data) {
		$currentFolder = $this->getCacheFolder();
		if ($currentFolder->fileExists($filename)) {
			$file = $currentFolder->getFile($filename);
		} else {
			$file = $currentFolder->newFile($filename);
		}
		$file->putContent($data);
		return $file;
	}

	/**
	 * remove cached files that are not required any longer
	 */
	public function cleanup() {
		$currentFolder = $this->getCacheFolder();
		$folders = $this->appData->getDirectoryListing();
		foreach ($folders as $folder) {
			if ($folder->getName() !== 'images' && $folder->getName() !== $currentFolder->getName()) {
				$folder->delete();
			}
		}
	}
}
