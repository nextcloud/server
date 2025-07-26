<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Federation;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Interface for resolving federated cloud ids
 *
 * @since 32.0.0
 */
#[Consumable(since: '32.0.0')]
#[Implementable(since: '32.0.0')]
interface ICloudIdResolver {
	/**
	 * @param string $cloudId
	 * @return ICloudId
	 * @throws \InvalidArgumentException
	 *
	 * @since 32.0.0
	 */
	public function resolveCloudId(string $cloudId): ICloudId;

	/**
	 * Check if the input is a correctly formatted cloud id
	 *
	 * @param string $cloudId
	 * @return bool
	 *
	 * @since 32.0.0
	 */
	public function isValidCloudId(string $cloudId): bool;
}
