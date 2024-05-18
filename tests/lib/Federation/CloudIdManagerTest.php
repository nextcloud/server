<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Federation;

use OC\Federation\CloudIdManager;
use OC\Memcache\ArrayCache;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use OCP\IUserManager;
use Test\TestCase;

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
		$this->cacheFactory->method('createLocal')
			->willReturn(new ArrayCache(''));

		$this->cloudIdManager = new CloudIdManager(
			$this->contactsManager,
			$this->urlGenerator,
			$this->userManager,
			$this->cacheFactory,
			$this->createMock(IEventDispatcher::class)
		);
	}

	public function cloudIdProvider() {
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
	 *
	 * @param string $cloudId
	 * @param string $user
	 * @param string $remote
	 */
	public function testResolveCloudId($cloudId, $user, $remote, $cleanId) {
		$displayName = 'Ample Ex';

		$this->contactsManager->expects($this->any())
			->method('search')
			->with($cleanId, ['CLOUD'])
			->willReturn([
				[
					'CLOUD' => [$cleanId],
					'FN' => 'Ample Ex',
				]
			]);

		$cloudId = $this->cloudIdManager->resolveCloudId($cloudId);

		$this->assertEquals($user, $cloudId->getUser());
		$this->assertEquals($remote, $cloudId->getRemote());
		$this->assertEquals($cleanId, $cloudId->getId());
		$this->assertEquals($displayName . '@' . $remote, $cloudId->getDisplayId());
	}

	public function invalidCloudIdProvider() {
		return [
			['example.com'],
			['test:foo@example.com'],
			['test/foo@example.com']
		];
	}

	/**
	 * @dataProvider invalidCloudIdProvider
	 *
	 * @param string $cloudId
	 *
	 */
	public function testInvalidCloudId($cloudId) {
		$this->expectException(\InvalidArgumentException::class);

		$this->contactsManager->expects($this->never())
			->method('search');

		$this->cloudIdManager->resolveCloudId($cloudId);
	}

	public function getCloudIdProvider(): array {
		return [
			['test', 'example.com', 'test@example.com'],
			['test', 'http://example.com', 'test@http://example.com', 'test@example.com'],
			['test', null, 'test@http://example.com', 'test@example.com', 'http://example.com', 'http://example.com'],
			['test@example.com', 'example.com', 'test@example.com@example.com'],
			['test@example.com', 'https://example.com', 'test@example.com@example.com'],
			['test@example.com', null, 'test@example.com@example.com', null, 'https://example.com', 'https://example.com'],
			['test@example.com', 'https://example.com/index.php/s/shareToken', 'test@example.com@example.com', null, 'https://example.com', 'https://example.com'],
		];
	}

	/**
	 * @dataProvider getCloudIdProvider
	 *
	 * @param string $user
	 * @param null|string $remote
	 * @param string $id
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
