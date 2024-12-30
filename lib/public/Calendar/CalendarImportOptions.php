<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * Calendar Import Options
 *
 * @since 32.0.0
 */
class CalendarImportOptions {

	public string $format = 'ical';
	public bool $supersede = false;
	public bool $emitITip = false;
	public int $errors = 1; // 0 - continue, 1 - fail
	public int $validate = 1; // 0 - no validation, 1 - validate and skip on issue, 2 - validate and fail on issue

}
