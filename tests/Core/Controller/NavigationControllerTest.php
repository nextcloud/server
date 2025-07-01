<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Core\Controller\NavigationController;
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

	public static function dataGetNavigation(): array {
		return [
			[false],
			[true],
		];
	}
	/** @dataProvider dataGetNavigation */
	public function testGetAppNavigation(bool $absolute): void {
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
				->willReturnMap([
					['/index.php/apps/files', 'http://localhost/index.php/apps/files'],
					['icon', 'http://localhost/icon'],
				]);
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
	public function testGetSettingsNavigation(bool $absolute): void {
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
				->willReturnMap([
					['/index.php/settings/user', 'http://localhost/index.php/settings/user'],
					['/core/img/settings.svg', 'http://localhost/core/img/settings.svg']
				]);
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

	public function testEtagIgnoresLogout(): void {
		$navigation1 = [
			['id' => 'files', 'href' => '/index.php/apps/files', 'icon' => 'icon' ],
			['id' => 'logout', 'href' => '/index.php/logout?requesttoken=abcd', 'icon' => 'icon' ],
		];
		$navigation2 = [
			['id' => 'files', 'href' => '/index.php/apps/files', 'icon' => 'icon' ],
			['id' => 'logout', 'href' => '/index.php/logout?requesttoken=1234', 'icon' => 'icon' ],
		];
		$navigation3 = [
			['id' => 'files', 'href' => '/index.php/apps/files/test', 'icon' => 'icon' ],
			['id' => 'logout', 'href' => '/index.php/logout?requesttoken=1234', 'icon' => 'icon' ],
		];
		$this->navigationManager->expects($this->exactly(3))
			->method('getAll')
			->with('link')
			->willReturnOnConsecutiveCalls(
				$navigation1,
				$navigation2,
				$navigation3,
			);

		// Changes in the logout url should not change the ETag
		$request1 = $this->controller->getAppsNavigation();
		$request2 = $this->controller->getAppsNavigation();
		$this->assertEquals($request1->getETag(), $request2->getETag());

		// Changes in non-logout urls should result in a different ETag
		$request3 = $this->controller->getAppsNavigation();
		$this->assertNotEquals($request2->getETag(), $request3->getETag());
	}
}
