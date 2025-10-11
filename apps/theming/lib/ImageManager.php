<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use OCP\ITempManager;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class ImageManager {
	public const SUPPORTED_IMAGE_KEYS = ['background', 'logo', 'logoheader', 'favicon'];

	public function __construct(
		private IConfig $config,
		private IAppData $appData,
		private IURLGenerator $urlGenerator,
		private ICacheFactory $cacheFactory,
		private LoggerInterface $logger,
		private ITempManager $tempManager,
		private BackgroundService $backgroundService,
	) {
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
		} elseif ($key === 'backgroundDark' && $this->hasImage('background')) {
			// Fall back to light variant
			return $this->urlGenerator->linkToRoute('theming.Theming.getImage', [ 'key' => 'background' ]) . '?v=' . $cacheBusterCounter;
		}

		switch ($key) {
			case 'logo':
			case 'logoheader':
			case 'favicon':
				return $this->urlGenerator->imagePath('core', 'logo/logo.png') . '?v=' . $cacheBusterCounter;
			case 'backgroundDark':
			case 'background':
				// Removing the background defines its mime as 'backgroundColor'
				$mimeSetting = $this->config->getAppValue('theming', 'backgroundMime', '');
				if ($mimeSetting !== 'backgroundColor') {
					$image = BackgroundService::DEFAULT_BACKGROUND_IMAGE;
					if ($key === 'backgroundDark') {
						$image = BackgroundService::SHIPPED_BACKGROUNDS[$image]['dark_variant'] ?? $image;
					}
					return $this->urlGenerator->linkTo(Application::APP_ID, "img/background/$image");
				}
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
		$mime = $this->config->getAppValue('theming', $key . 'Mime', '');
		$folder = $this->getRootFolder()->getFolder('images');
		$useSvg = $useSvg && $this->canConvert('SVG');

		if ($mime === '' || !$folder->fileExists($key)) {
			throw new NotFoundException();
		}
		// if SVG was requested and is supported
		if ($useSvg) {
			if (!$folder->fileExists($key . '.svg')) {
				try {
					$finalIconFile = new \Imagick();
					$finalIconFile->setBackgroundColor('none');
					$finalIconFile->readImageBlob($folder->getFile($key)->getContent());
					$finalIconFile->setImageFormat('SVG');
					$svgFile = $folder->newFile($key . '.svg');
					$svgFile->putContent($finalIconFile->getImageBlob());
					return $svgFile;
				} catch (\ImagickException $e) {
					$this->logger->info('The image was requested to be no SVG file, but converting it to SVG failed: ' . $e->getMessage());
				}
			} else {
				return $folder->getFile($key . '.svg');
			}
		}
		// if SVG was not requested, but PNG is supported
		if (!$useSvg && $this->canConvert('PNG')) {
			if (!$folder->fileExists($key . '.png')) {
				try {
					$finalIconFile = new \Imagick();
					$finalIconFile->setBackgroundColor('none');
					$finalIconFile->readImageBlob($folder->getFile($key)->getContent());
					$finalIconFile->setImageFormat('PNG32');
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
		// fallback to the original file
		return $folder->getFile($key);
	}

	public function hasImage(string $key): bool {
		$mimeSetting = $this->config->getAppValue('theming', $key . 'Mime', '');
		// Removing the background defines its mime as 'backgroundColor'
		return $mimeSetting !== '' && $mimeSetting !== 'backgroundColor';
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
	 * @return ISimpleFile
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
	 * @return ISimpleFile
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

		if ($key === 'logo') {
			$this->config->deleteAppValue('theming', 'logoDimensions');
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
			throw new \Exception('Unsupported image type: ' . $detectedMimeType);
		}

		if ($key === 'background') {
			if ($this->shouldOptimizeBackgroundImage($detectedMimeType, filesize($tmpFile))) {
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

					$imageWidth = imagesx($newImage);
					$imageHeight = imagesy($newImage);

					/** @var int */
					$newWidth = min(4096, $imageWidth);
					$newHeight = intval($imageHeight / ($imageWidth / $newWidth));
					$outputImage = imagescale($newImage, $newWidth, $newHeight);
					if ($outputImage === false) {
						throw new \Exception('Could not scale uploaded background image.');
					}

					$newTmpFile = $this->tempManager->getTemporaryFile();
					imageinterlace($outputImage, true);
					// Keep jpeg images encoded as jpeg
					if (str_contains($detectedMimeType, 'image/jpeg')) {
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
					if (isset($outputImage) && is_resource($outputImage) || $outputImage instanceof \GdImage) {
						imagedestroy($outputImage);
					}

					$this->logger->debug($e->getMessage());
				}
			}

			// For background images we need to announce it
			$this->backgroundService->setGlobalBackground($tmpFile);
		}

		$target->putContent(file_get_contents($tmpFile));

		if ($key === 'logo') {
			$content = file_get_contents($tmpFile);
			$newImage = @imagecreatefromstring($content);
			if ($newImage !== false) {
				$this->config->setAppValue('theming', 'logoDimensions', imagesx($newImage) . 'x' . imagesy($newImage));
			} elseif (str_starts_with($detectedMimeType, 'image/svg')) {
				$matched = preg_match('/viewbox=["\']\d* \d* (\d*\.?\d*) (\d*\.?\d*)["\']/i', $content, $matches);
				if ($matched) {
					$this->config->setAppValue('theming', 'logoDimensions', $matches[1] . 'x' . $matches[2]);
				} else {
					$this->logger->warning('Could not read logo image dimensions to optimize for mail header');
					$this->config->deleteAppValue('theming', 'logoDimensions');
				}
			} else {
				$this->logger->warning('Could not read logo image dimensions to optimize for mail header');
				$this->config->deleteAppValue('theming', 'logoDimensions');
			}
		}

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
		if (str_contains($mimeType, 'image/svg')) {
			return false;
		}
		// GIF does not benefit from converting
		if (str_contains($mimeType, 'image/gif')) {
			return false;
		}
		// WebP also does not benefit from converting
		// We could possibly try to convert to progressive image, but normally webP images are quite small
		if (str_contains($mimeType, 'image/webp')) {
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
	public function getSupportedUploadImageFormats(string $key): array {
		$supportedFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

		if ($key !== 'favicon' || $this->canConvert('SVG') === true) {
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
		return $this->canConvert('SVG');
	}

	/**
	 * Check if Imagemagick is enabled and if format is supported
	 *
	 * @return bool
	 */
	public function canConvert(string $format = 'SVG'): bool {
		$cache = $this->cacheFactory->createDistributed('theming-' . $this->urlGenerator->getBaseUrl());
		if ($value = $cache->get('convert-' . $format)) {
			return (bool)$value;
		}
		$value = false;
		if (extension_loaded('imagick')) {
			if (count(\Imagick::queryFormats($format)) >= 1) {
				$value = true;
			}
		}
		$cache->set('convert-' . $format, $value);
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
