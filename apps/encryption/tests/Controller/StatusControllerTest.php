<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
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


namespace OCA\Encryption\Tests\Controller;


use OCA\Encryption\Controller\StatusController;
use OCA\Encryption\Session;
use Test\TestCase;

class StatusControllerTest extends TestCase {

	/** @var \OCP\IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $requestMock;

	/** @var \OCP\IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10nMock;

	/** @var  \OCA\Encryption\Session | \PHPUnit_Framework_MockObject_MockObject */
	protected $sessionMock;

	/** @var StatusController */
	protected $controller;

	protected function setUp() {

		parent::setUp();

		$this->sessionMock = $this->getMockBuilder('OCA\Encryption\Session')
			->disableOriginalConstructor()->getMock();
		$this->requestMock = $this->getMock('OCP\IRequest');

		$this->l10nMock = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->l10nMock->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($message) {
				return $message;
			}));

		$this->controller = new StatusController('encryptionTest',
			$this->requestMock,
			$this->l10nMock,
			$this->sessionMock);

	}

	/**
	 * @dataProvider dataTestGetStatus
	 *
	 * @param string $status
	 * @param string $expectedStatus
	 */
	public function testGetStatus($status, $expectedStatus) {
		$this->sessionMock->expects($this->once())
			->method('getStatus')->willReturn($status);
		$result = $this->controller->getStatus();
		$data = $result->getData();
		$this->assertSame($expectedStatus, $data['status']);
	}

	public function dataTestGetStatus() {
		return array(
			array(Session::RUN_MIGRATION, 'interactionNeeded'),
			array(Session::INIT_EXECUTED, 'interactionNeeded'),
			array(Session::INIT_SUCCESSFUL, 'success'),
			array(Session::NOT_INITIALIZED, 'interactionNeeded'),
			array('unknown', 'error'),
		);
	}
}
