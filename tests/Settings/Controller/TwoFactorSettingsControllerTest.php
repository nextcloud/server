<?php
/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace Tests\Settings\Controller;

use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OC\Settings\Controller\TwoFactorSettingsController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TwoFactorSettingsControllerTest extends TestCase {

	/** @var IRequest|MockObject */
	private $request;

	/** @var MandatoryTwoFactor|MockObject */
	private $mandatoryTwoFactor;

	/** @var TwoFactorSettingsController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->mandatoryTwoFactor = $this->createMock(MandatoryTwoFactor::class);

		$this->controller = new TwoFactorSettingsController(
			'settings',
			$this->request,
			$this->mandatoryTwoFactor
		);
	}

	public function testIndex() {
		$this->mandatoryTwoFactor->expects($this->once())
			->method('isEnforced')
			->willReturn(true);
		$expected = new JSONResponse([
			'enabled' => true,
		]);

		$resp = $this->controller->index();

		$this->assertEquals($expected, $resp);
	}

	public function testUpdate() {
		$this->mandatoryTwoFactor->expects($this->once())
			->method('setEnforced')
			->with(true);
		$expected = new JSONResponse([
			'enabled' => true,
		]);

		$resp = $this->controller->update(true);

		$this->assertEquals($expected, $resp);
	}

}
