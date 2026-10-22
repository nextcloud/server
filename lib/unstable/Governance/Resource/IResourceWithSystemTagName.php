<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Resource;

use OCP\AppFramework\Attribute\Implementable;

/**
 * A resource that implements this, will get labels assigned as system tags
 * using the specified object type string, instead of one chosen by governance.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IResourceWithSystemTagName {
	/**
	 * @since 36.0.0
	 */
	public function getSystemTagObjectType(): string;
}
