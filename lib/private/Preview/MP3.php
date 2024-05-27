<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\IImage;
use Psr\Log\LoggerInterface;
use wapmorgan\Mp3Info\Mp3Info;

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

		try {
			$audio = new Mp3Info($tmpPath, true);
			/** @var string|null|false $picture */
			$picture = $audio->getCover();
		} catch (\Throwable $e) {
			\OC::$server->get(LoggerInterface::class)->info($e->getMessage(), [
				'exception' => $e,
				'app' => 'core',
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
