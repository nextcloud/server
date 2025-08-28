<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use OCA\DAV\AppInfo\Application;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory as IL10NFactory;
use Psr\Log\LoggerInterface;

class CalendarFactory {
	private readonly IL10N $l10n;

	public function __construct(
		private readonly CalDavBackend $calDavBackend,
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
		IL10NFactory $l10nFactory,
	) {
		$this->l10n = $l10nFactory->get(Application::APP_ID);
	}

	public function createCalendar(array $calendarInfo): Calendar {
		return new Calendar(
			$this->calDavBackend,
			$calendarInfo,
			$this->l10n,
			$this->config,
			$this->logger,
		);
	}

	public function createPublicCalendar(array $calendarInfo): PublicCalendar {
		return new PublicCalendar(
			$this->calDavBackend,
			$calendarInfo,
			$this->l10n,
			$this->config,
			$this->logger,
		);
	}
}
