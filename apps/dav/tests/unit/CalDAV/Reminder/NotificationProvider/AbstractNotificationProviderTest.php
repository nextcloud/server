<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Thomas Citharel
 *
 * @author Thomas Citharel <tcit@tcit.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\AbstractProvider;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10NFactory;
use OCP\IUser;
use Test\TestCase;
use Sabre\VObject\Component\VCalendar;

abstract class AbstractNotificationProviderTest extends TestCase {

	/** @var ILogger|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;

	/** @var L10NFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $l10nFactory;

	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	protected $l10n;

	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $urlGenerator;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var AbstractProvider|\PHPUnit\Framework\MockObject\MockObject */
	protected $provider;

	/**
	 * @var VCalendar
	 */
	protected $vcalendar;

	/**
	 * @var string
	 */
	protected $calendarDisplayName;

	/**
	 * @var IUser|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $user;

	public function setUp() {
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->l10nFactory = $this->createMock(L10NFactory::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);

		$this->vcalendar = new VCalendar();
		$this->vcalendar->add('VEVENT', [
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2017-01-01 00:00:00+00:00'), // 1483228800,
			'UID' => 'uid1234',
		]);
		$this->calendarDisplayName = 'Personal';

		$this->user = $this->createMock(IUser::class);
	}
}
