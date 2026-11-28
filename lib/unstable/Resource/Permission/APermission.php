<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Base class for the permissions of an app.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class APermission implements IPermission {
	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getHint(): ?string {
		return null;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	abstract public function getKindOf(): CorePermission;
}
