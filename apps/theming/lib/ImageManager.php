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

use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IURLGenerator;

/**
 * @property IURLGenerator urlGenerator
 */
class ImageManager {

	/** @var IConfig */
	private $config;
	/** @var IAppData */
	private $appData;

	/** @var array */
	private $supportedImageKeys = ['background', 'logo', 'logoheader', 'favicon'];

	/**
	 * ImageManager constructor.
	 *
	 * @param IConfig $config
	 * @param IAppData $appData
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IConfig $config,
								IAppData $appData,
								IURLGenerator $urlGenerator
	) {
		$this->config = $config;
		$this->appData = $appData;
		$this->urlGenerator = $urlGenerator;
	}

	public function getImageUrl(string $key): string {
		$cacheBusterCounter = $this->config->getAppValue('theming', 'cachebuster', '0');
		try {
			$this->getImage($key);
			return $this->urlGenerator->linkToRoute('theming.Theming.getImage', [ 'key' => $key ]) . '?v=' . $cacheBusterCounter;
		} catch (NotFoundException $e) {
		}

		switch ($key) {
			case 'logo':
			case 'logoheader':
			case 'favicon':
				return $this->urlGenerator->imagePath('core', 'logo.png') . '?v=' . $cacheBusterCounter;
			case 'background':
				return $this->urlGenerator->imagePath('core', 'background.png') . '?v=' . $cacheBusterCounter;
		}
	}

	public function getImageUrlAbsolute(string $key): string {
		return $this->urlGenerator->getAbsoluteURL($this->getImageUrl($key));
	}

	/**
	 * @param $key
	 * @return ISimpleFile
	 * @throws NotFoundException
	 */
	public function getImage(string $key): ISimpleFile {
		$logo = $this->config->getAppValue('theming', $key . 'Mime', false);
		if ($logo === false) {
			throw new NotFoundException();
		}
		$folder = $this->appData->getFolder('images');
		return $folder->getFile($key);
	}

	public function getCustomImages(): array {
		$images = [];
		foreach ($this->supportedImageKeys as $key) {
			$images[$key] = [
				'mime' => $this->config->getAppValue('theming', $key . 'Mime', ''),
				'url' => $this->getImageUrl($key),
			];
		}
		return $images;
	}

	/**
	 * Get folder for current theming files
	 *
	 * @return ISimpleFolder
	 * @throws NotPermittedException
	 */
	public function getCacheFolder(): ISimpleFolder {
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
	 * @throws NotPermittedException
	 */
	public function getCachedImage(string $filename): ISimpleFile {
		$currentFolder = $this->getCacheFolder();
		return $currentFolder->getFile($filename);
	}

	/**
	 * Store a file for theming in AppData
	 *
	 * @param string $filename
	 * @param string $data
	 * @return \OCP\Files\SimpleFS\ISimpleFile
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function setCachedImage(string $filename, string $data): ISimpleFile {
		$currentFolder = $this->getCacheFolder();
		if ($currentFolder->fileExists($filename)) {
			$file = $currentFolder->getFile($filename);
		} else {
			$file = $currentFolder->newFile($filename);
		}
		$file->putContent($data);
		return $file;
	}

	public function delete(string $key) {
		try {
			$file = $this->appData->getFolder('images')->getFile($key);
			$file->delete();
		} catch (NotFoundException $e) {
		} catch (NotPermittedException $e) {
		}
	}

	/**
	 * remove cached files that are not required any longer
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
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
