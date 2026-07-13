<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\CalDAV;

use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Collection;

class PublicCalendarRoot extends Collection {
	public function __construct(
		protected CalDavBackend $caldavBackend,
		protected IL10N $l10n,
		protected IAppConfig $appConfig,
		protected IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'public-calendars';
	}

	#[\Override]
	public function getChild($name): PublicCalendar {
		// Sharing via link is allowed by default, but if the option is set it should be checked.
		if (!$this->appConfig->getValueBool('core', 'shareapi_allow_links', true)) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}
		$calendar = $this->caldavBackend->getPublicCalendar($name);
		return new PublicCalendar($this->caldavBackend, $calendar, $this->l10n, $this->config, $this->logger);
	}

	#[\Override]
	public function getChildren(): array {
		return [];
	}
}
