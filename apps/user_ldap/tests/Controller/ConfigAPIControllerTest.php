<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Tests\Controller;

use OCA\User_LDAP\Connection;
use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Controller\ConfigAPIController;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ConfigAPIControllerTest extends TestCase {
	private const CONFIG_ID = 'sAuditTest';
	private const NEW_CONFIG_ID = 'sAuditNew';

	private Helper&MockObject $helper;
	private ConnectionFactory&MockObject $connectionFactory;
	private IEventDispatcher&MockObject $eventDispatcher;
	private ConfigAPIController $controller;
	/** @var list<CriticalActionPerformedEvent> */
	private array $auditEvents = [];

	protected function setUp(): void {
		parent::setUp();

		$this->helper = $this->createMock(Helper::class);
		$this->helper->method('getServerConfigurationPrefixes')
			->willReturn([self::CONFIG_ID]);
		$this->connectionFactory = $this->createMock(ConnectionFactory::class);
		$this->connectionFactory->method('get')
			->willReturn($this->getMockBuilder(Connection::class)
				->setConstructorArgs([$this->createMock(ILDAPWrapper::class)])
				->getMock());
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->eventDispatcher->method('dispatchTyped')
			->willReturnCallback(function (Event $event): void {
				if ($event instanceof CriticalActionPerformedEvent) {
					$this->auditEvents[] = $event;
				}
			});

		$this->controller = new ConfigAPIController(
			'user_ldap',
			$this->createMock(IRequest::class),
			$this->helper,
			$this->createMock(LoggerInterface::class),
			$this->connectionFactory,
			$this->createMock(IL10N::class),
			$this->eventDispatcher,
		);
	}

	protected function tearDown(): void {
		$config = Server::get(IConfig::class);
		foreach ($config->getAppKeys('user_ldap') as $key) {
			if (str_starts_with($key, self::CONFIG_ID) || str_starts_with($key, self::NEW_CONFIG_ID)) {
				$config->deleteAppValue('user_ldap', $key);
			}
		}
		parent::tearDown();
	}

	private function assertSingleAuditEvent(string $operation, string $message, array $parameters): void {
		$this->assertCount(1, $this->auditEvents);
		$this->assertSame($operation, $this->auditEvents[0]->getOperation());
		$this->assertSame($message, $this->auditEvents[0]->getLogMessage());
		$this->assertSame($parameters, $this->auditEvents[0]->getParameters());
	}

	public function testCreate(): void {
		$this->helper->method('getNextServerConfigurationPrefix')
			->willReturn(self::NEW_CONFIG_ID);

		$this->controller->create();

		$this->assertSingleAuditEvent(
			'user_ldap.config.created',
			'LDAP configuration "%s" was created',
			['configId' => self::NEW_CONFIG_ID],
		);
	}

	public function testDelete(): void {
		$this->helper->expects($this->once())
			->method('deleteServerConfiguration')
			->with(self::CONFIG_ID)
			->willReturn(true);

		$this->controller->delete(self::CONFIG_ID);

		$this->assertSingleAuditEvent(
			'user_ldap.config.deleted',
			'LDAP configuration "%s" was deleted',
			['configId' => self::CONFIG_ID],
		);
	}

	public function testModifyLogsChangedKeysOnly(): void {
		$this->controller->modify(self::CONFIG_ID, [
			'ldapHost' => 'ldap.example.com',
			'ldapAgentPassword' => 'bind-secret',
		]);

		$this->assertSingleAuditEvent(
			'user_ldap.config.updated',
			'LDAP configuration "%s" was updated, changed keys: %s',
			['configId' => self::CONFIG_ID, 'changedKeys' => 'ldapHost, ldapAgentPassword'],
		);
	}

	public function testModifyWithoutChanges(): void {
		$this->controller->modify(self::CONFIG_ID, []);

		$this->assertSame([], $this->auditEvents);
	}

	public function testShowWithoutPassword(): void {
		$this->controller->show(self::CONFIG_ID);

		$this->assertSame([], $this->auditEvents);
	}

	public function testShowWithPassword(): void {
		$this->controller->show(self::CONFIG_ID, true);

		$this->assertSingleAuditEvent(
			'user_ldap.config.password_revealed',
			'Bind password of LDAP configuration "%s" was revealed',
			['configId' => self::CONFIG_ID],
		);
	}

	public function testCopyConfiguration(): void {
		$this->helper->method('getNextServerConfigurationPrefix')
			->willReturn(self::NEW_CONFIG_ID);

		$this->controller->copyConfiguration(self::CONFIG_ID);

		$this->assertSingleAuditEvent(
			'user_ldap.config.copied',
			'LDAP configuration "%s" was copied to "%s"',
			['configId' => self::CONFIG_ID, 'newConfigId' => self::NEW_CONFIG_ID],
		);
	}
}
