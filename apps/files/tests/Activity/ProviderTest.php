<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\Files\Tests\Activity;


use OCA\Files\Activity\Provider;
use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use Test\TestCase;

/**
 * Class ProviderTest
 *
 * @package OCA\Files\Tests\Activity
 */
class ProviderTest extends TestCase {

	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $l10nFactory;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $url;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $activityManager;
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IEventMerger|\PHPUnit_Framework_MockObject_MockObject */
	protected $eventMerger;

	public function setUp() {
		parent::setUp();

		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->activityManager = $this->createMock(IManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->eventMerger = $this->createMock(IEventMerger::class);
	}

	/**
	 * @param string[] $methods
	 * @return Provider|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getProvider(array $methods = []) {
		if (!empty($methods)) {
			return $this->getMockBuilder(Provider::class)
				->setConstructorArgs([
					$this->l10nFactory,
					$this->url,
					$this->activityManager,
					$this->userManager,
					$this->eventMerger,
				])
				->setMethods($methods)
				->getMock();
		}
		return new Provider(
			$this->l10nFactory,
			$this->url,
			$this->activityManager,
			$this->userManager,
			$this->eventMerger
		);
	}

	public function dataGetFile() {
		return [
			[[42 => '/FortyTwo.txt'], null, '42', 'FortyTwo.txt', 'FortyTwo.txt'],
			[['23' => '/Twenty/Three.txt'], null, '23', 'Three.txt', 'Twenty/Three.txt'],
			['/Foo/Bar.txt', '128', '128', 'Bar.txt', 'Foo/Bar.txt'], // Legacy from ownCloud 8.2 and before
		];
	}

	/**
	 * @dataProvider dataGetFile
	 * @param mixed $parameter
	 * @param mixed $eventId
	 * @param int $id
	 * @param string $name
	 * @param string $path
	 */
	public function testGetFile($parameter, $eventId, $id, $name, $path) {
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

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetFileThrows() {
		$provider = $this->getProvider();
		self::invokePrivate($provider, 'getFile', ['/Foo/Bar.txt', null]);
	}

	public function dataGetUser() {
		return [
			['test', [], false, 'Test'],
			['foo', ['admin' => 'Admin'], false, 'Bar'],
			['admin', ['admin' => 'Administrator'], true, 'Administrator'],
		];
	}

	/**
	 * @dataProvider dataGetUser
	 * @param string $uid
	 * @param array $cache
	 * @param bool $cacheHit
	 * @param string $name
	 */
	public function testGetUser($uid, $cache, $cacheHit, $name) {
		$provider = $this->getProvider(['getDisplayName']);

		self::invokePrivate($provider, 'displayNames', [$cache]);

		if (!$cacheHit) {
			$provider->expects($this->once())
				->method('getDisplayName')
				->with($uid)
				->willReturn($name);
		} else {
			$provider->expects($this->never())
				->method('getDisplayName');
		}

		$result = self::invokePrivate($provider, 'getUser', [$uid]);
		$this->assertSame('user', $result['type']);
		$this->assertSame($uid, $result['id']);
		$this->assertSame($name, $result['name']);
	}

	public function dataGetDisplayName() {
		return [
			['test', true, 'Test'],
			['foo', false, 'foo'],
		];
	}

	/**
	 * @dataProvider dataGetDisplayName
	 * @param string $uid
	 * @param string $name
	 */
	public function testGetDisplayNamer($uid, $validUser, $name) {
		$provider = $this->getProvider();

		if ($validUser) {
			$user = $this->createMock(IUser::class);
			$user->expects($this->once())
				->method('getDisplayName')
				->willReturn($name);
			$this->userManager->expects($this->once())
				->method('get')
				->with($uid)
				->willReturn($user);
		} else {
			$this->userManager->expects($this->once())
				->method('get')
				->with($uid)
				->willReturn(null);
		}

		$this->assertSame($name, self::invokePrivate($provider, 'getDisplayName', [$uid]));
	}
}
