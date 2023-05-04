<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Gary Kim <gary@garykim.dev>
 * @author Jacob Neplokh <me@jacobneplokh.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author ste101 <stephan_bauer@gmx.de>
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
namespace OCA\Theming;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\BackgroundService;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\ITempManager;
use OCP\IURLGenerator;

class ImageManager {
	public const SUPPORTED_IMAGE_KEYS = ['background', 'logo', 'logoheader', 'favicon'];

	/** @var IConfig */
	private $config;
	/** @var IAppData */
	private $appData;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ICacheFactory */
	private $cacheFactory;
	/** @var ILogger */
	private $logger;
	/** @var ITempManager */
	private $tempManager;

	public function __construct(IConfig $config,
								IAppData $appData,
								IURLGenerator $urlGenerator,
								ICacheFactory $cacheFactory,
								ILogger $logger,
								ITempManager $tempManager) {
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->cacheFactory = $cacheFactory;
		$this->logger = $logger;
		$this->tempManager = $tempManager;
		$this->appData = $appData;
	}

	/**
	 * Get a globally defined image (admin theming settings)
	 *
	 * @param string $key the image key
	 * @return string the image url
	 */
	public function getImageUrl(string $key): string {
		$cacheBusterCounter = $this->config->getAppValue(Application::APP_ID, 'cachebuster', '0');
		if ($this->hasImage($key)) {
			return $this->urlGenerator->linkToRoute('theming.Theming.getImage', [ 'key' => $key ]) . '?v=' . $cacheBusterCounter;
		}

		switch ($key) {
			case 'logo':
			case 'logoheader':
			case 'favicon':
				return $this->urlGenerator->imagePath('core', 'logo/logo.png') . '?v=' . $cacheBusterCounter;
			case 'background':
				return $this->urlGenerator->linkTo(Application::APP_ID, 'img/background/' . BackgroundService::DEFAULT_BACKGROUND_IMAGE);
		}
		return '';
	}

	/**
	 * Get the absolute url. See getImageUrl
	 */
	public function getImageUrlAbsolute(string $key): string {
		return $this->urlGenerator->getAbsoluteURL($this->getImageUrl($key));
	}

	/**
	 * @param string $key
	 * @param bool $useSvg
	 * @return ISimpleFile
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function getImage(string $key, bool $useSvg = true): ISimpleFile {
		$logo = $this->config->getAppValue('theming', $key . 'Mime', '');
		$folder = $this->getRootFolder()->getFolder('images');

		if ($logo === '' || !$folder->fileExists($key)) {
			throw new NotFoundException();
		}

		if (!$useSvg && $this->shouldReplaceIcons()) {
			if (!$folder->fileExists($key . '.png')) {
				try {
					$finalIconFile = new \Imagick();
					$finalIconFile->setBackgroundColor('none');
					$finalIconFile->readImageBlob($folder->getFile($key)->getContent());
					$finalIconFile->setImageFormat('png32');
					$pngFile = $folder->newFile($key . '.png');
					$pngFile->putContent($finalIconFile->getImageBlob());
					return $pngFile;
				} catch (\ImagickException $e) {
					$this->logger->info('The image was requested to be no SVG file, but converting it to PNG failed: ' . $e->getMessage());
				}
			} else {
				return $folder->getFile($key . '.png');
			}
		}

		return $folder->getFile($key);
	}

	public function hasImage(string $key): bool {
		$mimeSetting = $this->config->getAppValue('theming', $key . 'Mime', '');
		return $mimeSetting !== '';
	}

	/**
	 * @return array<string, array{mime: string, url: string}>
	 */
	public function getCustomImages(): array {
		$images = [];
		foreach (self::SUPPORTED_IMAGE_KEYS as $key) {
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
			$folder = $this->getRootFolder()->getFolder($cacheBusterValue);
		} catch (NotFoundException $e) {
			$folder = $this->getRootFolder()->newFolder($cacheBusterValue);
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

	public function delete(string $key): void {
		/* ignore exceptions, since we don't want to fail hard if something goes wrong during cleanup */
		try {
			$file = $this->getRootFolder()->getFolder('images')->getFile($key);
			$file->delete();
		} catch (NotFoundException $e) {
		} catch (NotPermittedException $e) {
		}
		try {
			$file = $this->getRootFolder()->getFolder('images')->getFile($key . '.png');
			$file->delete();
		} catch (NotFoundException $e) {
		} catch (NotPermittedException $e) {
		}
	}

	public function updateImage(string $key, string $tmpFile): string {
		$this->delete($key);

		try {
			$folder = $this->getRootFolder()->getFolder('images');
		} catch (NotFoundException $e) {
			$folder = $this->getRootFolder()->newFolder('images');
		}

		$target = $folder->newFile($key);
		$supportedFormats = $this->getSupportedUploadImageFormats($key);
		$detectedMimeType = mime_content_type($tmpFile);
		if (!in_array($detectedMimeType, $supportedFormats, true)) {
			throw new \Exception('Unsupported image type');
		}

		if ($key === 'background' && $this->shouldOptimizeBackgroundImage($detectedMimeType, filesize($tmpFile))) {
			try {
				// Optimize the image since some people may upload images that will be
				// either to big or are not progressive rendering.
				$newImage = @imagecreatefromstring(file_get_contents($tmpFile));
				if ($newImage === false) {
					throw new \Exception('Could not read background image, possibly corrupted.');
				}

				// Preserve transparency
				imagesavealpha($newImage, true);
				imagealphablending($newImage, true);

				$newWidth = (int)(imagesx($newImage) < 4096 ? imagesx($newImage) : 4096);
				$newHeight = (int)(imagesy($newImage) / (imagesx($newImage) / $newWidth));
				$outputImage = imagescale($newImage, $newWidth, $newHeight);
				if ($outputImage === false) {
					throw new \Exception('Could not scale uploaded background image.');
				}

				$newTmpFile = $this->tempManager->getTemporaryFile();
				imageinterlace($outputImage, 1);
				// Keep jpeg images encoded as jpeg
				if (strpos($detectedMimeType, 'image/jpeg') !== false) {
					if (!imagejpeg($outputImage, $newTmpFile, 90)) {
						throw new \Exception('Could not recompress background image as JPEG');
					}
				} else {
					if (!imagepng($outputImage, $newTmpFile, 8)) {
						throw new \Exception('Could not recompress background image as PNG');
					}
				}
				$tmpFile = $newTmpFile;
				imagedestroy($outputImage);
			} catch (\Exception $e) {
				if (is_resource($outputImage) || $outputImage instanceof \GdImage) {
					imagedestroy($outputImage);
				}

				$this->logger->debug($e->getMessage());
			}
		}

		$target->putContent(file_get_contents($tmpFile));

		return $detectedMimeType;
	}

	/**
	 * Decide whether an image benefits from shrinking and reconverting
	 *
	 * @param string $mimeType the mime type of the image
	 * @param int $contentSize size of the image file
	 * @return bool
	 */
	private function shouldOptimizeBackgroundImage(string $mimeType, int $contentSize): bool {
		// Do not touch SVGs
		if (strpos($mimeType, 'image/svg') !== false) {
			return false;
		}
		// GIF does not benefit from converting
		if (strpos($mimeType, 'image/gif') !== false) {
			return false;
		}
		// WebP also does not benefit from converting
		// We could possibly try to convert to progressive image, but normally webP images are quite small
		if (strpos($mimeType, 'image/webp') !== false) {
			return false;
		}
		// As a rule of thumb background images should be max. 150-300 KiB, small images do not benefit from converting
		return $contentSize > 150000;
	}

	/**
	 * Returns a list of supported mime types for image uploads.
	 * "favicon" images are only allowed to be SVG when imagemagick with SVG support is available.
	 *
	 * @param string $key The image key, e.g. "favicon"
	 * @return string[]
	 */
	private function getSupportedUploadImageFormats(string $key): array {
		$supportedFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

		if ($key !== 'favicon' || $this->shouldReplaceIcons() === true) {
			$supportedFormats[] = 'image/svg+xml';
			$supportedFormats[] = 'image/svg';
		}

		if ($key === 'favicon') {
			$supportedFormats[] = 'image/x-icon';
			$supportedFormats[] = 'image/vnd.microsoft.icon';
		}

		return $supportedFormats;
	}

	/**
	 * remove cached files that are not required any longer
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function cleanup() {
		$currentFolder = $this->getCacheFolder();
		$folders = $this->getRootFolder()->getDirectoryListing();
		foreach ($folders as $folder) {
			if ($folder->getName() !== 'images' && $folder->getName() !== $currentFolder->getName()) {
				$folder->delete();
			}
		}
	}

	/**
	 * Check if Imagemagick is enabled and if SVG is supported
	 * otherwise we can't render custom icons
	 *
	 * @return bool
	 */
	public function shouldReplaceIcons() {
		$cache = $this->cacheFactory->createDistributed('theming-' . $this->urlGenerator->getBaseUrl());
		if ($value = $cache->get('shouldReplaceIcons')) {
			return (bool)$value;
		}
		$value = false;
		if (extension_loaded('imagick')) {
			if (count(\Imagick::queryFormats('SVG')) >= 1) {
				$value = true;
			}
		}
		$cache->set('shouldReplaceIcons', $value);
		return $value;
	}

	private function getRootFolder(): ISimpleFolder {
		try {
			return $this->appData->getFolder('global');
		} catch (NotFoundException $e) {
			return $this->appData->newFolder('global');
		}
	}
}
