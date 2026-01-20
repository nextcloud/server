<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Config;

/**
 * Marks a mount provider as being authoritative, meaning that it will proactively update the cached mounts
 *
 * @since 33.0.0
 */
interface IAuthoritativeMountProvider {

}
