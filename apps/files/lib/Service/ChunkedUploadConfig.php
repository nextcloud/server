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
	public static function getMaxChunkSize(): int {
		return Server::get(IConfig::class)->getSystemValueInt('files.chunked_upload.max_size', 100 * 1024 * 1024);
	}

	public static function getMaxParallelCount(): int {
		return Server::get(IConfig::class)->getSystemValueInt('files.chunked_upload.max_parallel_count', 5);
	}
}
