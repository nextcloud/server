<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory as IL10NFactory;
use Psr\Log\LoggerInterface;

class FederatedCalendarFactory {
	private readonly IL10N $l10n;

	public function __construct(
		private readonly CalDavBackend $caldavBackend,
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		IL10NFactory $l10nFactory,
	) {
		$this->l10n = $l10nFactory->get(Application::APP_ID);
	}

	public function createFederatedCalendar(array $calendarInfo): FederatedCalendar {
		return new FederatedCalendar(
			$this->caldavBackend,
			$calendarInfo,
			$this->l10n,
			$this->config,
			$this->logger,
			$this->federatedCalendarMapper,
		);
	}
}
