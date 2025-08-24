<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Reference;

use OCP\EventDispatcher\Event;

/**
 * Event emitted when apps might render references like link previews or smart picker widgets.
 *
 * This can be used to inject scripts for extending that.
 * Further details can be found in the :ref:`Reference providers` deep dive.
 *
 * @since 25.0.0
 */
class RenderReferenceEvent extends Event {
}
