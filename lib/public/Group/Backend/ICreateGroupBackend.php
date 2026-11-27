<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Group\Backend;

use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 14.0.0
 * @deprecated 30.0.0 Use ICreateNamedGroupBackend instead
 */
#[Implementable(since: '14.0.0')]
interface ICreateGroupBackend {
	/**
	 * @since 14.0.0
	 */
	public function createGroup(string $gid): bool;
}
