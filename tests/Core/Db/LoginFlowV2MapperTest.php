<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Db;

use OC\Core\Db\LoginFlowV2;
use OC\Core\Db\LoginFlowV2Mapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class LoginFlowV2MapperTest extends TestCase {
	private LoginFlowV2Mapper $mapper;
	private LoginFlowV2 $flow;
	private int $now;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->now = time();
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getTime')->willReturnCallback(fn (): int => $this->now);
		$this->mapper = new LoginFlowV2Mapper(Server::get(IDBConnection::class), $time);
		$this->flow = new LoginFlowV2();
		$this->flow->setLoginToken($this->getUniqueID('login-'));
		$this->flow->setPollToken($this->getUniqueID('poll-'));
		$this->flow->setTimestamp($this->now);
		$this->flow->setStarted(1);
		$this->flow->setClientName('Test app');
		$this->flow->setPublicKey('public-key');
		$this->flow->setPrivateKey('private-key');
		$this->mapper->insert($this->flow);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->mapper->delete($this->flow);
		parent::tearDown();
	}

	private function credentials(): LoginFlowV2 {
		$flow = clone $this->flow;
		$flow->setLoginName('user');
		$flow->setServer('https://cloud.example.org');
		$flow->setAppPassword('encrypted-password');
		return $flow;
	}

	public function testDeclinePreventsCompletionFromStaleRead(): void {
		$credentials = $this->credentials();
		$this->assertTrue($this->mapper->deletePending($this->flow->getLoginToken()));
		$this->assertFalse($this->mapper->complete($credentials));
		$this->expectException(DoesNotExistException::class);
		$this->mapper->getByPollToken($this->flow->getPollToken());
	}

	public function testCompletionPreventsDecline(): void {
		$this->assertTrue($this->mapper->complete($this->credentials()));
		$this->assertFalse($this->mapper->deletePending($this->flow->getLoginToken()));
		$completed = $this->mapper->getByPollToken($this->flow->getPollToken());
		$this->assertSame('encrypted-password', $completed->getAppPassword());
		$this->assertSame('user', $completed->getLoginName());
		$this->assertSame('https://cloud.example.org', $completed->getServer());
	}

	public function testCompletionCannotOverwriteCredentials(): void {
		$this->assertTrue($this->mapper->complete($this->credentials()));
		$otherCredentials = $this->credentials();
		$otherCredentials->setAppPassword('other-password');
		$this->assertFalse($this->mapper->complete($otherCredentials));
		$this->assertSame('encrypted-password', $this->mapper->getByPollToken($this->flow->getPollToken())->getAppPassword());
	}

	public function testExpiredRequestCannotComplete(): void {
		$this->now += 1201;
		$this->assertFalse($this->mapper->complete($this->credentials()));
	}

	public function testDeclineOnlyAffectsTheSelectedRequest(): void {
		$this->assertFalse($this->mapper->deletePending('unknown-token'));
		$this->assertSame($this->flow->getId(), $this->mapper->getByLoginToken($this->flow->getLoginToken())->getId());
		$this->assertTrue($this->mapper->deletePending($this->flow->getLoginToken()));
		$this->assertFalse($this->mapper->deletePending($this->flow->getLoginToken()));
	}
}
