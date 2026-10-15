<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Allow providers to specify a priority for selection when multiple providers can handle a share.
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
#[Implementable(since: '33.0.0')]
interface IPublicShareTemplateProviderWithPriority {
	/**
	 * Returns the priority of the provider. Lower values indicate higher priority.
	 *
	 * @since 33.0.0
	 */
	public function getPriority(): int;
}
