<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Reminder\NotificationProvider;

/**
 * Class AudioProvider
 *
 * This class only extends PushProvider at the moment. It does not provide true
 * audio-alarms yet, but it's better than no alarm at all right now.
 *
 * @package OCA\DAV\CalDAV\Reminder\NotificationProvider
 */
class AudioProvider extends PushProvider {

	/** @var string */
	public const NOTIFICATION_TYPE = 'AUDIO';
}
