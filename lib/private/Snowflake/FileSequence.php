<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

use OCP\ITempManager;

class FileSequence implements ISequence {
	/** Number of files to use */
	private const NB_FILES = 20;
	/** Lock filename format **/
	private const LOCK_FILE_FORMAT = 'seq-%03d.lock';
	/** Delete sequences after SEQUENCE_TTL seconds **/
	private const SEQUENCE_TTL = 30;

	public function __construct(
		private readonly ITempManager $tempManager,
	) {
	}

	public function isAvailable(): bool {
		return true;
	}

	public function nextId(int $serverId, int $seconds, int $milliseconds): int {
		// Open lock file
		$filePath = $this->getFilePath($milliseconds % self::NB_FILES);
		$fp = fopen($filePath, 'cb+');
		if (!flock($fp, LOCK_EX)) {
			throw new \Exception('Unable to acquire lock on sequence id file: ' . $filePath);
		}

		// Read content
		$content = (string)fgets($fp);
		$locks = $content === ''
			? []
			: json_decode($content, true, 3, JSON_THROW_ON_ERROR);

		// Generate new ID
		$paddedMs = str_pad((string)$milliseconds, 3, '0');
		if (isset($locks[$seconds])) {
			if (isset($locks[$seconds][$paddedMs])) {
				++$locks[$seconds][$paddedMs];
			} else {
				$locks[$seconds][$paddedMs] = 0;
			}
		} else {
			$locks[$seconds] = [
				$paddedMs => 0
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

		return $locks[$seconds][$paddedMs];
	}

	private function getFilePath(int $fileId): string {
		return $this->tempManager->getTemporaryFolder('.snowflakes') . sprintf(self::LOCK_FILE_FORMAT, $fileId);
	}
}
