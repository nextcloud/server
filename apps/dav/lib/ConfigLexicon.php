<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for files_sharing.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	public const RATE_LIMIT_PERIOD_SHARE_ADDRESSBOOK_OR_CALENDAR = 'rateLimitPeriodShareAddressbookOrCalendar';
	public const RATE_LIMIT_SHARE_ADDRESSBOOK_OR_CALENDAR = 'rateLimitShareAddressbookOrCalendar';
	public const ENABLE_CALENDAR_FEDERATION = 'enableCalendarFederation';
	public const SEND_INVITATIONS = 'sendInvitations';
	public const HIDE_ABSENCE_SETTINGS = 'hide_absence_settings';
	public const ALLOW_CALENDAR_LINK_SUBSCRIPTIONS = 'allow_calendar_link_subscriptions';
	public const DISABLE_FREE_BUSY = 'disableFreeBusy';
	public const SYSTEM_ADDRESSBOOK_EXPOSED = 'system_addressbook_exposed';

	#[\Override]
	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	#[\Override]
	public function getAppConfigs(): array {
		return [
			new Entry(
				self::RATE_LIMIT_PERIOD_SHARE_ADDRESSBOOK_OR_CALENDAR,
				ValueType::INT,
				3600,
				'The time window, in seconds, over which share requests for address books or calendars are counted for rate limiting',
				true,
			),
			new Entry(
				self::RATE_LIMIT_SHARE_ADDRESSBOOK_OR_CALENDAR,
				ValueType::INT,
				100,
				'The maximum number of address book or calendar share requests allowed per user within the configured rate limit period',
				true,
			),
			new Entry(
				self::ENABLE_CALENDAR_FEDERATION,
				ValueType::BOOL,
				defaultRaw: true,
				definition: 'Whether calendars can be shared with and received from other Nextcloud servers',
			),
			new Entry(
				self::SEND_INVITATIONS,
				ValueType::BOOL,
				defaultRaw: true,
				definition: 'Whether to send invitation emails for events with attendees',
			),
			new Entry(
				self::HIDE_ABSENCE_SETTINGS,
				ValueType::BOOL,
				defaultRaw: false,
				definition: 'Whether to hide the absence (out-of-office) settings from users',
			),
			new Entry(
				self::ALLOW_CALENDAR_LINK_SUBSCRIPTIONS,
				ValueType::BOOL,
				defaultRaw: true,
				definition: 'Whether users can subscribe to calendars by link',
			),
			new Entry(
				self::DISABLE_FREE_BUSY,
				ValueType::BOOL,
				defaultRaw: false,
				definition: 'Whether to disable the free/busy lookup on user outboxes',
			),
			new Entry(
				self::SYSTEM_ADDRESSBOOK_EXPOSED,
				ValueType::BOOL,
				defaultRaw: true,
				definition: 'Whether to not expose the system address book to users',
				lazy: true,
			),
		];
	}

	#[\Override]
	public function getUserConfigs(): array {
		return [];
	}
}
