<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Resource\IGovernanceResourceManager;

/**
 * Backend implemented by the Governance app.
 *
 * @experimental 36.0.0
 */
interface IGovernanceBackend {

	public function isAvailable(): bool;

	public function getRegistry(): IGovernanceRegistry;

	public function getLabelManager(): IGovernanceLabelManager;

	public function getResourceManager(): IGovernanceResourceManager;
}
