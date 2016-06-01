<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

namespace Tests\Settings\Controller;

use OC\DB\Connection;
use OC\Files\View;
use OC\Settings\Controller\EncryptionController;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use Test\TestCase;

/**
 * Class EncryptionControllerTest
 *
 * @package Tests\Settings\Controller
 */
class EncryptionControllerTest extends TestCase {
	/** @var IRequest */
	private $request;
	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var Connection */
	private $connection;
	/** @var IUserManager */
	private $userManager;
	/** @var View */
	private $view;
	/** @var ILogger */
	private $logger;
	/** @var EncryptionController */
	private $encryptionController;

	public function setUp() {
		$this->request = $this->getMockBuilder('\\OCP\\IRequest')
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->getMockBuilder('\\OCP\\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($message, array $replace) {
				return vsprintf($message, $replace);
			}));
		$this->config = $this->getMockBuilder('\\OCP\\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->connection = $this->getMockBuilder('\\OC\\DB\\Connection')
			->disableOriginalConstructor()->getMock();
		$this->userManager = $this->getMockBuilder('\\OCP\\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->view = $this->getMockBuilder('\\OC\\Files\\View')
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMockBuilder('\\OCP\\ILogger')
			->disableOriginalConstructor()->getMock();

		$this->encryptionController = $this->getMockBuilder('\\OC\\Settings\\Controller\\EncryptionController')
			->setConstructorArgs([
				'settings',
				$this->request,
				$this->l10n,
				$this->config,
				$this->connection,
				$this->userManager,
				$this->view,
				$this->logger,
			])
			->setMethods(['getMigration'])
			->getMock();
	}

	public function testStartMigrationSuccessful() {
		// we need to be able to autoload the class we're mocking
		\OC_App::registerAutoloading('encryption', \OC_App::getAppPath('encryption'));

		$migration = $this->getMockBuilder('\\OCA\\Encryption\\Migration')
			->disableOriginalConstructor()->getMock();
		$this->encryptionController
			->expects($this->once())
			->method('getMigration')
			->with($this->config, $this->view, $this->connection, $this->logger)
			->will($this->returnValue($migration));
		$migration
			->expects($this->once())
			->method('reorganizeSystemFolderStructure');
		$migration
			->expects($this->once())
			->method('updateDB');
		$backend = $this->getMockBuilder('\OCP\UserInterface')
			->getMock();
		$this->userManager
			->expects($this->once())
			->method('getBackends')
			->will($this->returnValue([$backend]));
		$backend
			->expects($this->once())
			->method('getUsers')
			->will($this->returnValue(['User 1', 'User 2']));
		$migration
			->expects($this->exactly(2))
			->method('reorganizeFolderStructureForUser')
			->withConsecutive(
				['User 1'],
				['User 2']
			);
		$migration
			->expects($this->once())
			->method('finalCleanUp');

		$expected = [
			'data' => [
				'message' => 'Migration Completed',
			],
			'status' => 'success',
		];
		$this->assertSame($expected, $this->encryptionController->startMigration());
	}

	public function testStartMigrationException() {
		$this->encryptionController
			->expects($this->once())
			->method('getMigration')
			->with($this->config, $this->view, $this->connection, $this->logger)
			->will($this->throwException(new \Exception('My error message')));

		$expected = [
			'data' => [
				'message' => 'A problem occurred, please check your log files (Error: My error message)',
			],
			'status' => 'error',
		];
		$this->assertSame($expected, $this->encryptionController->startMigration());
	}
}
