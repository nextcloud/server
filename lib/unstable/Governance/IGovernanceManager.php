<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Resource\IGovernanceResourceManager;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Class giving access to Governance services.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IGovernanceManager {

	/**
	 * Whether governance is available and enabled.
	 *
	 * @experimental 36.0.0
	 * @throws \RuntimeException if called during app registration.
	 */
	public function isAvailable(): bool;

	/**
	 * Registry to list and retrieve resources and their capabilities.
	 *
	 * @throws GovernanceUnavailableException when Governance is not available.
	 * @experimental 36.0.0
	 */
	public function getRegistry(): IGovernanceRegistry;

	/**
	 * A manager to list/add/change/remove labels from resources.
	 *
	 * @experimental 36.0.0
	 * @throws GovernanceUnavailableException when Governance is not available.
	 */
	public function getLabelManager(): IGovernanceLabelManager;

	/**
	 * A manager to query the capabilities for a specific resource.
	 *
	 * @experimental 36.0.0
	 * @throws GovernanceUnavailableException when Governance is not available.
	 */
	public function getResourceManager(): IGovernanceResourceManager;
}
