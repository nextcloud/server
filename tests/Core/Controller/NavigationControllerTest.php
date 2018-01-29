<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Tests\Core\Controller;

use OC\Core\Controller\NavigationController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\INavigationManager;
use OCP\IRequest;
use Test\TestCase;

class NavigationControllerTest extends TestCase {

	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;

	/** @var INavigationManager|\PHPUnit_Framework_MockObject_MockObject */
	private $navigationManager;

	/** @var NavigationController */
	private $controller;

	public function setUp() {
		parent::setUp();


		$this->request = $this->createMock(IRequest::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);

		$this->controller = new NavigationController(
			'core',
			$this->request,
			$this->navigationManager
		);
	}

	public function dataGetNavigation() {
		return [
			[false], [true]
		];
	}
	/** @dataProvider dataGetNavigation */
	public function testGetAppNavigation($absolute) {
		$this->navigationManager->expects($this->once())
			->method('getAll')
			->with('link', $absolute);
		$this->assertInstanceOf(JSONResponse::class, $this->controller->getAppsNavigation($absolute));
	}

	/** @dataProvider dataGetNavigation */
	public function testGetSettingsNavigation($absolute) {
		$this->navigationManager->expects($this->once())
			->method('getAll')
			->with('settings', $absolute);
		$this->assertInstanceOf(JSONResponse::class, $this->controller->getSettingsNavigation($absolute));
	}

}
