<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events\Node;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final class NodeUpdatedEvent extends AbstractNodeEvent {
}
