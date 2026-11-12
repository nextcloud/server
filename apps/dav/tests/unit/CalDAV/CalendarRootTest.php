<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\CalendarRoot;
use OCA\DAV\CalDAV\Federation\FederatedCalendarFactory;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;
use Test\TestCase;

class CalendarRootTest extends TestCase {
	private CalendarRoot $calendarRoot;

	protected function setUp(): void {
		parent::setUp();

		$this->calendarRoot = new CalendarRoot(
			$this->createMock(BackendInterface::class),
			$this->createMock(CalDavBackend::class),
			'principals/users',
			$this->createMock(LoggerInterface::class),
			$this->createMock(IL10N::class),
			$this->createMock(IConfig::class),
			$this->createMock(FederatedCalendarFactory::class),
		);
	}

	private function returnsCachedSubscriptions(string $principalUri): bool {
		$calendarHome = $this->calendarRoot->getChildForPrincipal(['uri' => $principalUri]);
		$this->assertInstanceOf(CalendarHome::class, $calendarHome);
		return $this->invokePrivate($calendarHome, 'returnCachedSubscriptions');
	}

	public function testDoesNotReturnCachedSubscriptionsByDefault(): void {
		$this->assertFalse($this->returnsCachedSubscriptions('principals/users/alice'));
	}

	public function testEnableReturnCachedSubscriptions(): void {
		$this->calendarRoot->enableReturnCachedSubscriptions('alice');

		$this->assertTrue($this->returnsCachedSubscriptions('principals/users/alice'));
		$this->assertFalse($this->returnsCachedSubscriptions('principals/users/bob'));
	}

	public function testSetReturnCachedSubscriptionsForAll(): void {
		$this->calendarRoot->setReturnCachedSubscriptionsForAll(true);
		$this->assertTrue($this->returnsCachedSubscriptions('principals/users/alice'));
		$this->assertTrue($this->returnsCachedSubscriptions('principals/users/bob'));

		$this->calendarRoot->setReturnCachedSubscriptionsForAll(false);
		$this->assertFalse($this->returnsCachedSubscriptions('principals/users/bob'));
	}
}
