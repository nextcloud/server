<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Federation;

use OC\Federation\CloudIdManager;
use OC\Memcache\ArrayCache;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use OCP\IUserManager;
use Test\TestCase;

/**
 * @group DB
 */
class CloudIdManagerTest extends TestCase {
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $contactsManager;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var CloudIdManager */
	private $cloudIdManager;
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $cacheFactory;


	protected function setUp(): void {
		parent::setUp();

		$this->contactsManager = $this->createMock(IManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheFactory->method('createDistributed')
			->willReturn(new ArrayCache(''));

		$this->cloudIdManager = new CloudIdManager(
			$this->contactsManager,
			$this->urlGenerator,
			$this->userManager,
			$this->cacheFactory,
			$this->createMock(IEventDispatcher::class)
		);
		$this->overwriteService(ICloudIdManager::class, $this->cloudIdManager);
	}

	public function dataGetDisplayNameFromContact(): array {
		return [
			['test1@example.tld', 'test', 'test'],
			['test2@example.tld', null, null],
			['test3@example.tld', 'test3@example', 'test3@example'],
			['test4@example.tld', 'test4@example.tld', null],
		];
	}

	/**
	 * @dataProvider dataGetDisplayNameFromContact
	 */
	public function testGetDisplayNameFromContact(string $cloudId, ?string $displayName, ?string $expected): void {
		$returnedContact = [
			'CLOUD' => [$cloudId],
			'FN' => $expected,
		];
		if ($displayName === null) {
			unset($returnedContact['FN']);
		}
		$this->contactsManager->method('search')
			->with($cloudId, ['CLOUD'])
			->willReturn([$returnedContact]);

		$this->assertEquals($expected, $this->cloudIdManager->getDisplayNameFromContact($cloudId));
		$this->assertEquals($expected, $this->cloudIdManager->getDisplayNameFromContact($cloudId));
	}

	public function cloudIdProvider(): array {
		return [
			['test@example.com', 'test', 'example.com', 'test@example.com'],
			['test@example.com/cloud', 'test', 'example.com/cloud', 'test@example.com/cloud'],
			['test@example.com/cloud/', 'test', 'example.com/cloud', 'test@example.com/cloud'],
			['test@example.com/cloud/index.php', 'test', 'example.com/cloud', 'test@example.com/cloud'],
			['test@example.com@example.com', 'test@example.com', 'example.com', 'test@example.com@example.com'],
		];
	}

	/**
	 * @dataProvider cloudIdProvider
	 */
	public function testResolveCloudId(string $cloudId, string $user, string $noProtocolRemote, string $cleanId): void {
		$displayName = 'Ample Ex';

		$this->contactsManager->expects($this->any())
			->method('search')
			->with($cleanId, ['CLOUD'])
			->willReturn([
				[
					'CLOUD' => [$cleanId],
					'FN' => $displayName,
				]
			]);

		$cloudId = $this->cloudIdManager->resolveCloudId($cloudId);

		$this->assertEquals($user, $cloudId->getUser());
		$this->assertEquals('https://' . $noProtocolRemote, $cloudId->getRemote());
		$this->assertEquals($cleanId, $cloudId->getId());
		$this->assertEquals($displayName . '@' . $noProtocolRemote, $cloudId->getDisplayId());
	}

	public function invalidCloudIdProvider(): array {
		return [
			['example.com'],
			['test:foo@example.com'],
			['test/foo@example.com']
		];
	}

	/**
	 * @dataProvider invalidCloudIdProvider
	 */
	public function testInvalidCloudId(string $cloudId): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->contactsManager->expects($this->never())
			->method('search');

		$this->cloudIdManager->resolveCloudId($cloudId);
	}

	public function getCloudIdProvider(): array {
		return [
			['test', 'example.com', 'test@example.com', null, 'https://example.com', 'https://example.com'],
			['test', 'http://example.com', 'test@http://example.com', 'test@example.com'],
			['test', null, 'test@http://example.com', 'test@example.com', 'http://example.com', 'http://example.com'],
			['test@example.com', 'example.com', 'test@example.com@example.com', null, 'https://example.com', 'https://example.com'],
			['test@example.com', 'https://example.com', 'test@example.com@example.com'],
			['test@example.com', null, 'test@example.com@example.com', null, 'https://example.com', 'https://example.com'],
			['test@example.com', 'https://example.com/index.php/s/shareToken', 'test@example.com@example.com', null, 'https://example.com', 'https://example.com'],
		];
	}

	/**
	 * @dataProvider getCloudIdProvider
	 */
	public function testGetCloudId(string $user, ?string $remote, string $id, ?string $searchCloudId = null, ?string $localHost = 'https://example.com', ?string $expectedRemoteId = null): void {
		if ($remote !== null) {
			$this->contactsManager->expects($this->any())
				->method('search')
				->with($searchCloudId ?? $id, ['CLOUD'])
				->willReturn([
					[
						'CLOUD' => [$searchCloudId ?? $id],
						'FN' => 'Ample Ex',
					]
				]);
		} else {
			$this->urlGenerator->expects(self::once())
				->method('getAbsoluteUrl')
				->willReturn($localHost);
		}
		$expectedRemoteId ??= $remote;

		$cloudId = $this->cloudIdManager->getCloudId($user, $remote);

		$this->assertEquals($id, $cloudId->getId(), 'Cloud ID');
		$this->assertEquals($expectedRemoteId, $cloudId->getRemote(), 'Remote URL');
	}
}
