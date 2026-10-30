<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\App\Events;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered when all enabled apps are loaded and available.
 * So this can be used if your app needs to interact with other apps, which is not guaranteed to be available during the app bootstrapping phase.
 *
 * @since 35.0.0
 */
class AppsLoadedEvent extends Event {
}
