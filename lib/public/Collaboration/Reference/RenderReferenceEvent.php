<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Reference;

use OCP\EventDispatcher\Event;

/**
 * Event that apps can emit on their page rendering to trigger loading of aditional
 * scripts for reference widget rendering
 *
 * @since 25.0.0
 */
class RenderReferenceEvent extends Event {
}
