<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Activity\Setting;

use OCP\Activity\ActivitySettings;
use OCP\IL10N;

abstract class CalDAVSetting extends ActivitySettings {
	/** @var IL10N */
	protected $l;

	/**
	 * @param IL10N $l
	 */
	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	public function getGroupIdentifier() {
		return 'calendar';
	}

	public function getGroupName() {
		return $this->l->t('Calendar, contacts and tasks');
	}
}
