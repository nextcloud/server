<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Controller;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OCA\Settings\Controller\TwoFactorSettingsController;
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

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->mandatoryTwoFactor = $this->createMock(MandatoryTwoFactor::class);

		$this->controller = new TwoFactorSettingsController(
			'settings',
			$this->request,
			$this->mandatoryTwoFactor
		);
	}

	public function testIndex(): void {
		$state = new EnforcementState(true);
		$this->mandatoryTwoFactor->expects($this->once())
			->method('getState')
			->willReturn($state);
		$expected = new JSONResponse($state);

		$resp = $this->controller->index();

		$this->assertEquals($expected, $resp);
	}

	public function testUpdate(): void {
		$state = new EnforcementState(true);
		$this->mandatoryTwoFactor->expects($this->once())
			->method('setState')
			->with($this->equalTo(new EnforcementState(true)));
		$this->mandatoryTwoFactor->expects($this->once())
			->method('getState')
			->willReturn($state);
		$expected = new JSONResponse($state);

		$resp = $this->controller->update(true);

		$this->assertEquals($expected, $resp);
	}
}
