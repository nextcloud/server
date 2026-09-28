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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

abstract class AbstractNotificationProviderTestCase extends TestCase {
	protected LoggerInterface&MockObject $logger;
	protected L10NFactory&MockObject $l10nFactory;
	protected IL10N&MockObject $l10n;
	protected IURLGenerator&MockObject $urlGenerator;
	protected IConfig&MockObject $config;
	protected AbstractProvider $provider;
	protected VCalendar $vcalendar;
	protected string $calendarDisplayName;
	protected IUser&MockObject $user;

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
