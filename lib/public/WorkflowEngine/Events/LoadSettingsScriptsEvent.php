<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted when the workflow engine settings page is loaded.
 *
 * @since 20.0.0
 */
class LoadSettingsScriptsEvent extends Event {
}
