<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * Nextcloud ID generator
 *
 * Generates unique ID
 * @since 33.0.0
 */
interface ISnowflakeIdGenerator {
	public function __invoke(): int|float;
}
