<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\Archive\ZIP;
use OCP\Files\File;
use OCP\IImage;
use OCP\Image;
use OCP\ITempManager;
use OCP\Server;

/**
 * Extracts a preview from files that embed them in an ZIP archive
 */
abstract class Bundled extends ProviderV2 {
	protected function extractThumbnail(File $file, string $path): ?IImage {
		if ($file->getSize() === 0) {
			return null;
		}

		$sourceTmp = Server::get(ITempManager::class)->getTemporaryFile();
		$targetTmp = Server::get(ITempManager::class)->getTemporaryFile();
		$this->tmpFiles[] = $sourceTmp;
		$this->tmpFiles[] = $targetTmp;

		try {
			$content = $file->fopen('r');
			file_put_contents($sourceTmp, $content);

			$zip = new ZIP($sourceTmp);
			$zip->extractFile($path, $targetTmp);

			$image = new Image();
			$image->loadFromFile($targetTmp);
			$image->fixOrientation();

			return $image;
		} catch (\Throwable $e) {
			return null;
		}
	}
}
