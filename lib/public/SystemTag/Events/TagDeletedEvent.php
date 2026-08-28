<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\SystemTag\Events;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Event triggered when deleting a new tag.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class TagDeletedEvent extends AbstractTagEvent {
}
