<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

use OCP\ITempManager;
use Override;

class FileSequence implements ISequence {
	/** Number of files to use */
	private const NB_FILES = 20;
	/** Lock file directory **/
	public const LOCK_FILE_DIRECTORY = 'sfi_file_sequence';
	/** Lock filename format **/
	private const LOCK_FILE_FORMAT = 'seq-%03d.lock';
	/** Delete sequences after SEQUENCE_TTL seconds **/
	private const SEQUENCE_TTL = 30;

	private string $workDir;

	public function __construct(
		ITempManager $tempManager,
	) {
		$this->workDir = $tempManager->getTempBaseDir() . '/' . self::LOCK_FILE_DIRECTORY;
		$this->ensureWorkdirExists();
	}

	private function ensureWorkdirExists(): void {
		if (is_dir($this->workDir)) {
			return;
		}

		if (@mkdir($this->workDir, 0700)) {
			return;
		}

		// Maybe the directory was created in the meantime
		if (is_dir($this->workDir)) {
			return;
		}

		throw new \Exception('Fail to create file sequence directory');
	}

	#[Override]
	public function isAvailable(): bool {
		return true;
	}

	#[Override]
	public function nextId(int $serverId, int $seconds, int $milliseconds): int {
		// Open lock file
		$filePath = $this->getFilePath($milliseconds % self::NB_FILES);
		$fp = fopen($filePath, 'c+');
		if ($fp === false) {
			throw new \Exception('Unable to open sequence ID file: ' . $filePath);
		}
		if (!flock($fp, LOCK_EX)) {
			throw new \Exception('Unable to acquire lock on sequence ID file: ' . $filePath);
		}

		// Read content
		$content = (string)fgets($fp);
		$locks = $content === ''
			? []
			: json_decode($content, true, 3, JSON_THROW_ON_ERROR);

		// Generate new ID
		if (isset($locks[$seconds])) {
			if (isset($locks[$seconds][$milliseconds])) {
				++$locks[$seconds][$milliseconds];
			} else {
				$locks[$seconds][$milliseconds] = 0;
			}
		} else {
			$locks[$seconds] = [
				$milliseconds => 0
			];
		}

		// Clean old sequence IDs
		$cleanBefore = $seconds - self::SEQUENCE_TTL;
		$locks = array_filter($locks, static function ($key) use ($cleanBefore) {
			return $key >= $cleanBefore;
		}, ARRAY_FILTER_USE_KEY);

		// Write data
		ftruncate($fp, 0);
		$content = json_encode($locks, JSON_THROW_ON_ERROR);
		rewind($fp);
		fwrite($fp, $content);
		fsync($fp);

		// Release lock
		fclose($fp);

		return $locks[$seconds][$milliseconds];
	}

	private function getFilePath(int $fileId): string {
		return $this->workDir . sprintf(self::LOCK_FILE_FORMAT, $fileId);
	}
}
