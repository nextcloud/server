<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus;

/**
 * @psalm-type UserStatusClearAtTimeType = "day"|"week"
 *
 * @psalm-type UserStatusClearAt = array{
 *     type: "period"|"end-of",
 *     time: int|UserStatusClearAtTimeType,
 * }
 *
 * @psalm-type UserStatusPredefined = array{
 *     id: string,
 *     icon: string,
 *     message: string,
 *     clearAt: ?UserStatusClearAt,
 * }
 *
 * @psalm-type UserStatusType = "online"|"away"|"dnd"|"busy"|"offline"|"invisible"
 *
 * @psalm-type UserStatusPublic = array{
 *     userId: string,
 *     message: ?string,
 *     icon: ?string,
 *     clearAt: ?int,
 *     status: UserStatusType,
 * }
 *
 * @psalm-type UserStatusPrivate = UserStatusPublic&array{
 *     messageId: ?string,
 *     messageIsPredefined: bool,
 *     statusIsUserDefined: bool,
 * }
 */
class ResponseDefinitions {
}
