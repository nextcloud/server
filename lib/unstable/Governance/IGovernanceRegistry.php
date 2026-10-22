<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Resource\IGovernanceResourceProvider;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Allows apps to list registered resources and their capabilities.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IGovernanceRegistry {
	/**
	 * Every registered resource provider, keyed by {@see IGovernanceResourceProvider::getIdentifier()}.
	 * A provider can be missing due to issues with registrations. Critical log entries will be logged in that case.
	 *
	 * @return array<non-empty-lowercase-string, IGovernanceResourceProvider>
	 * @experimental 36.0.0
	 */
	public function getResourceProviders(): array;

	/**
	 * Returns the resource provider registered with $identifier, null when the identifier was not registered.
	 *
	 * @experimental 36.0.0
	 */
	public function getResourceProvider(string $identifier): ?IGovernanceResourceProvider;
}
