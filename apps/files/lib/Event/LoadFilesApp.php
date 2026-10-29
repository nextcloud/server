<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Event;

use OCP\EventDispatcher\Event;

/**
 * Dispatch this event from any page that needs to render the Files UI
 * (e.g. via `OCP.Files.renderFilesApp()`) but is not served by the Files
 * app's own controller. It triggers the same script/initial-state bootstrap
 * that `files.view.index` performs.
 */
class LoadFilesApp extends Event {
}
