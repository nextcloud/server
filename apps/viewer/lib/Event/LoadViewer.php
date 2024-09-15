<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Viewer\Event;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered whenever the viewer is loaded and extensions should be loaded.
 *
 * @since 17.0.0
 * @psalm-immutable
 */
class LoadViewer extends Event {
}
