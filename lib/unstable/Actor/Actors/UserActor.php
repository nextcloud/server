<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor\Actors;

use NCU\Actor\ILocalAccountActor;
use OCP\AppFramework\Attribute\Consumable;

/**
 * A user account on this instance. Its identifier is a uid.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class UserActor implements ILocalAccountActor {

	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		protected string $id,
	) {
	}

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getTypeIdentifier(): string {
		return 'user';
	}
}
