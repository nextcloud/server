<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Snowflake is a network service that generates unique ID numbers at a high scale
 * with simple guarantees.
 *
 * We use the following variation of it:
 *
 * - The first bit is an unused sign bit.
 * - The second part consists of a 42-bit timestamp (in milliseconds) representing
 *   the offset of the current time relative to a certain reference time. This is 84
 *   years.
 * - The third and fourth parts are represented by 5 bits each, indicating the data
 *   centerID and workerID. The maximum value for both is 31 (2^5 -1). At the moment the
 *   data centerID is randomly generated, the workerID is generated from the hostname.
 * - The fifth part is only one bit and is set if the snowflake id was generated from
 *   the CLI and used a different APCu pool.
 * - The last part consists of 10 bits, which represents the length of the serial number
 *   generated per millisecond per working node. A maximum of 1023 IDs can be generated
 *   in the same millisecond (2^10 -1).
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
interface ISnowflake {
	/**
	 * Get a snowflake id. Each call to this method is guaranteed to return a different id.
	 *
	 * @return string The snowflake id.
	 */
	public function nextId(): string;
}
