<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Events\Node;

use OCP\EventDispatcher\Event;

/**
 * Event fired after the filesystem has been torn down
 *
 * @since 24.0.0
 */
class FilesystemTornDownEvent extends Event {
}
