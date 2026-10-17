<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OCA\Settings\Controller\TwoFactorSettingsController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TwoFactorSettingsControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private MandatoryTwoFactor&MockObject $mandatoryTwoFactor;
	private IEventDispatcher&MockObject $eventDispatcher;
	private TwoFactorSettingsController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->mandatoryTwoFactor = $this->createMock(MandatoryTwoFactor::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->controller = new TwoFactorSettingsController(
			'settings',
			$this->request,
			$this->mandatoryTwoFactor,
			$this->eventDispatcher,
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

	public function testUpdateIsAudited(): void {
		$this->mandatoryTwoFactor->method('getState')
			->willReturn(new EnforcementState(true, ['admin', 'staff'], ['guests']));
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'auth.twofactor_enforcement.updated'
				&& $event->getParameters() === ['enforced' => 'enabled', 'enforcedGroups' => 'admin, staff', 'excludedGroups' => 'guests']
				&& $event->getLogMessage() === 'Two-factor authentication enforcement was set to "%s" with enforced groups "%s" and excluded groups "%s"'));

		$this->controller->update(true, ['admin', 'staff'], ['guests']);
	}
}
