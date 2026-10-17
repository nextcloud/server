<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\Resource;

use NCU\Governance\Context\Context;
use OCP\AppFramework\Attribute\Consumable;

#[Consumable(since: '36.0.0')]
interface IGovernanceResourceManager {
	public function resolveCapabilities(Context $context, $resourceType, $resourceIds, $capabilityList): array;
}
