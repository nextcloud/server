<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type;

use NCU\Governance\ResourceCapability\Parameter\ICapabilityParameterType;
use OCP\AppFramework\Attribute\Implementable;

/**
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IParameterisedCapability {

	/**
	 * @return list<ICapabilityParameterType>
	 * @experimental 36.0.0
	 */
	public function getParameters(): array;
}
