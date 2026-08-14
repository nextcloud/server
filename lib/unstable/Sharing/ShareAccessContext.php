<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing;

use NCU\Sharing\Property\ISharePropertyTypeFilter;
use NCU\Sharing\Recipient\IShareRecipientType;
use OCP\AppFramework\Attribute\Consumable;
use OCP\IUser;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class ShareAccessContext {
	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		public ?IUser $currentUser = null,
		public ?string $secret = null,
		/** @var array<class-string<IShareRecipientType|ISharePropertyTypeFilter>, mixed> $arguments */
		public array $arguments = [],
		/**
		 * Ignore all checks and allow any operation. Only use it for admins and occ.
		 */
		public bool $overrideChecks = false,
	) {
	}
}
