<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * The status of a calendar event.
 *
 * @since 32.0.0
 */
enum CalendarEventStatus: string {
	case TENTATIVE = 'TENTATIVE';
	case CONFIRMED = 'CONFIRMED';
	case CANCELLED = 'CANCELLED';
};
