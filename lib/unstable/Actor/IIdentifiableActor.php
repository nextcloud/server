<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use OCP\AppFramework\Attribute\Implementable;

/**
 * For actors carrying an identifier, e.g. a user id or an email address.
 *
 * Narrow to this wherever an identifier is needed; actors without one like the
 * system, or an unauthenticated caller, do not satisfy it.
 *
 * The identifier is unique within the actor kind, not across kinds. Resolve it
 * only against the authority the actor's other interfaces imply, and persist it
 * together with {@see IActor::getTypeIdentifier()}.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IIdentifiableActor extends IActor {
	/**
	 * @experimental 35.0.0
	 */
	public function getId(): string;
}
