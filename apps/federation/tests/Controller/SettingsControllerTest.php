<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Carl Schwan <carl@carlschwan.eu>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Federation\Tests\Controller;

use OCA\Federation\Controller\SettingsController;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	private SettingsController $controller;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OCP\IRequest */
	private $request;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OCP\IL10N */
	private $l10n;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OCA\Federation\TrustedServers */
	private $trustedServers;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->trustedServers = $this->getMockBuilder(TrustedServers::class)
			->disableOriginalConstructor()->getMock();

		$this->controller = new SettingsController(
			'SettingsControllerTest',
			$this->request,
			$this->l10n,
			$this->trustedServers
		);
	}

	public function testAddServer(): void {
		$this->trustedServers
			->expects($this->once())
			->method('isTrustedServer')
			->with('url')
			->willReturn(false);
		$this->trustedServers
			->expects($this->once())
			->method('isNextcloudServer')
			->with('url')
			->willReturn(true);

		$result = $this->controller->addServer('url');
		$this->assertTrue($result instanceof DataResponse);

		$data = $result->getData();
		$this->assertSame(200, $result->getStatus());
		$this->assertSame('url', $data['url']);
		$this->assertArrayHasKey('id', $data);
	}

	/**
	 * @dataProvider checkServerFails
	 */
	public function testAddServerFail(bool $isTrustedServer, bool $isNextcloud): void {
		$this->expectException(\OCP\HintException::class);

		$this->trustedServers
			->expects($this->any())
			->method('isTrustedServer')
			->with('url')
			->willReturn($isTrustedServer);
		$this->trustedServers
			->expects($this->any())
			->method('isNextcloudServer')
			->with('url')
			->willReturn($isNextcloud);

		$this->controller->addServer('url');
	}

	public function testRemoveServer(): void {
		$this->trustedServers->expects($this->once())->method('removeServer')
		->with('url');
		$result = $this->controller->removeServer('url');
		$this->assertTrue($result instanceof DataResponse);
		$this->assertSame(200, $result->getStatus());
	}

	public function testCheckServer(): void {
		$this->trustedServers
			->expects($this->once())
			->method('isTrustedServer')
			->with('url')
			->willReturn(false);
		$this->trustedServers
			->expects($this->once())
			->method('isNextcloudServer')
			->with('url')
			->willReturn(true);

		$this->assertTrue(
			$this->invokePrivate($this->controller, 'checkServer', ['url'])
		);
	}

	/**
	 * @dataProvider checkServerFails
	 */
	public function testCheckServerFail(bool $isTrustedServer, bool $isNextcloud): void {
		$this->expectException(\OCP\HintException::class);

		$this->trustedServers
			->expects($this->any())
			->method('isTrustedServer')
			->with('url')
			->willReturn($isTrustedServer);
		$this->trustedServers
			->expects($this->any())
			->method('isNextcloudServer')
			->with('url')
			->willReturn($isNextcloud);

		$this->assertTrue(
			$this->invokePrivate($this->controller, 'checkServer', ['url'])
		);
	}

	/**
	 * Data to simulate checkServer fails
	 */
	public function checkServerFails(): array {
		return [
			[true, true],
			[false, false]
		];
	}
}
