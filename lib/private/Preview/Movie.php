<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IConfig;
use OCP\IImage;
use OCP\ITempManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class Movie extends ProviderV2 {
	private IConfig $config;

	private ?string $binary = null;

	public function __construct(array $options = []) {
		parent::__construct($options);
		$this->config = Server::get(IConfig::class);
	}

	public function getMimeType(): string {
		return '/video\/.*/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAvailable(FileInfo $file): bool {
		if (is_null($this->binary)) {
			if (isset($this->options['movieBinary'])) {
				$this->binary = $this->options['movieBinary'];
			}
		}
		return is_string($this->binary);
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

			$result = $this->generateThumbNail($maxX, $maxY, $absPath, 5);
			if ($result === null) {
				$result = $this->generateThumbNail($maxX, $maxY, $absPath, 1);
				if ($result === null) {
					$result = $this->generateThumbNail($maxX, $maxY, $absPath, 0);
				}
			}

			$this->cleanTmpFiles();

			if ($result !== null) {
				break;
			}
		}

		return $result;
	}

	private function useHdr(string $absPath): bool {
		// load ffprobe path from configuration, otherwise generate binary path using ffmpeg binary path
		$ffprobe_binary = $this->config->getSystemValue('preview_ffprobe_path', null) ?? (pathinfo($this->binary, PATHINFO_DIRNAME) . '/ffprobe');
		// run ffprobe on the video file to get value of "color_transfer"
		$test_hdr_cmd = [$ffprobe_binary,'-select_streams', 'v:0',
			'-show_entries', 'stream=color_transfer',
			'-of', 'default=noprint_wrappers=1:nokey=1',
			$absPath];
		$test_hdr_proc = proc_open($test_hdr_cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $test_hdr_pipes);
		if ($test_hdr_proc === false) {
			return false;
		}
		$test_hdr_stdout = trim(stream_get_contents($test_hdr_pipes[1]));
		$test_hdr_stderr = trim(stream_get_contents($test_hdr_pipes[2]));
		proc_close($test_hdr_proc);
		// search build options for libzimg (provides zscale filter)
		$ffmpeg_libzimg_installed = strpos($test_hdr_stderr, '--enable-libzimg');
		// Only values of "smpte2084" and "arib-std-b67" indicate an HDR video.
		// Only return true if video is detected as HDR and libzimg is installed.
		if (($test_hdr_stdout === 'smpte2084' || $test_hdr_stdout === 'arib-std-b67') && $ffmpeg_libzimg_installed !== false) {
			return true;
		} else {
			return false;
		}
	}

	private function generateThumbNail(int $maxX, int $maxY, string $absPath, int $second): ?IImage {
		$tmpPath = Server::get(ITempManager::class)->getTemporaryFile();

		if ($tmpPath === false) {
			Server::get(LoggerInterface::class)->error(
				'Failed to get local file to generate thumbnail for: ' . $absPath,
				['app' => 'core']
			);
			return null;
		}

		$binaryType = substr(strrchr($this->binary, '/'), 1);

		if ($binaryType === 'avconv') {
			$cmd = [$this->binary, '-y', '-ss', (string)$second,
				'-i', $absPath,
				'-an', '-f', 'mjpeg', '-vframes', '1', '-vsync', '1',
				$tmpPath];
		} elseif ($binaryType === 'ffmpeg') {
			if ($this->useHdr($absPath)) {
				// Force colorspace to '2020_ncl' because some videos are
				// tagged incorrectly as 'reserved' resulting in fail if not forced.
				$cmd = [$this->binary, '-y', '-ss', (string)$second,
					'-i', $absPath,
					'-f', 'mjpeg', '-vframes', '1',
					'-vf', 'zscale=min=2020_ncl:t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p',
					$tmpPath];
			} else {
				// always default to generating preview using non-HDR command
				$cmd = [$this->binary, '-y', '-ss', (string)$second,
					'-i', $absPath,
					'-f', 'mjpeg', '-vframes', '1',
					$tmpPath];
			}
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
			$logger = Server::get(LoggerInterface::class);
			$logger->info('Movie preview generation failed Output: {output}', ['app' => 'core', 'output' => $output]);
		}

		unlink($tmpPath);
		return null;
	}
}
