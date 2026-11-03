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
 */
#[Implementable(since: '14.0.0')]
interface IAddToGroupBackend {
	/**
	 * @since 14.0.0
	 */
	public function addToGroup(string $uid, string $gid): bool;
}
