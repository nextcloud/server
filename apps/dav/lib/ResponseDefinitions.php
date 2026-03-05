<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV;

use OCA\DAV\CalDAV\UpcomingEvent;

/**
 * @psalm-type DAVOutOfOfficeDataCommon = array{
 *      userId: string,
 *      message: string,
 *      replacementUserId: ?string,
 *      replacementUserDisplayName: ?string,
 *  }
 *
 * @psalm-type DAVOutOfOfficeData = DAVOutOfOfficeDataCommon&array{
 *     id: int,
 *     firstDay: string,
 *     lastDay: string,
 *     status: string,
 * }
 *
 * @todo this is a copy of \OCP\User\IOutOfOfficeData
 * @psalm-type DAVCurrentOutOfOfficeData = DAVOutOfOfficeDataCommon&array{
 *     id: string,
 *     startDate: int,
 *     endDate: int,
 *     shortMessage: string,
 * }
 *
 * @see UpcomingEvent::jsonSerialize
 * @psalm-type DAVUpcomingEvent = array{
 *      uri: string,
 *      calendarUri: string,
 *      start: ?int,
 *      summary: ?string,
 *      location: ?string,
 *  }
 */
class ResponseDefinitions {
}
