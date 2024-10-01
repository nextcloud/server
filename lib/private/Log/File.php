<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Log;

use OC\SystemConfig;
use OCP\ILogger;
use OCP\Log\IFileBased;
use OCP\Log\IWriter;

/**
 * logging utilities
 *
 * Log is saved at data/nextcloud.log (on default)
 */

class File extends LogDetails implements IWriter, IFileBased {
	protected string $logFile;

	protected int $logFileMode;

	public function __construct(
		string $path,
		string $fallbackPath,
		private SystemConfig $config,
	) {
		parent::__construct($config);
		$this->logFile = $path;
		if (!file_exists($this->logFile)) {
			if (
				(
					!is_writable(dirname($this->logFile))
					|| !touch($this->logFile)
				)
				&& $fallbackPath !== ''
			) {
				$this->logFile = $fallbackPath;
			}
		}
		$this->logFileMode = $config->getValue('logfilemode', 0640);
	}

	/**
	 * write a message in the log
	 * @param string|array $message
	 */
	public function write(string $app, $message, int $level): void {
		$entry = $this->logDetailsAsJSON($app, $message, $level);
		$handle = @fopen($this->logFile, 'a');
		if ($this->logFileMode > 0 && is_file($this->logFile) && (fileperms($this->logFile) & 0777) != $this->logFileMode) {
			@chmod($this->logFile, $this->logFileMode);
		}
		if ($handle) {
			fwrite($handle, $entry . "\n");
			fclose($handle);
		} else {
			// Fall back to error_log
			error_log($entry);
		}
		if (php_sapi_name() === 'cli-server') {
			if (!\is_string($message)) {
				$message = json_encode($message);
			}
			error_log($message, 4);
		}
	}

	/**
	 * get entries from the log in reverse chronological order
	 */
	public function getEntries(int $limit = 50, int $offset = 0): array {
		$minLevel = $this->config->getValue('loglevel', ILogger::WARN);
		$entries = [];
		$handle = @fopen($this->logFile, 'rb');
		if ($handle) {
			fseek($handle, 0, SEEK_END);
			$pos = ftell($handle);
			$line = '';
			$entriesCount = 0;
			$lines = 0;
			// Loop through each character of the file looking for new lines
			while ($pos >= 0 && ($limit === null || $entriesCount < $limit)) {
				fseek($handle, $pos);
				$ch = fgetc($handle);
				if ($ch == "\n" || $pos == 0) {
					if ($line != '') {
						// Add the first character if at the start of the file,
						// because it doesn't hit the else in the loop
						if ($pos == 0) {
							$line = $ch . $line;
						}
						$entry = json_decode($line);
						// Add the line as an entry if it is passed the offset and is equal or above the log level
						if ($entry->level >= $minLevel) {
							$lines++;
							if ($lines > $offset) {
								$entries[] = $entry;
								$entriesCount++;
							}
						}
						$line = '';
					}
				} else {
					$line = $ch . $line;
				}
				$pos--;
			}
			fclose($handle);
		}
		return $entries;
	}

	public function getLogFilePath():string {
		return $this->logFile;
	}
}
