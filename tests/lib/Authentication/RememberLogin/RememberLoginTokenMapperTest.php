<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\RememberLogin;

use OC\Authentication\RememberLogin\RememberLoginToken;
use OC\Authentication\RememberLogin\RememberLoginTokenMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Server;
use OCP\Snowflake\ISnowflakeGenerator;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class RememberLoginTokenMapperTest extends TestCase {
	private const UID = 'remember-login-token-mapper-test';

	private RememberLoginTokenMapper $mapper;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->mapper = Server::get(RememberLoginTokenMapper::class);
		$this->mapper->deleteByUid(self::UID);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->mapper->deleteByUid(self::UID);
		parent::tearDown();
	}

	private function createToken(string $token): void {
		$rememberLoginToken = new RememberLoginToken();
		$rememberLoginToken->uid = self::UID;
		$rememberLoginToken->token = $token;
		$this->mapper->insert($rememberLoginToken);
	}

	public function testRotateToken(): void {
		$this->createToken('oldToken');

		$this->assertSame(1, $this->mapper->rotateToken('oldToken', 'newToken'));
		$this->assertSame(self::UID, $this->mapper->findByToken('newToken')->uid);

		$this->expectException(DoesNotExistException::class);
		$this->mapper->findByToken('oldToken');
	}

	public function testRotateTokenAlreadyRotated(): void {
		$this->createToken('oldToken');
		$this->mapper->rotateToken('oldToken', 'newToken');

		$this->assertSame(0, $this->mapper->rotateToken('oldToken', 'otherToken'));
		$this->assertSame(self::UID, $this->mapper->findByToken('newToken')->uid);
	}

	public function testDeleteOlderThanBeforeSnowflakeEpoch(): void {
		$this->createToken('token');

		$this->assertSame(0, $this->mapper->deleteOlderThan(ISnowflakeGenerator::TS_OFFSET - 1));
		$this->assertSame(0, $this->mapper->deleteOlderThan(0));
		$this->assertSame(self::UID, $this->mapper->findByToken('token')->uid);
	}

	public function testDeleteOlderThan(): void {
		$this->createToken('token');

		$this->mapper->deleteOlderThan(time() - 60);
		$this->assertSame(self::UID, $this->mapper->findByToken('token')->uid);

		$this->assertGreaterThanOrEqual(1, $this->mapper->deleteOlderThan(time() + 60));
		$this->expectException(DoesNotExistException::class);
		$this->mapper->findByToken('token');
	}
}
