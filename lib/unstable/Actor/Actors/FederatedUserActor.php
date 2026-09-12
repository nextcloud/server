<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor\Actors;

use NCU\Actor\IIdentifiableActor;
use OCP\AppFramework\Attribute\Consumable;

/**
 * A user account on another server.
 *
 * Identifiable but not an {@see \NCU\Actor\ILocalAccountActor}: another server
 * is the authority behind the identifier, so it must not be resolved against
 * local accounts, groups or quotas.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class FederatedUserActor implements IIdentifiableActor {

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
		return 'federated_user';
	}
}
