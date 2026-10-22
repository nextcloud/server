<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Resource\IGovernanceResourceManager;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Backend implemented by the Governance app.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IGovernanceBackend {

	/**
	 * @experimental 36.0.0
	 */
	public function getRegistry(): IGovernanceRegistry;

	/**
	 * @experimental 36.0.0
	 */
	public function getLabelManager(): IGovernanceLabelManager;

	/**
	 * @experimental 36.0.0
	 */
	public function getResourceManager(): IGovernanceResourceManager;
}
