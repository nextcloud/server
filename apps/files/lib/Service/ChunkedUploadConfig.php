<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Service;

use OCP\IConfig;
use OCP\Server;

class ChunkedUploadConfig {
	private const KEY_MAX_SIZE = 'files.chunked_upload.max_size';
	private const KEY_MAX_PARALLEL_COUNT = 'files.chunked_upload.max_parallel_count';

	public static function getMaxChunkSize(): int {
		return Server::get(IConfig::class)->getSystemValueInt(self::KEY_MAX_SIZE, 100 * 1024 * 1024);
	}

	public static function setMaxChunkSize(int $maxChunkSize): void {
		Server::get(IConfig::class)->setSystemValue(self::KEY_MAX_SIZE, $maxChunkSize);
	}

	public static function getMaxParallelCount(): int {
		return Server::get(IConfig::class)->getSystemValueInt(self::KEY_MAX_PARALLEL_COUNT, 5);
	}
}
