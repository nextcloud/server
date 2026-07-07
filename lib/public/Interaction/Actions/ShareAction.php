<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Interaction\Actions;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Constants;
use OCP\Interaction\InteractionAction;

/**
 * Used when a user wants to share a resource to a receiver.
 *
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final readonly class ShareAction implements InteractionAction {
	/**
	 * @since 34.0.2
	 */
	public function __construct(
		/** @var ?int-mask-of<Constants::PERMISSION_*> */
		public ?int $filesSharingPermissions = null,
	) {
	}
}
