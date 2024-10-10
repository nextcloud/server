<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Tests\Db;

use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Exceptions\AccessTokenNotFoundException;
use OCP\AppFramework\Utility\ITimeFactory;
use Test\TestCase;

/**
 * @group DB
 */
class AccessTokenMapperTest extends TestCase {
	/** @var AccessTokenMapper */
	private $accessTokenMapper;

	protected function setUp(): void {
		parent::setUp();
		$this->accessTokenMapper = new AccessTokenMapper(\OC::$server->getDatabaseConnection(), \OC::$server->get(ITimeFactory::class));
	}

	public function testGetByCode(): void {
		$this->accessTokenMapper->deleteByClientId(1234);
		$token = new AccessToken();
		$token->setClientId(1234);
		$token->setTokenId((string)time());
		$token->setEncryptedToken('MyEncryptedToken');
		$token->setHashedCode(hash('sha512', 'MyAwesomeToken'));
		$this->accessTokenMapper->insert($token);
		$token->resetUpdatedFields();

		$result = $this->accessTokenMapper->getByCode('MyAwesomeToken');
		$this->assertEquals($token, $result);
		$this->accessTokenMapper->delete($token);
	}


	public function testDeleteByClientId(): void {
		$this->expectException(AccessTokenNotFoundException::class);

		$this->accessTokenMapper->deleteByClientId(1234);
		$token = new AccessToken();
		$token->setClientId(1234);
		$token->setTokenId((string)time());
		$token->setEncryptedToken('MyEncryptedToken');
		$token->setHashedCode(hash('sha512', 'MyAwesomeToken'));
		$this->accessTokenMapper->insert($token);
		$token->resetUpdatedFields();
		$this->accessTokenMapper->deleteByClientId(1234);
		$this->accessTokenMapper->getByCode('MyAwesomeToken');
	}
}
