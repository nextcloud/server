<?php

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

class AddressHandlerTest extends \Test\TestCase {
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $contactsManager;

	/** @var AddressHandler */
	private $addressHandler;

	/** @var IURLGenerator | \PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;

	/** @var IL10N | \PHPUnit\Framework\MockObject\MockObject */
	private $il10n;

	/** @var CloudIdManager */
	private $cloudIdManager;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)
			->getMock();
		$this->il10n = $this->getMockBuilder(IL10N::class)
			->getMock();

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

	public function dataTestSplitUserRemote() {
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
	 *
	 * @param string $remote
	 * @param string $expectedUser
	 * @param string $expectedUrl
	 */
	public function testSplitUserRemote($remote, $expectedUser, $expectedUrl): void {
		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		[$remoteUser, $remoteUrl] = $this->addressHandler->splitUserRemote($remote);
		$this->assertSame($expectedUser, $remoteUser);
		$this->assertSame($expectedUrl, $remoteUrl);
	}

	public function dataTestSplitUserRemoteError() {
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
	 *
	 * @param string $id
	 */
	public function testSplitUserRemoteError($id): void {
		$this->expectException(HintException::class);

		$this->addressHandler->splitUserRemote($id);
	}

	/**
	 * @dataProvider dataTestCompareAddresses
	 *
	 * @param string $user1
	 * @param string $server1
	 * @param string $user2
	 * @param string $server2
	 * @param bool $expected
	 */
	public function testCompareAddresses($user1, $server1, $user2, $server2, $expected): void {
		$this->assertSame($expected,
			$this->addressHandler->compareAddresses($user1, $server1, $user2, $server2)
		);
	}

	public function dataTestCompareAddresses() {
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
	 *
	 * @param string $url
	 * @param string $expectedResult
	 */
	public function testRemoveProtocolFromUrl($url, $expectedResult): void {
		$result = $this->addressHandler->removeProtocolFromUrl($url);
		$this->assertSame($expectedResult, $result);
	}

	public function dataTestRemoveProtocolFromUrl() {
		return [
			['http://owncloud.org', 'owncloud.org'],
			['https://owncloud.org', 'owncloud.org'],
			['owncloud.org', 'owncloud.org'],
		];
	}

	/**
	 * @dataProvider dataTestUrlContainProtocol
	 *
	 * @param string $url
	 * @param bool $expectedResult
	 */
	public function testUrlContainProtocol($url, $expectedResult): void {
		$result = $this->addressHandler->urlContainProtocol($url);
		$this->assertSame($expectedResult, $result);
	}

	public function dataTestUrlContainProtocol() {
		return [
			['http://nextcloud.com', true],
			['https://nextcloud.com', true],
			['nextcloud.com', false],
			['httpserver.com', false],
		];
	}
}
