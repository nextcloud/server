<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Server;
use Psr\Log\LoggerInterface;

class Movie extends ProviderV2 {
	/**
	 * @deprecated 23.0.0 pass option to \OCP\Preview\ProviderV2
	 * @var string
	 */
	public static $avconvBinary;

	/**
	 * @deprecated 23.0.0 pass option to \OCP\Preview\ProviderV2
	 * @var string
	 */
	public static $ffmpegBinary;

	/** @var string */
	private $binary;

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/video\/.*/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAvailable(FileInfo $file): bool {
		// TODO: remove when avconv is dropped
		if (is_null($this->binary)) {
			if (isset($this->options['movieBinary'])) {
				$this->binary = $this->options['movieBinary'];
			} elseif (is_string(self::$avconvBinary)) {
				$this->binary = self::$avconvBinary;
			} elseif (is_string(self::$ffmpegBinary)) {
				$this->binary = self::$ffmpegBinary;
			}
		}
		return is_string($this->binary);
	}

	private function connectDirect(File $file): string|false {
		if ($file->isEncrypted()) {
			return false;
		}

		// Checks for availability to access the video file directly via HTTP/HTTPS.
		// Returns a string containing URL if available. Only implemented and tested
		// with Amazon S3 currently. In all other cases, return false. ffmpeg
		// supports other protocols so this function may expand in the future.
		$gddValues = $file->getStorage()->getDirectDownloadById((string)$file->getId());

		if (is_array($gddValues) && array_key_exists('url', $gddValues)) {
			return str_starts_with($gddValues['url'], 'http') ? $gddValues['url'] : false;
		}
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		// TODO: use proc_open() and stream the source file ?

		if (!$this->isAvailable($file)) {
			return null;
		}

		$result = null;
		if ($this->useTempFile($file)) {
			// Try downloading 5 MB first, as it's likely that the first frames are present there.
			// In some cases this doesn't work, for example when the moov atom is at the
			// end of the file, so if it fails we fall back to getting the full file.
			// Unless the file is not local (e.g. S3) as we do not want to download the whole (e.g. 37Gb) file
			if ($file->getStorage()->isLocal()) {
				$sizeAttempts = [5242880, null];
			} else {
				$sizeAttempts = [5242880];
			}
		} else {
			// size is irrelevant, only attempt once
			$sizeAttempts = [null];
		}

		foreach ($sizeAttempts as $size) {
			$absPath = $this->getLocalFile($file, $size);
			if ($absPath === false) {
				Server::get(LoggerInterface::class)->error(
					'Failed to get local file to generate thumbnail for: ' . $file->getPath(),
					['app' => 'core']
				);
				return null;
			}

			$result = null;
			if (is_string($absPath)) {
				$result = $this->generateThumbNail($maxX, $maxY, $absPath, 5);
				if ($result === null) {
					$result = $this->generateThumbNail($maxX, $maxY, $absPath, 1);
					if ($result === null) {
						$result = $this->generateThumbNail($maxX, $maxY, $absPath, 0);
					}
				}
			}

			$this->cleanTmpFiles();

			if ($result !== null) {
				break;
			}
		}

		return $result;
	}

	private function generateThumbNail(int $maxX, int $maxY, string $absPath, int $second): ?IImage {
		$tmpPath = \OC::$server->getTempManager()->getTemporaryFile();

		$binaryType = substr(strrchr($this->binary, '/'), 1);

		if ($binaryType === 'avconv') {
			$cmd = [$this->binary, '-y', '-ss', (string)$second,
				'-i', $absPath,
				'-an', '-f', 'mjpeg', '-vframes', '1', '-vsync', '1',
				$tmpPath];
		} elseif ($binaryType === 'ffmpeg') {
			$cmd = [$this->binary, '-y', '-ss', (string)$second,
				'-i', $absPath,
				'-f', 'mjpeg', '-vframes', '1',
				$tmpPath];
		} else {
			// Not supported
			unlink($tmpPath);
			return null;
		}

		$proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
		$returnCode = -1;
		$output = '';
		if (is_resource($proc)) {
			$stderr = trim(stream_get_contents($pipes[2]));
			$stdout = trim(stream_get_contents($pipes[1]));
			$returnCode = proc_close($proc);
			$output = $stdout . $stderr;
		}

		if ($returnCode === 0) {
			$image = new \OCP\Image();
			$image->loadFromFile($tmpPath);
			if ($image->valid()) {
				unlink($tmpPath);
				$image->scaleDownToFit($maxX, $maxY);

				return $image;
			}
		}

		if ($second === 0) {
			$logger = \OC::$server->get(LoggerInterface::class);
			$logger->info('Movie preview generation failed Output: {output}', ['app' => 'core', 'output' => $output]);
		}

		unlink($tmpPath);
		return null;
	}
}
