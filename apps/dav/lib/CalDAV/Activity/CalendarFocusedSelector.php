<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Activity;

use OCP\Activity\IActivityFocusedSelector;
use OCP\DB\QueryBuilder\IQueryBuilder;

class CalendarFocusedSelector implements IActivityFocusedSelector {
	#[\Override]
	public function extendQuery(IQueryBuilder $query, string $userId): array {
		// Activity related to calendars owned by the user
		$query->leftJoin('a', 'calendars', 'calendars',
			$query->expr()->andX(
				$query->expr()->eq('a.object_type', $query->createNamedParameter('calendar')),
				$query->expr()->eq('a.object_id', 'calendars.id'),
			));
		return [
			$query->expr()->eq('calendars.principaluri', $query->createNamedParameter('principals/users/' . $userId)),
		];
	}
}
