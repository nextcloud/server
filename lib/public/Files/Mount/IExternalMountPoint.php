<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Mount;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Mark the mount points as pointing to a remote server.
 *
 * These mount point might be temporarily un-available and need special handling.
 *
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface IExternalMountPoint {
}
