<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\FreeBusy;

use Sabre\VObject\Component\VCalendar;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FreeBusyGenerator extends \Sabre\VObject\FreeBusyGenerator {

	public function __construct() {
		parent::__construct();
	}

	public function getVCalendar(): VCalendar {
		return new VCalendar();
	}
}
