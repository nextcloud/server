<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Event;

use OCP\EventDispatcher\Event;

/**
 * Kept so that an app still constructing or dispatching it does not fail.
 * Nothing dispatches it any more: the viewer is on every page already, see
 * \OC\Template\LoadViewerListener.
 *
 * @since 17.0.0
 * @deprecated 36.0.0 Dispatching it does nothing. Register your handler with the `@nextcloud/viewer` package and load it with `\OCP\Util::addInitScript()` instead. It will be removed in Nextcloud 39.
 */
final class LoadViewer extends Event {
}
