<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
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

namespace OCA\UpdateNotification\Tests\Notification;


use OCA\UpdateNotification\Notification\Notifier;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use Test\TestCase;

class NotifierTest extends TestCase {

	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;
	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $l10nFactory;

	public function setUp() {
		parent::setUp();

		$this->notificationManager = $this->getMock('OCP\Notification\IManager');
		$this->l10nFactory = $this->getMock('OCP\L10n\IFactory');
	}

	/**
	 * @param array $methods
	 * @return Notifier|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getNotifier(array $methods = []) {
		if (empty($methods)) {
			return new Notifier(
				$this->notificationManager,
				$this->l10nFactory
			);
		} {
			return $this->getMockBuilder('OCA\UpdateNotification\Notification\Notifier')
				->setConstructorArgs([
					$this->notificationManager,
					$this->l10nFactory,
				])
				->setMethods($methods)
				->getMock();
		}
	}

	public function dataUpdateAlreadyInstalledCheck() {
		return [
			['1.1.0', '1.0.0', false],
			['1.1.0', '1.1.0', true],
			['1.1.0', '1.2.0', true],
		];
	}

	/**
	 * @dataProvider dataUpdateAlreadyInstalledCheck
	 *
	 * @param string $versionNotification
	 * @param string $versionInstalled
	 * @param bool $exception
	 */
	public function testUpdateAlreadyInstalledCheck($versionNotification, $versionInstalled, $exception) {
		$notifier = $this->getNotifier();

		$notification = $this->getMock('OCP\Notification\INotification');
		$notification->expects($this->once())
			->method('getObjectId')
			->willReturn($versionNotification);

		if ($exception) {
			$this->notificationManager->expects($this->once())
				->method('markProcessed')
				->with($notification);
		} else {
			$this->notificationManager->expects($this->never())
				->method('markProcessed');
		}

		try {
			$this->invokePrivate($notifier, 'updateAlreadyInstalledCheck', [$notification, $versionInstalled]);
			$this->assertFalse($exception);
		} catch (\Exception $e) {
			$this->assertTrue($exception);
			$this->assertInstanceOf('InvalidArgumentException', $e);
		}
	}
}
