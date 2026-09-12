<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use OCP\AppFramework\Attribute\Implementable;

/**
 * For actors whose identifier is a local account, as resolved by
 * `IUserManager::get()`.
 *
 * Narrow to this before resolving an actor against group membership,
 * administrator status, quota or any other account property.
 *
 * Originating on this instance is not sufficient: a bot or a guest participant
 * may be local and still have no account. Nor is the identifier's shape a
 * reliable test — user ids may contain `@`, so a local id can be
 * indistinguishable by value from a federated one.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ILocalAccountActor extends IIdentifiableActor {
}
