<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\UpdateNotification\Tests\Controller;

use OCA\UpdateNotification\Controller\AdminController;
use OCA\UpdateNotification\UpdateChecker;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class AdminControllerTest extends TestCase {
	/** @var IRequest */
	private $request;
	/** @var IJobList */
	private $jobList;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var IConfig */
	private $config;
	/** @var AdminController */
	private $adminController;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IL10N */
	private $l10n;
	/** @var UpdateChecker */
	private $updateChecker;
	/** @var IDateTimeFormatter */
	private $dateTimeFormatter;

	public function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('\\OCP\\IRequest')->getMock();
		$this->jobList = $this->getMockBuilder('\\OCP\\BackgroundJob\\IJobList')->getMock();
		$this->secureRandom = $this->getMockBuilder('\\OCP\\Security\\ISecureRandom')->getMock();
		$this->config = $this->getMockBuilder('\\OCP\\IConfig')->getMock();
		$this->timeFactory = $this->getMockBuilder('\\OCP\\AppFramework\\Utility\\ITimeFactory')->getMock();
		$this->l10n = $this->getMockBuilder('\\OCP\\IL10N')->getMock();
		$this->updateChecker = $this->getMockBuilder('\\OCA\\UpdateNotification\\UpdateChecker')->disableOriginalConstructor()->getMock();
		$this->dateTimeFormatter = $this->getMockBuilder('\\OCP\\IDateTimeFormatter')->getMock();

		$this->adminController = new AdminController(
			'updatenotification',
			$this->request,
			$this->jobList,
			$this->secureRandom,
			$this->config,
			$this->timeFactory,
			$this->l10n,
			$this->updateChecker,
			$this->dateTimeFormatter
		);
	}

	public function testDisplayPanelWithUpdate() {
		$channels = [
			'daily',
			'beta',
			'stable',
			'production',
		];
		$currentChannel = \OCP\Util::getChannel();

		// Remove the currently used channel from the channels list
		if(($key = array_search($currentChannel, $channels)) !== false) {
			unset($channels[$key]);
		}

		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['core', 'lastupdatedat', '', '12345'],
				['updatenotification', 'notify_groups', '["admin"]', '["admin"]'],
			]);
		$this->dateTimeFormatter
			->expects($this->once())
			->method('formatDateTime')
			->with('12345')
			->willReturn('LastCheckedReturnValue');
		$this->updateChecker
			->expects($this->once())
			->method('getUpdateState')
			->willReturn([
				'updateAvailable' => true,
				'updateVersion' => '8.1.2',
				'downloadLink' => 'https://downloads.nextcloud.org/server',
				'updaterEnabled' => true,
			]);

		$params = [
			'isNewVersionAvailable' => true,
			'lastChecked' => 'LastCheckedReturnValue',
			'currentChannel' => \OCP\Util::getChannel(),
			'channels' => $channels,
			'newVersionString' => '8.1.2',
			'downloadLink' => 'https://downloads.nextcloud.org/server',
			'updaterEnabled' => true,
			'outdatedPHP' => version_compare(PHP_VERSION, '5.6') === -1,
			'notify_groups' => 'admin',
		];

		$expected = new TemplateResponse('updatenotification', 'admin', $params, '');
		$this->assertEquals($expected, $this->adminController->displayPanel());
	}

	public function testDisplayPanelWithoutUpdate() {
		$channels = [
			'daily',
			'beta',
			'stable',
			'production',
		];
		$currentChannel = \OCP\Util::getChannel();

		// Remove the currently used channel from the channels list
		if(($key = array_search($currentChannel, $channels)) !== false) {
			unset($channels[$key]);
		}

		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['core', 'lastupdatedat', '', '12345'],
				['updatenotification', 'notify_groups', '["admin"]', '["admin"]'],
			]);
		$this->dateTimeFormatter
			->expects($this->once())
			->method('formatDateTime')
			->with('12345')
			->willReturn('LastCheckedReturnValue');
		$this->updateChecker
			->expects($this->once())
			->method('getUpdateState')
			->willReturn([]);

		$params = [
			'isNewVersionAvailable' => false,
			'lastChecked' => 'LastCheckedReturnValue',
			'currentChannel' => \OCP\Util::getChannel(),
			'channels' => $channels,
			'newVersionString' => '',
			'downloadLink' => '',
			'updaterEnabled' => 0,
			'outdatedPHP' => version_compare(PHP_VERSION, '5.6') === -1,
			'notify_groups' => 'admin',
		];

		$expected = new TemplateResponse('updatenotification', 'admin', $params, '');
		$this->assertEquals($expected, $this->adminController->displayPanel());
	}


	public function testCreateCredentials() {
		$this->jobList
			->expects($this->once())
			->method('add')
			->with('OCA\UpdateNotification\ResetTokenBackgroundJob');
		$this->secureRandom
			->expects($this->once())
			->method('generate')
			->with(64)
			->willReturn('MyGeneratedToken');
		$this->config
			->expects($this->once())
			->method('setSystemValue')
			->with('updater.secret');
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(12345);
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'updater.secret.created', 12345);

		$expected = new DataResponse('MyGeneratedToken');
		$this->assertEquals($expected, $this->adminController->createCredentials());
	}

	public function testGetSection() {
		$this->assertSame('server', $this->adminController->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(1, $this->adminController->getPriority());
	}
}
