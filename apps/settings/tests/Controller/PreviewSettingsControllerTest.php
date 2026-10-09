<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OCA\Settings\Controller\PreviewSettingsController;
use OCA\Settings\Service\PreviewSettingsService;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PreviewSettingsControllerTest extends TestCase {
	private PreviewSettingsService&MockObject $service;
	private PreviewSettingsController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->service = $this->createMock(PreviewSettingsService::class);
		$l = $this->createMock(IL10N::class);
		$l->method('t')->willReturnArgument(0);

		$this->controller = new PreviewSettingsController('settings', $this->createMock(IRequest::class), $this->service, $l);
	}

	public function testUpdateSettingsReturnsTheNewState(): void {
		$state = ['enabled' => true];
		$this->service->expects($this->once())->method('setSettings')->with(true, 1024, null, null, null, 90, null, null, null, 7);
		$this->service->method('getSettings')->willReturn($state);

		$response = $this->controller->updateSettings(true, 1024, null, null, null, 90, null, null, null, 7);

		$this->assertSame($state, $response->getData());
	}

	public function testInvalidValuesAreABadRequest(): void {
		$this->service->method('setSettings')->willThrowException(new \InvalidArgumentException('maxX is out of range'));

		$this->expectException(OCSBadRequestException::class);
		$this->controller->updateSettings(true, 0, null, null, null, null, null, null, null, null);
	}

	public function testUnknownProvidersAreABadRequest(): void {
		$this->service->method('setProviders')->willThrowException(new \InvalidArgumentException('Unknown preview provider'));

		$this->expectException(OCSBadRequestException::class);
		$this->controller->updateProviders(['OCA\\Evil\\Provider']);
	}

	public function testReadOnlyConfigIsForbidden(): void {
		$this->service->method('isConfigReadOnly')->willReturn(true);
		$this->service->expects($this->never())->method('setSettings');
		$this->service->expects($this->never())->method('setProviders');
		$this->service->expects($this->never())->method('resetProviders');

		foreach ([
			fn () => $this->controller->updateSettings(true, null, null, null, null, null, null, null, null, null),
			fn () => $this->controller->updateProviders([]),
			fn () => $this->controller->resetProviders(),
		] as $call) {
			try {
				$call();
				$this->fail('Expected the read-only config to block the write');
			} catch (OCSForbiddenException) {
			}
		}
	}
}
