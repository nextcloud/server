<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OC\Preview\PreviewAdminConfig;
use OCA\Settings\Controller\PreviewAdminController;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PreviewAdminControllerTest extends TestCase {
	private PreviewAdminConfig&MockObject $adminConfig;
	private PreviewAdminController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->adminConfig = $this->createMock(PreviewAdminConfig::class);
		$this->controller = new PreviewAdminController(
			'settings',
			$this->createMock(IRequest::class),
			$this->adminConfig,
			$this->createMock(IClientService::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testUpdatePersistsValidPayload(): void {
		$payload = ['enablePreviews' => true, 'previewMaxX' => 2048];
		$this->adminConfig->expects($this->once())->method('setSettings')->with($payload);
		$this->adminConfig->method('getSettings')->willReturn($payload + ['enablePreviews' => true]);

		$response = $this->controller->update($payload);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertTrue($response->getData()['enablePreviews']);
	}

	public function testUpdateRejectsInvalidPayload(): void {
		$this->adminConfig->method('setSettings')->willThrowException(new \InvalidArgumentException('Refusing to write an empty preview provider list'));
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Refusing to write an empty preview provider list');
		$this->controller->update(['enabledPreviewProviders' => []]);
	}

	public function testTestImaginaryRejectsGarbageUrl(): void {
		$this->adminConfig->method('validateImaginaryUrl')->willThrowException(new \InvalidArgumentException('Imaginary URL must use http or https'));
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Imaginary URL must use http or https');
		$this->controller->testImaginary('not-a-url');
	}
}
