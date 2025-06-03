<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing\Tests;

use OC\Federation\CloudIdManager;
use OCA\FederatedFileSharing\AddressHandler;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;

class AddressHandlerTest extends \Test\TestCase {
	protected IManager&MockObject $contactsManager;
	private IURLGenerator&MockObject $urlGenerator;
	private IL10N&MockObject $il10n;
	private CloudIdManager $cloudIdManager;
	private AddressHandler $addressHandler;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->il10n = $this->createMock(IL10N::class);
		$this->contactsManager = $this->createMock(IManager::class);

		$this->cloudIdManager = new CloudIdManager(
			$this->contactsManager,
			$this->urlGenerator,
			$this->createMock(IUserManager::class),
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class)
		);

		$this->addressHandler = new AddressHandler($this->urlGenerator, $this->il10n, $this->cloudIdManager);
	}

	public static function dataTestSplitUserRemote(): array {
		$userPrefix = ['user@name', 'username'];
		$protocols = ['', 'http://', 'https://'];
		$remotes = [
			'localhost',
			'local.host',
			'dev.local.host',
			'dev.local.host/path',
			'dev.local.host/at@inpath',
			'127.0.0.1',
			'::1',
			'::192.0.2.128',
			'::192.0.2.128/at@inpath',
		];

		$testCases = [];
		foreach ($userPrefix as $user) {
			foreach ($remotes as $remote) {
				foreach ($protocols as $protocol) {
					$baseUrl = $user . '@' . $protocol . $remote;

					if ($protocol === '') {
						// https:// protocol is expected in the final result
						$protocol = 'https://';
					}

					$testCases[] = [$baseUrl, $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/', $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/index.php', $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/index.php/s/token', $user, $protocol . $remote];
				}
			}
		}
		return $testCases;
	}

	/**
	 * @dataProvider dataTestSplitUserRemote
	 */
	public function testSplitUserRemote(string $remote, string $expectedUser, string $expectedUrl): void {
		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		[$remoteUser, $remoteUrl] = $this->addressHandler->splitUserRemote($remote);
		$this->assertSame($expectedUser, $remoteUser);
		$this->assertSame($expectedUrl, $remoteUrl);
	}

	public static function dataTestSplitUserRemoteError(): array {
		return [
			// Invalid path
			['user@'],

			// Invalid user
			['@server'],
			['us/er@server'],
			['us:er@server'],

			// Invalid splitting
			['user'],
			[''],
			['us/erserver'],
			['us:erserver'],
		];
	}

	/**
	 * @dataProvider dataTestSplitUserRemoteError
	 */
	public function testSplitUserRemoteError(string $id): void {
		$this->expectException(HintException::class);

		$this->addressHandler->splitUserRemote($id);
	}

	/**
	 * @dataProvider dataTestCompareAddresses
	 */
	public function testCompareAddresses(string $user1, string $server1, string $user2, string $server2, bool $expected): void {
		$this->assertSame($expected,
			$this->addressHandler->compareAddresses($user1, $server1, $user2, $server2)
		);
	}

	public static function dataTestCompareAddresses(): array {
		return [
			['user1', 'http://server1', 'user1', 'http://server1', true],
			['user1', 'https://server1', 'user1', 'http://server1', true],
			['user1', 'http://serVer1', 'user1', 'http://server1', true],
			['user1', 'http://server1/',  'user1', 'http://server1', true],
			['user1', 'server1', 'user1', 'http://server1', true],
			['user1', 'http://server1', 'user1', 'http://server2', false],
			['user1', 'https://server1', 'user1', 'http://server2', false],
			['user1', 'http://serVer1', 'user1', 'http://serer2', false],
			['user1', 'http://server1/', 'user1', 'http://server2', false],
			['user1', 'server1', 'user1', 'http://server2', false],
			['user1', 'http://server1', 'user2', 'http://server1', false],
			['user1', 'https://server1', 'user2', 'http://server1', false],
			['user1', 'http://serVer1', 'user2', 'http://server1', false],
			['user1', 'http://server1/',  'user2', 'http://server1', false],
			['user1', 'server1', 'user2', 'http://server1', false],
		];
	}

	/**
	 * @dataProvider dataTestRemoveProtocolFromUrl
	 */
	public function testRemoveProtocolFromUrl(string $url, string $expectedResult): void {
		$result = $this->addressHandler->removeProtocolFromUrl($url);
		$this->assertSame($expectedResult, $result);
	}

	public static function dataTestRemoveProtocolFromUrl(): array {
		return [
			['http://example.tld', 'example.tld'],
			['https://example.tld', 'example.tld'],
			['example.tld', 'example.tld'],
		];
	}

	/**
	 * @dataProvider dataTestUrlContainProtocol
	 */
	public function testUrlContainProtocol(string $url, bool $expectedResult): void {
		$result = $this->addressHandler->urlContainProtocol($url);
		$this->assertSame($expectedResult, $result);
	}

	public static function dataTestUrlContainProtocol(): array {
		return [
			['http://nextcloud.com', true],
			['https://nextcloud.com', true],
			['nextcloud.com', false],
			['httpserver.com', false],
		];
	}
}
