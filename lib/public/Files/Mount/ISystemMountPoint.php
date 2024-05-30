<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Mount;

/**
 * Mark a mountpoint as containing system data, meaning that the data is not user specific
 *
 * Example use case is signaling to the encryption wrapper that system-wide keys should be used for a mountpoint
 *
 * @since 25.0.0
 */
interface ISystemMountPoint extends IMountPoint {
}
