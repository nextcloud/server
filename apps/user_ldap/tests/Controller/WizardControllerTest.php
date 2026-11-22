<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Tests\Controller;

use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Controller\WizardController;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\WizardFactory;
use OCP\AppFramework\OCS\OCSException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class WizardControllerTest extends TestCase {
	private GroupMapping&MockObject $groupMapping;
	private IEventDispatcher&MockObject $eventDispatcher;
	private WizardController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->groupMapping = $this->createMock(GroupMapping::class);
		$this->overwriteService(GroupMapping::class, $this->groupMapping);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->controller = new WizardController(
			'user_ldap',
			$this->createMock(IRequest::class),
			$this->createMock(LoggerInterface::class),
			$this->createMock(ConnectionFactory::class),
			$this->createMock(IL10N::class),
			$this->createMock(WizardFactory::class),
			$this->eventDispatcher,
		);
	}

	protected function tearDown(): void {
		$this->restoreService(GroupMapping::class);
		parent::tearDown();
	}

	public function testClearGroupMappings(): void {
		$this->groupMapping->expects($this->once())
			->method('clear')
			->willReturn(true);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === 'user_ldap.mapping.cleared'
				&& $event->getLogMessage() === 'LDAP %s mappings were cleared'
				&& $event->getParameters() === ['subject' => 'group']));

		$this->controller->clearMappings('group');
	}

	public function testClearGroupMappingsFailed(): void {
		$this->groupMapping->expects($this->once())
			->method('clear')
			->willReturn(false);

		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->expectException(OCSException::class);
		$this->controller->clearMappings('group');
	}
}
