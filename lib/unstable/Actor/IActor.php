<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Transports who is asking, at the point an API or function call is made, so
 * the callee can adjust its answer and behavior.
 *
 * Consumers SHOULD narrow on the sub-interfaces {@see IIdentifiableActor},
 * {@see ILocalAccountActor}, {@see ISystemActor}, each answering one question
 * about the actor, and not on a concrete implementation. An implementation
 * may therefore be added without existing call sites changing, provided it
 * declares the sub-interfaces it satisfies. Only in cases where a specific
 * actor is wanted, apps MAY exclude all other implementations by referring to
 * a specific actor class.
 *
 * New implementations MUST NOT introduce actor types which are deducible in
 * type and location by a combination of the existing interfaces, or which
 * represent a property resolvable from {@see IIdentifiableActor::getId()},
 * such as administrator status or an app-specific role.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IActor {
	/**
	 * Stable identifier for this kind of actor, for persisting a reference to
	 * an actor or configuration keyed by actor kind.
	 *
	 * Declared per implementation, never derived from the class name and never
	 * a fully qualified class name. Core uses bare words (`user`, `system`); an
	 * app prefixes with its app id (`myapp_bot`).
	 *
	 * Persist together with {@see IIdentifiableActor::getId()}: an identifier is
	 * unique within its kind, not across kinds.
	 *
	 * @experimental 35.0.0
	 */
	public function getTypeIdentifier(): string;
}
