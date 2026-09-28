<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\IImage;
use OCP\Server;
use Psr\Log\LoggerInterface;
use wapmorgan\Mp3Info\Mp3Info;
use function OCP\Log\logger;

class MP3 extends ProviderV2 {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/audio\/mpeg/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$tmpPath = $this->getLocalFile($file);
		if ($tmpPath === false) {
			Server::get(LoggerInterface::class)->error(
				'Failed to get local file to generate thumbnail for: ' . $file->getPath(),
				['app' => 'core']
			);
			return null;
		}

		try {
			$audio = new Mp3Info($tmpPath, true);
			/** @var string|null|false $picture */
			$picture = $audio->getCover();
		} catch (\Throwable $e) {
			logger('core')->info('Error while getting cover from mp3 file: ' . $e->getMessage(), [
				'fileId' => $file->getId(),
				'filePath' => $file->getPath(),
			]);
			return null;
		} finally {
			$this->cleanTmpFiles();
		}

		if (is_string($picture)) {
			$image = new \OCP\Image();
			$image->loadFromData($picture);

			if ($image->valid()) {
				$image->scaleDownToFit($maxX, $maxY);

				return $image;
			}
		}

		return null;
	}
}
