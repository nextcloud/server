<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\AbstractProvider;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory as L10NFactory;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

abstract class AbstractNotificationProviderTest extends TestCase {

	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
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

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
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
