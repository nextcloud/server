<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Core\Controller\NavigationController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use Test\TestCase;

class NavigationControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;

	/** @var INavigationManager|\PHPUnit\Framework\MockObject\MockObject */
	private $navigationManager;

	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;

	/** @var NavigationController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->controller = new NavigationController(
			'core',
			$this->request,
			$this->navigationManager,
			$this->urlGenerator
		);
	}

	public function dataGetNavigation() {
		return [
			[false], [true]
		];
	}
	/** @dataProvider dataGetNavigation */
	public function testGetAppNavigation($absolute): void {
		$this->navigationManager->expects($this->once())
			->method('getAll')
			->with('link')
			->willReturn(['files' => ['id' => 'files', 'href' => '/index.php/apps/files', 'icon' => 'icon' ] ]);
		if ($absolute) {
			$this->urlGenerator->expects($this->any())
				->method('getBaseURL')
				->willReturn('http://localhost/');
			$this->urlGenerator->expects($this->exactly(2))
				->method('getAbsoluteURL')
				->withConsecutive(['/index.php/apps/files'], ['icon'])
				->willReturnOnConsecutiveCalls(
					'http://localhost/index.php/apps/files',
					'http://localhost/icon'
				);
			$actual = $this->controller->getAppsNavigation($absolute);
			$this->assertInstanceOf(DataResponse::class, $actual);
			$this->assertEquals('http://localhost/index.php/apps/files', $actual->getData()[0]['href']);
			$this->assertEquals('http://localhost/icon', $actual->getData()[0]['icon']);
		} else {
			$actual = $this->controller->getAppsNavigation($absolute);
			$this->assertInstanceOf(DataResponse::class, $actual);
			$this->assertEquals('/index.php/apps/files', $actual->getData()[0]['href']);
			$this->assertEquals('icon', $actual->getData()[0]['icon']);
		}
	}

	/** @dataProvider dataGetNavigation */
	public function testGetSettingsNavigation($absolute): void {
		$this->navigationManager->expects($this->once())
			->method('getAll')
			->with('settings')
			->willReturn(['settings' => ['id' => 'settings', 'href' => '/index.php/settings/user', 'icon' => '/core/img/settings.svg'] ]);
		if ($absolute) {
			$this->urlGenerator->expects($this->any())
				->method('getBaseURL')
				->willReturn('http://localhost/');
			$this->urlGenerator->expects($this->exactly(2))
				->method('getAbsoluteURL')
				->withConsecutive(
					['/index.php/settings/user'],
					['/core/img/settings.svg']
				)
				->willReturnOnConsecutiveCalls(
					'http://localhost/index.php/settings/user',
					'http://localhost/core/img/settings.svg'
				);
			$actual = $this->controller->getSettingsNavigation($absolute);
			$this->assertInstanceOf(DataResponse::class, $actual);
			$this->assertEquals('http://localhost/index.php/settings/user', $actual->getData()[0]['href']);
			$this->assertEquals('http://localhost/core/img/settings.svg', $actual->getData()[0]['icon']);
		} else {
			$actual = $this->controller->getSettingsNavigation($absolute);
			$this->assertInstanceOf(DataResponse::class, $actual);
			$this->assertEquals('/index.php/settings/user', $actual->getData()[0]['href']);
			$this->assertEquals('/core/img/settings.svg', $actual->getData()[0]['icon']);
		}
	}

	public function testGetAppNavigationEtagMatch(): void {
		$navigation = [ ['id' => 'files', 'href' => '/index.php/apps/files', 'icon' => 'icon' ] ];
		$this->request->expects($this->once())
			->method('getHeader')
			->with('If-None-Match')
			->willReturn(md5(json_encode($navigation)));
		$this->navigationManager->expects($this->once())
			->method('getAll')
			->with('link')
			->willReturn($navigation);
		$actual = $this->controller->getAppsNavigation();
		$this->assertInstanceOf(DataResponse::class, $actual);
		$this->assertEquals(Http::STATUS_NOT_MODIFIED, $actual->getStatus());
	}

	public function testGetSettingsNavigationEtagMatch(): void {
		$navigation = [ ['id' => 'logout', 'href' => '/index.php/apps/files', 'icon' => 'icon' ] ];
		$this->request->expects($this->once())
			->method('getHeader')
			->with('If-None-Match')
			->willReturn(md5(json_encode([ ['id' => 'logout', 'href' => 'logout', 'icon' => 'icon' ] ])));
		$this->navigationManager->expects($this->once())
			->method('getAll')
			->with('settings')
			->willReturn($navigation);
		$actual = $this->controller->getSettingsNavigation();
		$this->assertInstanceOf(DataResponse::class, $actual);
		$this->assertEquals(Http::STATUS_NOT_MODIFIED, $actual->getStatus());
	}
}
