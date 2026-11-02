<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

/**
 * @psalm-type PrincipalData = array{
 *     type: non-empty-string,
 *     id: non-empty-string,
 *     origin: ?non-empty-string,
 *     source: ?non-empty-string
 * }
 *
 * @psalm-type ActorData = array{
 *     principal: PrincipalData,
 *     delegate: ?array<string, mixed>,
 *     via: ?value-of<DelegationKind>
 * }
 *
 * @experimental 36.0.0
 */
final class DataTypes {
}
