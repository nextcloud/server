<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Collection;

class PublicCalendarRoot extends Collection {

	/**
	 * PublicCalendarRoot constructor.
	 *
	 * @param CalDavBackend $caldavBackend
	 * @param IL10N $l10n
	 * @param IConfig $config
	 */
	public function __construct(
		protected CalDavBackend $caldavBackend,
		protected IL10N $l10n,
		protected IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'public-calendars';
	}

	/**
	 * @inheritdoc
	 */
	public function getChild($name) {
		$calendar = $this->caldavBackend->getPublicCalendar($name);
		return new PublicCalendar($this->caldavBackend, $calendar, $this->l10n, $this->config, $this->logger);
	}

	/**
	 * @inheritdoc
	 */
	public function getChildren() {
		return [];
	}
}
