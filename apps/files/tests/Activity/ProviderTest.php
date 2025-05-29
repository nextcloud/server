<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Tests\Activity;

use OCA\Files\Activity\Provider;
use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\Contacts\IManager as IContactsManager;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class ProviderTest
 *
 * @package OCA\Files\Tests\Activity
 */
class ProviderTest extends TestCase {
	protected IFactory&MockObject $l10nFactory;
	protected IURLGenerator&MockObject $url;
	protected IManager&MockObject $activityManager;
	protected IUserManager&MockObject $userManager;
	protected IRootFolder&MockObject $rootFolder;
	protected ICloudIdManager&MockObject $cloudIdManager;
	protected IContactsManager&MockObject $contactsManager;
	protected IEventMerger&MockObject $eventMerger;

	protected function setUp(): void {
		parent::setUp();

		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->activityManager = $this->createMock(IManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->contactsManager = $this->createMock(IContactsManager::class);
		$this->eventMerger = $this->createMock(IEventMerger::class);
	}

	/**
	 * @param string[] $methods
	 * @return Provider|MockObject
	 */
	protected function getProvider(array $methods = []) {
		if (!empty($methods)) {
			return $this->getMockBuilder(Provider::class)
				->setConstructorArgs([
					$this->l10nFactory,
					$this->url,
					$this->activityManager,
					$this->userManager,
					$this->rootFolder,
					$this->cloudIdManager,
					$this->contactsManager,
					$this->eventMerger,
				])
				->onlyMethods($methods)
				->getMock();
		}
		return new Provider(
			$this->l10nFactory,
			$this->url,
			$this->activityManager,
			$this->userManager,
			$this->rootFolder,
			$this->cloudIdManager,
			$this->contactsManager,
			$this->eventMerger
		);
	}

	public static function dataGetFile(): array {
		return [
			[[42 => '/FortyTwo.txt'], null, '42', 'FortyTwo.txt', 'FortyTwo.txt'],
			[['23' => '/Twenty/Three.txt'], null, '23', 'Three.txt', 'Twenty/Three.txt'],
			['/Foo/Bar.txt', 128, '128', 'Bar.txt', 'Foo/Bar.txt'], // Legacy from ownCloud 8.2 and before
		];
	}

	/**
	 * @dataProvider dataGetFile
	 */
	public function testGetFile(array|string $parameter, ?int $eventId, string $id, string $name, string $path): void {
		$provider = $this->getProvider();

		if ($eventId !== null) {
			$event = $this->createMock(IEvent::class);
			$event->expects($this->once())
				->method('getObjectId')
				->willReturn($eventId);
		} else {
			$event = null;
		}

		$this->url->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('files.viewcontroller.showFile', ['fileid' => $id])
			->willReturn('link-' . $id);

		$result = self::invokePrivate($provider, 'getFile', [$parameter, $event]);

		$this->assertSame('file', $result['type']);
		$this->assertSame($id, $result['id']);
		$this->assertSame($name, $result['name']);
		$this->assertSame($path, $result['path']);
		$this->assertSame('link-' . $id, $result['link']);
	}


	public function testGetFileThrows(): void {
		$this->expectException(UnknownActivityException::class);

		$provider = $this->getProvider();
		self::invokePrivate($provider, 'getFile', ['/Foo/Bar.txt', null]);
	}

	public static function dataGetUser(): array {
		return [
			['test', 'Test user', null, ['type' => 'user', 'id' => 'test', 'name' => 'Test user']],
			['test@http://localhost', null, ['user' => 'test', 'displayId' => 'test@localhost', 'remote' => 'localhost', 'name' => null], ['type' => 'user', 'id' => 'test', 'name' => 'test@localhost', 'server' => 'localhost']],
			['test@http://localhost', null, ['user' => 'test', 'displayId' => 'test@localhost', 'remote' => 'localhost', 'name' => 'Remote user'], ['type' => 'user', 'id' => 'test', 'name' => 'Remote user (test@localhost)', 'server' => 'localhost']],
			['test', null, null, ['type' => 'user', 'id' => 'test', 'name' => 'test']],
		];
	}

	/**
	 * @dataProvider dataGetUser
	 */
	public function testGetUser(string $uid, ?string $userDisplayName, ?array $cloudIdData, array $expected): void {
		$provider = $this->getProvider();

		if ($userDisplayName !== null) {
			$this->userManager->expects($this->once())
				->method('getDisplayName')
				->with($uid)
				->willReturn($userDisplayName);
		}
		if ($cloudIdData !== null) {
			$this->cloudIdManager->expects($this->once())
				->method('isValidCloudId')
				->willReturn(true);

			$cloudId = $this->createMock(ICloudId::class);
			$cloudId->expects($this->once())
				->method('getUser')
				->willReturn($cloudIdData['user']);
			$cloudId->expects($this->once())
				->method('getDisplayId')
				->willReturn($cloudIdData['displayId']);
			$cloudId->expects($this->once())
				->method('getRemote')
				->willReturn($cloudIdData['remote']);

			$this->cloudIdManager->expects($this->once())
				->method('resolveCloudId')
				->with($uid)
				->willReturn($cloudId);

			if ($cloudIdData['name'] !== null) {
				$this->contactsManager->expects($this->once())
					->method('search')
					->with($cloudIdData['displayId'], ['CLOUD'])
					->willReturn([
						[
							'CLOUD' => $cloudIdData['displayId'],
							'FN' => $cloudIdData['name'],
						]
					]);
			} else {
				$this->contactsManager->expects($this->once())
					->method('search')
					->with($cloudIdData['displayId'], ['CLOUD'])
					->willReturn([]);
			}
		}

		$result = self::invokePrivate($provider, 'getUser', [$uid]);
		$this->assertEquals($expected, $result);
	}
}
