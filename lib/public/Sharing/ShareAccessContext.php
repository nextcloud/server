<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IUser;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Recipient\IShareRecipientType;

/**
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final readonly class ShareAccessContext {
	/**
	 * @throws ShareInvalidException
	 */
	public function __construct(
		public ?IUser $currentUser = null,
		/** @var array<class-string<IShareRecipientType|ISharePropertyTypeFilter>, mixed> $arguments */
		public array $arguments = [],
		/**
		 * Ignore all checks and allow any operation. Only use it for admins and occ.
		 */
		public bool $force = false,
	) {
	}
}
