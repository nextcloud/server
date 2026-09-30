<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

use OC_Util;
use OCP\ITempManager;
use Override;

class FileSequence implements ISequence {
	/** Number of files to use */
	private const int NB_FILES = 20;
	/** Lock file directory **/
	public const LOCK_FILE_DIRECTORY = 'sfi_file_sequence';
	/** Lock filename format **/
	private const string LOCK_FILE_FORMAT = 'seq-%03d.slots';
	/** Reuse the slot of a sequence after SEQUENCE_TTL seconds **/
	private const int SEQUENCE_TTL = 30;
	/** Size of a slot: the seconds and the last sequence ID, both as unsigned 32-bit integers **/
	private const int SLOT_SIZE = 8;

	private string $workDir;

	public function __construct(
		ITempManager $tempManager,
	) {
		$this->workDir = $tempManager->getTempBaseDir() . '/' . self::LOCK_FILE_DIRECTORY . '_' . OC_Util::getInstanceId();
		$this->ensureWorkdirExists();
	}

	private function ensureWorkdirExists(): void {
		if (is_dir($this->workDir)) {
			if (!is_writable($this->workDir)) {
				throw new \Exception('File sequence directory exists but is not writable');
			}
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

		// Each file holds one slot per second of the TTL window and per millisecond mapped to it
		$slot = (($seconds % self::SEQUENCE_TTL + self::SEQUENCE_TTL) % self::SEQUENCE_TTL) * intdiv(1000, self::NB_FILES)
			+ intdiv($milliseconds, self::NB_FILES);
		$slotSecondsKey = $seconds & 0xFFFFFFFF;
		fseek($fp, $slot * self::SLOT_SIZE);
		$data = fread($fp, self::SLOT_SIZE);
		$sequenceId = 0;
		if (is_string($data) && strlen($data) === self::SLOT_SIZE) {
			['seconds' => $slotSeconds, 'sequence' => $slotSequenceId] = unpack('Nseconds/Nsequence', $data);
			if ($slotSeconds === $slotSecondsKey) {
				$sequenceId = $slotSequenceId + 1;
			}
		}

		// No fsync needed, the file only coordinates processes running at the same time
		fseek($fp, $slot * self::SLOT_SIZE);
		fwrite($fp, pack('NN', $slotSecondsKey, $sequenceId));

		// Release lock
		fclose($fp);

		return $sequenceId;
	}

	private function getFilePath(int $fileId): string {
		return $this->workDir . '/' . sprintf(self::LOCK_FILE_FORMAT, $fileId);
	}
}
