<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Tests\Service;

use OC\Authentication\Token\IProvider as IAuthTokenProvider;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Service\ClientService;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\Authentication\Token\IToken;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class ClientServiceTest extends TestCase {
	private ClientMapper&MockObject $clientMapper;
	private ISecureRandom&MockObject $secureRandom;
	private AccessTokenMapper&MockObject $accessTokenMapper;
	private IAuthTokenProvider&MockObject $authTokenProvider;
	private IUserManager&MockObject $userManager;
	private ClientService $clientService;
	private ICrypto&MockObject $crypto;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->clientMapper = $this->createMock(ClientMapper::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->accessTokenMapper = $this->createMock(AccessTokenMapper::class);
		$this->authTokenProvider = $this->createMock(IAuthTokenProvider::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->clientService = new ClientService(
			$this->secureRandom,
			$this->crypto,
			$this->clientMapper,
			$this->userManager,
			$this->authTokenProvider,
			$this->logger,
			$this->accessTokenMapper,
		);
	}

	public function testAddClient(): void {
		$this->secureRandom
			->expects($this->exactly(2))
			->method('generate')
			->with(64, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
			->willReturnOnConsecutiveCalls(
				'MySecret',
				'MyClientIdentifier');

		$this->crypto
			->expects($this->once())
			->method('calculateHMAC')
			->willReturn('MyHashedSecret');

		$client = new Client();
		$client->setName('My Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret(bin2hex('MyHashedSecret'));
		$client->setClientIdentifier('MyClientIdentifier');

		$this->clientMapper
			->expects($this->once())
			->method('insert')
			->with($this->callback(function (Client $c) {
				return $c->getName() === 'My Client Name'
					&& $c->getRedirectUri() === 'https://example.com/'
					&& $c->getSecret() === bin2hex('MyHashedSecret')
					&& $c->getClientIdentifier() === 'MyClientIdentifier';
			}))->willReturnCallback(function (Client $c) {
				$c->setId(42);
				return $c;
			});

		$result = $this->clientService->addClient('My Client Name', 'https://example.com/');

		$this->assertEquals([
			'id' => 42,
			'name' => 'My Client Name',
			'redirectUri' => 'https://example.com/',
			'clientId' => 'MyClientIdentifier',
			'clientSecret' => 'MySecret',
		], $result);
	}

	public function testDeleteClient(): void {

		$userManager = Server::get(IUserManager::class);
		// count other users in the db before adding our own
		$count = 0;
		$function = function (IUser $user) use (&$count): void {
			if ($user->getLastLogin() > 0) {
				$count++;
			}
		};
		$userManager->callForAllUsers($function);
		$user1 = $userManager->createUser('test101', 'test101');
		$user1->updateLastLoginTimestamp();
		$tokenProviderMock = $this->getMockBuilder(IAuthTokenProvider::class)->getMock();

		// One getTokenByUser call per user; we return no matching tokens here
		// so invalidateTokenById is never invoked.
		$tokenProviderMock
			->expects($this->exactly($count + 1))
			->method('getTokenByUser')
			->willReturn([]);
		$tokenProviderMock
			->expects($this->never())
			->method('invalidateTokenById');

		$client = new Client();
		$client->setId(123);
		$client->setName('My Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret(bin2hex('MyHashedSecret'));
		$client->setClientIdentifier('MyClientIdentifier');

		$this->clientMapper
			->method('getByUid')
			->with(123)
			->willReturn($client);
		$this->accessTokenMapper
			->expects($this->once())
			->method('deleteByClientId')
			->with(123);
		$this->clientMapper
			->expects($this->once())
			->method('delete')
			->with($client);

		$this->clientService = new ClientService(
			$this->secureRandom,
			$this->crypto,
			$this->clientMapper,
			$userManager,
			$tokenProviderMock,
			$this->logger,
			$this->accessTokenMapper,
		);

		$this->clientService->deleteClient(123);
		$user1->delete();
	}

	public function testDeleteClientPreservesWipePendingToken(): void {
		$userManager = Server::get(IUserManager::class);
		$user = $userManager->createUser('test_wipe_preserve', 'test_wipe_preserve');
		$user->updateLastLoginTimestamp();

		$client = new Client();
		$client->setId(456);
		$client->setName('My Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret(bin2hex('MyHashedSecret'));
		$client->setClientIdentifier('MyClientIdentifier');

		// Token marked for wipe with a matching client name: must NOT be invalidated.
		$wipeToken = $this->createMock(IToken::class);
		$wipeToken->method('getId')->willReturn(11);
		$wipeToken->method('getName')->willReturn('My Client Name');

		// Regular token with matching name: must be invalidated.
		$regularToken = $this->createMock(IToken::class);
		$regularToken->method('getId')->willReturn(12);
		$regularToken->method('getName')->willReturn('My Client Name');

		// Non-matching name: must be left alone.
		$otherToken = $this->createMock(IToken::class);
		$otherToken->method('getId')->willReturn(13);
		$otherToken->method('getName')->willReturn('Some Other Client');

		$this->authTokenProvider
			->method('getTokenByUser')
			->willReturnCallback(function (string $uid) use ($wipeToken, $regularToken, $otherToken) {
				return $uid === 'test_wipe_preserve'
					? [$wipeToken, $regularToken, $otherToken]
					: [];
			});
		// Wipe state is signalled via WipeTokenException from getTokenById.
		$this->authTokenProvider
			->method('getTokenById')
			->willReturnCallback(function (int $id) use ($wipeToken, $regularToken) {
				if ($id === 11) {
					throw new WipeTokenException($wipeToken);
				}
				return $regularToken;
			});
		$this->authTokenProvider
			->expects($this->once())
			->method('invalidateTokenById')
			->with('test_wipe_preserve', 12);

		$this->clientMapper
			->method('getByUid')
			->with(456)
			->willReturn($client);
		$this->accessTokenMapper
			->expects($this->once())
			->method('deleteByClientId')
			->with(456);
		$this->clientMapper
			->expects($this->once())
			->method('delete')
			->with($client);

		$this->logger->expects($this->atLeastOnce())
			->method('info')
			->with($this->stringContains('Preserving token'), $this->callback(function (array $context) {
				return ($context['tokenId'] ?? null) === 11
					&& ($context['uid'] ?? null) === 'test_wipe_preserve';
			}));

		$clientService = new ClientService(
			$this->secureRandom,
			$this->crypto,
			$this->clientMapper,
			$userManager,
			$this->authTokenProvider,
			$this->logger,
			$this->accessTokenMapper,
		);

		$clientService->deleteClient(456);
	}
}
