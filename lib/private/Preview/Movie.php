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
			// try downloading 5 MB first as it's likely that the first frames are present there
			// in some cases this doesn't work for example when the moov atom is at the
			// end of the file, so if it fails we fall back to getting the full file
			$sizeAttempts = [5242880, null];
		} else {
			// size is irrelevant, only attempt once
			$sizeAttempts = [null];
		}

		foreach ($sizeAttempts as $size) {
			$absPath = $this->getLocalFile($file, $size);

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
	
		$cmd = $this->buildCommand($binaryType, $absPath, $second, $tmpPath);
		if ($cmd === null) {
			unlink($tmpPath);
			return null;
		}
	
		$timeout = 10; // set ffmpeg timeout
		[$returnCode, $output] = $this->executeCommand($cmd, $timeout);
	
		$image = $this->processThumbnailResult($returnCode, $tmpPath, $maxX, $maxY);
		if ($image === null && $second === 0) {
			$logger = \OC::$server->get(LoggerInterface::class);
			$logger->info('Movie preview generation failed. Output: {output}', ['app' => 'core','output' => $output]);
		}
	
		if (file_exists($tmpPath)) {
			unlink($tmpPath);
		}
	
		return $image;
	}
	
	private function buildCommand(string $binaryType, string $absPath, int $second, string $tmpPath): ?array {
		if ($binaryType === 'avconv') {
			return [
				$this->binary,'-y', '-ss', (string)$second, '-i',  $absPath, '-an',
				'-f', 'mjpeg', '-vframes', '1', '-vsync', '1', $tmpPath
			];
		} elseif ($binaryType === 'ffmpeg') {
			return [
				$this->binary,  '-y', '-ss', (string)$second, 
				'-i', $absPath, '-f', 'mjpeg', '-vframes', '1', $tmpPath
			];
		}
		return null;
	}

	private function executeCommand(array $cmd, int $timeout): array {
		$descriptorspec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];
	
		$proc = proc_open($cmd, $descriptorspec, $pipes);
		$returnCode = -1;
		$output = '';
	
		if (is_resource($proc)) {
			$startTime = time();
			$running = true;
	
			while ($running) {
				$status = proc_get_status($proc);
				if (!$status['running']) {
					$running = false;
					break;
				}
	
				if ((time() - $startTime) > $timeout) {
					proc_terminate($proc);
					usleep(500000);
					$status = proc_get_status($proc);
					if ($status['running']) {
						proc_terminate($proc, 9); 
					}
					$running = false;
					break;
				}
	
				usleep(100000); 
			}
	
			$stdout = isset($pipes[1]) ? trim(stream_get_contents($pipes[1])) : '';
			if (isset($pipes[1])) fclose($pipes[1]);
	
			$stderr = isset($pipes[2]) ? trim(stream_get_contents($pipes[2])) : '';
			if (isset($pipes[2])) fclose($pipes[2]);
	
			$returnCode = isset($status['exitcode']) && $status['exitcode'] !== -1
				? $status['exitcode']
				: proc_close($proc);
	
			$output = $stdout . $stderr;
		}
	
		return [$returnCode, $output];
	}

	private function processThumbnailResult(int $returnCode, string $tmpPath, int $maxX, int $maxY): ?\OCP\IImage {
		if ($returnCode === 0 && file_exists($tmpPath)) {
			$image = new \OCP\Image();
			$image->loadFromFile($tmpPath);
			if ($image->valid()) {
				$image->scaleDownToFit($maxX, $maxY);
				return $image;
			}
		}
		return null;
	}
	
}
