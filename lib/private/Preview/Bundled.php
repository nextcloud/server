<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\Archive\ZIP;
use OCP\Files\File;
use OCP\IConfig;
use OCP\IImage;
use OCP\ITempManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Extracts a preview from files that embed them in an ZIP archive
 */
abstract class Bundled extends ProviderV2 {
	protected function extractThumbnail(File $file, string $path): ?IImage {
		$previewMaxFilesize = Server::get(IConfig::class)->getSystemValueInt('preview_max_filesize_image', 50);
		$size = $file->getSize();
		if ($size === 0) {
			return null;
		}
		if ($previewMaxFilesize !== -1 && $size > ($previewMaxFilesize * 1024 * 1024)) {
			return null;
		}

		$sourceTmp = Server::get(ITempManager::class)->getTemporaryFile();
		$targetTmp = Server::get(ITempManager::class)->getTemporaryFile();
		$this->tmpFiles[] = $sourceTmp;
		$this->tmpFiles[] = $targetTmp;

		try {
			/** @var resource|false|null */
			$src = $file->fopen('r');
			if ($src === false) {
				Server::get(LoggerInterface::class)->debug(
					'Unable to extract thumbnail - fopen source failed',
					['path' => $path]
				);
				return null;
			}

			/** @var resource|false|null */
			$dst = fopen($sourceTmp, 'wb');
			if ($dst === false) {
				Server::get(LoggerInterface::class)->debug(
					'Unable to extract thumbnail - fopen destination failed',
					['path' => $path]
				);
				return null;
			}

			$bytes = stream_copy_to_stream($src, $dst);
			if ($bytes === false || $bytes === 0) {
				Server::get(LoggerInterface::class)->debug(
					'Unable to extract thumbnail - copy returned 0 bytes',
					['path' => $path]
				);
				return null;
			}

			fclose($dst);
			$dst = null;
			fclose($src);
			$src = null;

			$zip = new ZIP($sourceTmp);
			$result = $zip->extractFile($path, $targetTmp);
			if ($result === false || !file_exists($targetTmp) || filesize($targetTmp) === 0) {
				Server::get(LoggerInterface::class)->debug(
					'Unable to extract thumbnail - extractFile failed or target missing/empty',
					['path' => $path]
				);
				return null;
			}

			$image = new \OCP\Image();
			$image->loadFromFile($targetTmp);
			$image->fixOrientation();

			return $image;
		} catch (\Throwable $e) {
			Server::get(LoggerInterface::class)->debug(
				'Unable to extract thumbnail - exception while extracting',
				['message' => $e->getMessage(), 'path' => $path]
			);
			return null;
		} finally {
			if (isset($dst) && is_resource($dst)) {
				fclose($dst);
			}
			if (isset($src) && is_resource($src)) {
				fclose($src);
			}
			$this->cleanTmpFiles();
		}
	}
}
