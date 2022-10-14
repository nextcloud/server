<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2022 Informatyka Boguslawski sp. z o.o. sp.k., http://www.ib.pl/
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV;

use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\Forbidden;

class PublicCalendarRoot extends Collection {

	/** @var CalDavBackend */
	protected $caldavBackend;

	/** @var \OCP\IL10N */
	protected $l10n;

	/** @var \OCP\IConfig */
	protected $config;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * PublicCalendarRoot constructor.
	 *
	 * @param CalDavBackend $caldavBackend
	 * @param IL10N $l10n
	 * @param IConfig $config
	 */
	public function __construct(CalDavBackend $caldavBackend, IL10N $l10n,
						 IConfig $config, LoggerInterface $logger) {
		$this->caldavBackend = $caldavBackend;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->logger = $logger;
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
		// Sharing via link is allowed by default, but if the option is set it should be checked.
		if ($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'no' ) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}
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
