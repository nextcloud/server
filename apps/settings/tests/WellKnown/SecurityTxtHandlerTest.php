<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\WellKnown;

use OCA\Settings\WellKnown\SecurityTxtHandler;
use OCP\AppFramework\Http\TextPlainResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityTxtHandlerTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IRequestContext&MockObject $context;
	private SecurityTxtHandler $handler;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->context = $this->createMock(IRequestContext::class);
		$this->handler = new SecurityTxtHandler($this->appConfig);
	}

	private function bodyOf(?IResponse $response): string {
		$this->assertInstanceOf(GenericResponse::class, $response);
		$inner = $response->toHttpResponse();
		$this->assertInstanceOf(TextPlainResponse::class, $inner);
		$this->assertSame(200, $inner->getStatus());
		return $inner->render();
	}

	public function testOtherServiceIsPassedThrough(): void {
		$previous = $this->createMock(IResponse::class);
		$this->appConfig->expects($this->never())->method('getValueString');

		$this->assertSame($previous, $this->handler->handle('webfinger', $this->context, $previous));
		$this->assertNull($this->handler->handle('webfinger', $this->context, null));
	}

	public static function provideUnsetValues(): array {
		return [
			'empty' => [''],
			'whitespace' => ["  \n\t "],
		];
	}

	#[DataProvider('provideUnsetValues')]
	public function testDefaultIsServedWhenNotConfigured(string $configured): void {
		$this->appConfig->method('getValueString')
			->with('settings', 'security_txt')
			->willReturn($configured);

		$body = $this->bodyOf($this->handler->handle('security.txt', $this->context, null));

		$this->assertStringContainsString('Contact: https://hackerone.com/nextcloud', $body);
		$this->assertStringEndsWith("\n", $body);
	}

	public function testConfiguredValueIsServed(): void {
		$this->appConfig->method('getValueString')
			->with('settings', 'security_txt')
			->willReturn("Contact: mailto:security@example.com\nExpires: 2030-01-01T00:00:00.000Z");

		$body = $this->bodyOf($this->handler->handle('security.txt', $this->context, null));

		$this->assertSame("Contact: mailto:security@example.com\nExpires: 2030-01-01T00:00:00.000Z\n", $body);
	}

	public function testConfiguredValueKeepsSingleTrailingNewline(): void {
		$this->appConfig->method('getValueString')
			->willReturn("Contact: mailto:security@example.com\n\n");

		$body = $this->bodyOf($this->handler->handle('security.txt', $this->context, null));

		$this->assertSame("Contact: mailto:security@example.com\n", $body);
	}
}
