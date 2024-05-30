<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Resources;

use OCP\EventDispatcher\Event;

/**
 * This event is used by apps to register their own frontend scripts for integrating
 * projects in their app. Apps also need to dispatch the event in order to load
 * scripts during page load
 *
 * @see https://docs.nextcloud.com/server/latest/developer_manual/digging_deeper/projects.html
 * @since 25.0.0
 */
class LoadAdditionalScriptsEvent extends Event {
}
