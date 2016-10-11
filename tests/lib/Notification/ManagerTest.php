<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Notification;

use OC\Notification\Manager;
use OCP\Notification\IManager;
use Test\TestCase;

class ManagerTest extends TestCase {
	/** @var IManager */
	protected $manager;

	public function setUp() {
		parent::setUp();
		$this->manager = new Manager();
	}

	public function testRegisterApp() {
		$app = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();

		$closure = function() use ($app) {
			return $app;
		};

		$this->assertEquals([], $this->invokePrivate($this->manager, 'getApps'));

		$this->manager->registerApp($closure);

		$this->assertEquals([$app], $this->invokePrivate($this->manager, 'getApps'));
		$this->assertEquals([$app], $this->invokePrivate($this->manager, 'getApps'));

		$this->manager->registerApp($closure);

		$this->assertEquals([$app, $app], $this->invokePrivate($this->manager, 'getApps'));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRegisterAppInvalid() {
		$notifier = $this->getMockBuilder('OCP\Notification\INotifier')
			->disableOriginalConstructor()
			->getMock();

		$closure = function() use ($notifier) {
			return $notifier;
		};

		$this->manager->registerApp($closure);

		$this->invokePrivate($this->manager, 'getApps');
	}

	public function testRegisterNotifier() {
		$notifier = $this->getMockBuilder('OCP\Notification\INotifier')
			->disableOriginalConstructor()
			->getMock();

		$closure = function() use ($notifier) {
			return $notifier;
		};

		$this->assertEquals([], $this->invokePrivate($this->manager, 'getNotifiers'));
		$this->assertEquals([], $this->invokePrivate($this->manager, 'listNotifiers'));

		$this->manager->registerNotifier($closure, function() {
			return ['id' => 'test1', 'name' => 'Test One'];
		});

		$this->assertEquals([$notifier], $this->invokePrivate($this->manager, 'getNotifiers'));
		$this->assertEquals(['test1' => 'Test One'], $this->invokePrivate($this->manager, 'listNotifiers'));
		$this->assertEquals([$notifier], $this->invokePrivate($this->manager, 'getNotifiers'));
		$this->assertEquals(['test1' => 'Test One'], $this->invokePrivate($this->manager, 'listNotifiers'));

		$this->manager->registerNotifier($closure, function() {
			return ['id' => 'test2', 'name' => 'Test Two'];
		});

		$this->assertEquals([$notifier, $notifier], $this->invokePrivate($this->manager, 'getNotifiers'));
		$this->assertEquals(['test1' => 'Test One', 'test2' => 'Test Two'], $this->invokePrivate($this->manager, 'listNotifiers'));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRegisterNotifierInvalid() {
		$app = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();

		$closure = function() use ($app) {
			return $app;
		};

		$this->manager->registerNotifier($closure, function() {
			return ['id' => 'test1', 'name' => 'Test One'];
		});

		$this->invokePrivate($this->manager, 'getNotifiers');
	}

	public function dataRegisterNotifierInfoInvalid() {
		return [
			[null],
			['No array'],
			[['id' => 'test1', 'name' => 'Test One', 'size' => 'Invalid']],
			[['no-id' => 'test1', 'name' => 'Test One']],
			[['id' => 'test1', 'no-name' => 'Test One']],
		];
	}

	/**
	 * @dataProvider dataRegisterNotifierInfoInvalid
	 * @expectedException \InvalidArgumentException
	 * @param mixed $data
	 */
	public function testRegisterNotifierInfoInvalid($data) {
		$app = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();

		$closure = function() use ($app) {
			return $app;
		};

		$this->manager->registerNotifier($closure, function() use ($data) {
			return $data;
		});

		$this->manager->listNotifiers();
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage The given notifier ID test1 is already in use
	 */
	public function testRegisterNotifierInfoDuplicate() {
		$app = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();

		$closure = function() use ($app) {
			return $app;
		};

		$this->manager->registerNotifier($closure, function() {
			return ['id' => 'test1', 'name' => 'Test One'];
		});

		$this->manager->registerNotifier($closure, function() {
			return ['id' => 'test1', 'name' => 'Test One'];
		});

		$this->manager->listNotifiers();
	}

	public function testCreateNotification() {
		$action = $this->manager->createNotification();
		$this->assertInstanceOf('OCP\Notification\INotification', $action);
	}

	public function testNotify() {
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('isValid')
			->willReturn(true);

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $app */
		$app = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();
		$app->expects($this->once())
			->method('notify')
			->with($notification);

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $app2 */
		$app2 = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();
		$app2->expects($this->once())
			->method('notify')
			->with($notification);

		$this->manager->registerApp(function() use ($app) {
			return $app;
		});
		$this->manager->registerApp(function() use ($app2) {
			return $app2;
		});

		$this->manager->notify($notification);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testNotifyInvalid() {
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('isValid')
			->willReturn(false);

		$this->manager->notify($notification);
	}

	public function testPrepare() {
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('isValidParsed')
			->willReturn(true);
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification2 */
		$notification2 = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$notification2->expects($this->exactly(2))
			->method('isValidParsed')
			->willReturn(true);

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $notifier */
		$notifier = $this->getMockBuilder('OCP\Notification\INotifier')
			->disableOriginalConstructor()
			->getMock();
		$notifier->expects($this->once())
			->method('prepare')
			->with($notification, 'en')
			->willReturnArgument(0);

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $notifier2 */
		$notifier2 = $this->getMockBuilder('OCP\Notification\INotifier')
			->disableOriginalConstructor()
			->getMock();
		$notifier2->expects($this->once())
			->method('prepare')
			->with($notification, 'en')
			->willReturn($notification2);

		$this->manager->registerNotifier(function() use ($notifier) {
			return $notifier;
		}, function() {
			return ['id' => 'test1', 'name' => 'Test One'];
		});
		$this->manager->registerNotifier(function() use ($notifier2) {
			return $notifier2;
		}, function() {
			return ['id' => 'test2', 'name' => 'Test Two'];
		});

		$this->assertEquals($notification2, $this->manager->prepare($notification, 'en'));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareInvalid() {
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('isValidParsed')
			->willReturn(false);

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $notifier */
		$notifier = $this->getMockBuilder('OCP\Notification\INotifier')
			->disableOriginalConstructor()
			->getMock();
		$notifier->expects($this->once())
			->method('prepare')
			->with($notification, 'de')
			->willReturnArgument(0);

		$this->manager->registerNotifier(function() use ($notifier) {
			return $notifier;
		}, function() {
			return ['id' => 'test1', 'name' => 'Test One'];
		});

		$this->manager->prepare($notification, 'de');
	}

	public function testPrepareNotifierThrows() {
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('isValidParsed')
			->willReturn(true);

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $notifier */
		$notifier = $this->getMockBuilder('OCP\Notification\INotifier')
			->disableOriginalConstructor()
			->getMock();
		$notifier->expects($this->once())
			->method('prepare')
			->with($notification, 'de')
			->willThrowException(new \InvalidArgumentException);

		$this->manager->registerNotifier(function() use ($notifier) {
			return $notifier;
		}, function() {
			return ['id' => 'test1', 'name' => 'Test One'];
		});

		$this->assertEquals($notification, $this->manager->prepare($notification, 'de'));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareNoNotifier() {
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('isValidParsed')
			->willReturn(false);

		$this->manager->prepare($notification, 'en');
	}

	public function testMarkProcessed() {
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $app */
		$app = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();
		$app->expects($this->once())
			->method('markProcessed')
			->with($notification);

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $app2 */
		$app2 = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();
		$app2->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$this->manager->registerApp(function() use ($app) {
			return $app;
		});
		$this->manager->registerApp(function() use ($app2) {
			return $app2;
		});

		$this->manager->markProcessed($notification);
	}

	public function testGetCount() {
		/** @var \OCP\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $app */
		$app = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();
		$app->expects($this->once())
			->method('getCount')
			->with($notification)
			->willReturn(21);

		/** @var \OCP\Notification\IApp|\PHPUnit_Framework_MockObject_MockObject $app2 */
		$app2 = $this->getMockBuilder('OCP\Notification\IApp')
			->disableOriginalConstructor()
			->getMock();
		$app2->expects($this->once())
			->method('getCount')
			->with($notification)
			->willReturn(42);

		$this->manager->registerApp(function() use ($app) {
			return $app;
		});
		$this->manager->registerApp(function() use ($app2) {
			return $app2;
		});

		$this->assertSame(63, $this->manager->getCount($notification));
	}
}
