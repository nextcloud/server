<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Dispatching this event triggers a job to update cached rooms and resources of all backends.
 *
 * @since 30.0.0
 */
class ScheduleResourcesRoomsUpdateEvent extends Event {
}
