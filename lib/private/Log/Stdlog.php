<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Log;

use OC\SystemConfig;
use OCP\Log\IWriter;

class Stdlog extends LogDetails implements IWriter {
	public function __construct(
		SystemConfig $config,
		protected string $tag = 'nextcloud',
	) {
		parent::__construct($config);
	}

	/**
	 * Write a message in the log
	 *
	 * @param string|array $message
	 */
	public function write(string $app, $message, int $level): void {
	    $detailsJson = $this->logDetailsAsJSON($app, $message, $level);
	    $details = json_decode($detailsJson, true);

	    if (json_last_error() !== JSON_ERROR_NONE || !is_array($details)) {
		return;
	    }

	    $logEntry = array_merge([
		'tag' => $this->tag,
		'app' => $app,
		'level' => $level,
	    ], $details);
	    // Check if 'message' field exists and is a string
	    if (isset($logEntry['message']) && is_string($logEntry['message'])) {
		$msg = $logEntry['message'];

		if (strlen($msg) > 0 && $msg[0] === '{') {
		    // Try decoding JSON
		    $decoded = json_decode($msg, true);

		    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
			// Remove original 'message' field
			unset($logEntry['message']);

			// Flatten decoded JSON into top-level logEntry
			// This will overwrite existing keys if there are
			// conflicts
			$logEntry = array_merge($logEntry, $decoded);
		    }
		}
	    }

	    $json = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	    if ($json !== false) {
		file_put_contents('php://stderr', $json . PHP_EOL);
	    }
	}
}
