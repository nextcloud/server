<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Events;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered right before the user management template is rendered.
 *
 * @since 20.0.0
 */
class BeforeTemplateRenderedEvent extends Event {
}
