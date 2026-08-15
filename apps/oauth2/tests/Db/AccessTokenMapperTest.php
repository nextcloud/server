<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Tests\Db;

use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Exceptions\AccessTokenNotFoundException;
use OCP\Server;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
final class AccessTokenMapperTest extends TestCase {
	private AccessTokenMapper $accessTokenMapper;

	protected function setUp(): void {
		parent::setUp();
		$this->accessTokenMapper = Server::get(AccessTokenMapper::class);
	}

	public function testGetByCode(): void {
		$this->accessTokenMapper->deleteByClientId(1234);
		$token = new AccessToken();
		$token->clientId = 1234;
		$token->tokenId = time();
		$token->encryptedToken = 'MyEncryptedToken';
		$token->hashedCode = hash('sha512', 'MyAwesomeToken');
		$this->accessTokenMapper->insert($token);

		$result = $this->accessTokenMapper->getByCode('MyAwesomeToken');
		$this->assertEquals($token, $result);
		$this->accessTokenMapper->delete($token);
	}

	public function testDeleteByClientId(): void {
		$this->expectException(AccessTokenNotFoundException::class);

		$this->accessTokenMapper->deleteByClientId(1234);
		$token = new AccessToken();
		$token->clientId = 1234;
		$token->tokenId = time();
		$token->encryptedToken = 'MyEncryptedToken';
		$token->hashedCode = hash('sha512', 'MyAwesomeToken');
		$this->accessTokenMapper->insert($token);
		$this->accessTokenMapper->deleteByClientId(1234);
		$this->accessTokenMapper->getByCode('MyAwesomeToken');
	}
}
