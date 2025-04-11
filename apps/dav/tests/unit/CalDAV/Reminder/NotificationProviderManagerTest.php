<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProviderManager;
use OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException;
use OCA\DAV\Capabilities;
use OCP\AppFramework\QueryException;
use Test\TestCase;

/**
 * @group DB
 */
class NotificationProviderManagerTest extends TestCase {

	/** @var NotificationProviderManager|\PHPUnit\Framework\MockObject\MockObject */
	private $providerManager;

	/**
	 * @throws QueryException
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->providerManager = new NotificationProviderManager();
		$this->providerManager->registerProvider(EmailProvider::class);
	}

	/**
	 * @throws ProviderNotAvailableException
	 * @throws NotificationTypeDoesNotExistException
	 */
	public function testGetProviderForUnknownType(): void {
		$this->expectException(NotificationTypeDoesNotExistException::class);
		$this->expectExceptionMessage('Type NOT EXISTENT is not an accepted type of notification');

		$this->providerManager->getProvider('NOT EXISTENT');
	}

	/**
	 * @throws NotificationTypeDoesNotExistException
	 * @throws ProviderNotAvailableException
	 */
	public function testGetProviderForUnRegisteredType(): void {
		$this->expectException(ProviderNotAvailableException::class);
		$this->expectExceptionMessage('No notification provider for type AUDIO available');

		$this->providerManager->getProvider('AUDIO');
	}

	public function testGetProvider(): void {
		$provider = $this->providerManager->getProvider('EMAIL');
		$this->assertInstanceOf(EmailProvider::class, $provider);
	}

	public function testRegisterProvider(): void {
		$this->providerManager->registerProvider(PushProvider::class);
		$provider = $this->providerManager->getProvider('DISPLAY');
		$this->assertInstanceOf(PushProvider::class, $provider);
	}

	/**
	 * @throws QueryException
	 */
	public function testRegisterBadProvider(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid notification provider registered');

		$this->providerManager->registerProvider(Capabilities::class);
	}

	public function testHasProvider(): void {
		$this->assertTrue($this->providerManager->hasProvider('EMAIL'));
		$this->assertFalse($this->providerManager->hasProvider('EMAIL123'));
	}
}
