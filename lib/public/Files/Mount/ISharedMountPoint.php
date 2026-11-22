<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Mount;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Mark the mount points as pointing to a shared storaged.
 *
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface ISharedMountPoint {

}
