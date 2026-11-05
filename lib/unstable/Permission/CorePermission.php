<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * IDs of the generic permissions registered by the server.
 *
 * Apps should reuse these instead of defining their own permission whenever the meaning is the same.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum CorePermission: string {
	/**
	 * @experimental 36.0.0
	 */
	case Read = 'core:read';

	/**
	 * @experimental 36.0.0
	 */
	case Update = 'core:update';

	/**
	 * @experimental 36.0.0
	 */
	case Create = 'core:create';

	/**
	 * @experimental 36.0.0
	 */
	case Delete = 'core:delete';

	/**
	 * @experimental 36.0.0
	 */
	case Share = 'core:share';

	/**
	 * @experimental 36.0.0
	 */
	case Download = 'core:download';
}
