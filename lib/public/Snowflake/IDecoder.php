<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Snowflake;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Nextcloud Snowflake ID decoder
 *
 * @see \OCP\Snowflake\IGenerator for format
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
interface IDecoder {
	/**
	 * Decode information contained into Snowflake ID
	 *
	 * It includes:
	 *  - server ID: identify server on which ID was generated
	 *  - sequence ID: sequence number (number of snowflakes generated in the same second)
	 *  - createdAt: timestamp at which ID was generated
	 *  - isCli: if ID was generated using CLI or not
	 *
	 * @return array{createdAt: \DateTimeImmutable, serverId: int<0,1023>, sequenceId: int<0,4095>, isCli: bool, seconds: positive-int, milliseconds: int<0,999>}
	 * @since 33.0
	 */
	public function decode(string $snowflakeId): array;
}
