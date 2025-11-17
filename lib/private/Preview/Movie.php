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
use OCP\Image;
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

		// Timestamps to make attempts to generate a still
		$timeAttempts = [5, 1, 0];

		// By default, download $sizeAttempts from the file along with
		// the 'moov' atom.
		// Example bitrates in the higher range:
		// 4K HDR H265 60 FPS = 75 Mbps = 9 MB per second needed for a still
		// 1080p H265 30 FPS = 10 Mbps = 1.25 MB per second needed for a still
		// 1080p H264 30 FPS = 16 Mbps = 2 MB per second needed for a still
		$sizeAttempts = [1024 * 1024 * 10];

		if ($this->useTempFile($file)) {
			if ($file->getStorage()->isLocal()) {
				// Temp file required but file is local, so retrieve $sizeAttempt bytes first,
				// and if it doesn't work, retrieve the entire file.
				$sizeAttempts[] = null;
			}
		} else {
			// Temp file is not required and file is local so retrieve entire file.
			$sizeAttempts = [null];
		}

		foreach ($sizeAttempts as $size) {
			$absPath = false;
			// File is remote, generate a sparse file
			if (!$file->getStorage()->isLocal()) {
				$absPath = $this->getSparseFile($file, $size);
			}
			// Defaults to existing routine if generating sparse file fails
			if ($absPath === false) {
				$absPath = $this->getLocalFile($file, $size);
			}
			if ($absPath === false) {
				Server::get(LoggerInterface::class)->error(
					'Failed to get local file to generate thumbnail for: ' . $file->getPath(),
					['app' => 'core']
				);
				return null;
			}

			// Attempt still image grabs from selected timestamps
			foreach ($timeAttempts as $timeStamp) {
				$result = $this->generateThumbNail($maxX, $maxY, $absPath, $timeStamp);
				if ($result !== null) {
					break;
				}
				Server::get(LoggerInterface::class)->debug(
					'Movie preview generation attempt failed'
						. ', file=' . $file->getPath()
						. ', time=' . $timeStamp
						. ', size=' . ($size ?? 'entire file'),
					['app' => 'core']
				);
			}

			$this->cleanTmpFiles();

			if ($result !== null) {
				Server::get(LoggerInterface::class)->debug(
					'Movie preview generation attempt success'
						. ', file=' . $file->getPath()
						. ', time=' . $timeStamp
						. ', size=' . ($size ?? 'entire file'),
					['app' => 'core']
				);
				break;
			}

		}
		if ($result === null) {
			Server::get(LoggerInterface::class)->error(
				'Movie preview generation process failed'
					. ', file=' . $file->getPath(),
				['app' => 'core']
			);
		}
		return $result;
	}

	private function getSparseFile(File $file, int $size): string|false {
		// File is smaller than $size or file is larger than max int size
		// of the host so return false so getLocalFile method is used
		if (($size >= $file->getSize()) || ($file->getSize() > PHP_INT_MAX)) {
			return false;
		}
		$content = $file->fopen('r');

		// Stream does not support seeking so generating a sparse file is not possible.
		if (stream_get_meta_data($content)['seekable'] !== true) {
			fclose($content);
			return false;
		}

		$absPath = Server::get(ITempManager::class)->getTemporaryFile();
		if ($absPath === false) {
			Server::get(LoggerInterface::class)->error(
				'Failed to get temp file to create sparse file to generate thumbnail: ' . $file->getPath(),
				['app' => 'core']
			);
			fclose($content);
			return false;
		}
		$sparseFile = fopen($absPath, 'w');

		// Firsts 4 bytes indicate length of 1st atom.
		$ftypSize = (int)hexdec(bin2hex(stream_get_contents($content, 4, 0)));
		// Download next 4 bytes to find name of 1st atom.
		$ftypLabel = stream_get_contents($content, 4, 4);

		// MP4/MOVs all begin with the 'ftyp' atom. Anything else is not MP4/MOV
		// and therefore should be processed differently.
		if ($ftypLabel === 'ftyp') {
			// Set offset for 2nd atom. Atoms begin where the previous one ends.
			$offset = $ftypSize;
			$moovSize = 0;
			$moovOffset = 0;
			// Iterate and seek from atom to until the 'moov' atom is found or
			// EOF is reached
			while (($offset + 8 < $file->getSize()) && ($moovSize === 0)) {
				// First 4 bytes of atom header indicates size of the atom.
				$atomSize = (int)hexdec(bin2hex(stream_get_contents($content, 4, (int)$offset)));
				// Next 4 bytes of atom header is the name/label of the atom
				$atomLabel = stream_get_contents($content, 4, (int)($offset + 4));
				// Size value has two special values that don't directly indicate size
				// 0 = atom size equals the rest of the file
				if ($atomSize === 0) {
					$atomSize = $file->getsize() - $offset;
				} else {
					// 1 = read an additional 8 bytes after the label to get the 64 bit
					// size of the atom. Needed for large atoms like 'mdat' (the video data)
					if ($atomSize === 1) {
						$atomSize = (int)hexdec(bin2hex(stream_get_contents($content, 8, (int)($offset + 8))));
						// 0 in the 64 bit field should not occur in a valid file, stop processing
						if ($atomSize === 0) {
							return false;
						}
					}
				}
				// Found the 'moov' atom, store its location and size
				if ($atomLabel === 'moov') {
					$moovSize = $atomSize;
					$moovOffset = $offset;
					break;
				}
				$offset += $atomSize;
			}
			// 'moov' atom wasn't found or larger than $size
			// 'moov' atoms are generally small relative to video length.
			// Examples:
			// 4K HDR H265 60 FPS, 10 second video = 12.5 KB 'moov' atom, 54 MB total file size
			// 4K HDR H265 60 FPS, 5 minute video = 330 KB 'moov' atom, 1.95 GB total file size
			// Capping it at $size is a precaution against a corrupt/malicious 'moov' atom.
			// This effectively caps the total download size to 2x $size.
			// Also, if the 'moov' atom size+offset extends past EOF, it is invalid.
			if (($moovSize === 0) || ($moovSize > $size) || ($moovOffset + $moovSize > $file->getSize())) {
				fclose($content);
				fclose($sparseFile);
				return false;
			}
			// Generate new file of same size
			ftruncate($sparseFile, (int)($file->getSize()));
			fseek($sparseFile, 0);
			fseek($content, 0);
			// Copy first $size bytes of video into new file
			stream_copy_to_stream($content, $sparseFile, $size, 0);

			// If 'moov' is located entirely before $size in the video, it was already streamed,
			// so no need to download it again.
			if ($moovOffset + $moovSize >= $size) {
				// Seek to where 'moov' atom needs to be placed
				fseek($content, (int)$moovOffset);
				fseek($sparseFile, (int)$moovOffset);
				stream_copy_to_stream($content, $sparseFile, (int)$moovSize, 0);
			}
		} else {
			// 'ftyp' atom not found, not a valid MP4/MOV
			fclose($content);
			fclose($sparseFile);
			return false;
		}
		fclose($content);
		fclose($sparseFile);
		Server::get(LoggerInterface::class)->debug(
			'Sparse file being utilized for preview generation for ' . $file->getPath(),
			['app' => 'core']
		);
		return $absPath;
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

		if ($binaryType === 'ffmpeg') {
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

		Server::get(LoggerInterface::class)->debug(
			'Movie preview generation output'
				. ', file=' . $absPath
				. ', output=',
			['app' => 'core', 'output' => $output]
		);

		if ($returnCode === 0) {
			$image = new Image();
			$image->loadFromFile($tmpPath);
			if ($image->valid()) {
				unlink($tmpPath);
				$image->scaleDownToFit($maxX, $maxY);
				return $image;
			}
		}


		unlink($tmpPath);
		return null;
	}
}
