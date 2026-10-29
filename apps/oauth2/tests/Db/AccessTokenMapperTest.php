<?php

declare(strict_types=1);

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

	#[\Override]
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

	private function insertToken(int $clientId, int $tokenCount): AccessToken {
		$token = new AccessToken();
		$token->clientId = $clientId;
		$token->tokenId = time();
		$token->encryptedToken = 'MyEncryptedToken';
		$token->hashedCode = hash('sha512', 'MyAwesomeToken');
		$token->tokenCount = $tokenCount;

		$this->accessTokenMapper->insert($token);
		return $token;
	}

	public function testRotateTokenInAuthorizationCodeState(): void {
		$this->accessTokenMapper->deleteByClientId(1235);
		$token = $this->insertToken(1235, 0);

		$rotated = $this->accessTokenMapper->rotateToken($token->id, 'MyAwesomeToken', 'rotatedcode', 'OtherEncryptedToken', 'authorization_code');
		$this->assertEquals(1, $rotated);

		$row = $this->accessTokenMapper->getByCode('rotatedcode');
		$this->assertEquals('OtherEncryptedToken', $row->encryptedToken);
		$this->assertEquals(1, $row->tokenCount);

		$this->accessTokenMapper->deleteByClientId(1235);
	}

	public function testRotateTokenWithRefreshTokenGrantOnUnusedToken(): void {
		$this->accessTokenMapper->deleteByClientId(1235);
		$token = $this->insertToken(1235, 0);

		// an unused authorization code is not a valid refresh token
		$rotated = $this->accessTokenMapper->rotateToken($token->id, 'MyAwesomeToken', 'rotatedcode', 'OtherEncryptedToken', 'refresh_token');
		$this->assertEquals(0, $rotated);

		$row = $this->accessTokenMapper->getByCode('MyAwesomeToken');
		$this->assertEquals('MyEncryptedToken', $row->encryptedToken);
		$this->assertEquals(0, $row->tokenCount);

		$this->accessTokenMapper->deleteByClientId(1235);
	}

	public function testRotateTokenInRefreshTokenState(): void {
		$this->accessTokenMapper->deleteByClientId(1235);
		$token = $this->insertToken(1235, 1);

		$rotated = $this->accessTokenMapper->rotateToken($token->id, 'MyAwesomeToken', 'rotatedcode', 'OtherEncryptedToken', 'refresh_token');
		$this->assertEquals(1, $rotated);

		$row = $this->accessTokenMapper->getByCode('rotatedcode');
		$this->assertEquals('OtherEncryptedToken', $row->encryptedToken);
		$this->assertEquals(2, $row->tokenCount);

		$this->accessTokenMapper->deleteByClientId(1235);
	}

	public function testRotateTokenWithAuthorizationCodeGrantOnUsedToken(): void {
		$this->accessTokenMapper->deleteByClientId(1235);
		$token = $this->insertToken(1235, 1);

		// an already used token can't be processed through the authorization code grant
		$rotated = $this->accessTokenMapper->rotateToken($token->id, 'MyAwesomeToken', 'rotatedcode', 'OtherEncryptedToken', 'authorization_code');
		$this->assertEquals(0, $rotated);

		$this->accessTokenMapper->deleteByClientId(1235);
	}
}
