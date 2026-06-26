<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Mount;

/**
 * Denotes that shares created under this mountpoint will be manageable by everyone with share permission.
 *
 * @since 31.0.0
 */
interface IShareOwnerlessMount {
}
