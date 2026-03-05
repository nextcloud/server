<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

use Override;

class APCuSequence implements ISequence {
	#[Override]
	public function isAvailable(): bool {
		return PHP_SAPI !== 'cli' && function_exists('apcu_enabled') && apcu_enabled();
	}

	#[Override]
	public function nextId(int $serverId, int $seconds, int $milliseconds): int|false {
		if ((int)apcu_cache_info(true)['start_time'] === $seconds) {
			// APCu cache was just started
			// It means a sequence was maybe deleted
			return false;
		}

		$key = 'seq:' . $seconds . ':' . $milliseconds;
		$sequenceId = apcu_inc($key, ttl: 1);
		return $sequenceId === false ? throw new \Exception('Failed to generate SnowflakeId with APCu') : $sequenceId;
	}
}
