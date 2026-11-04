<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use JsonSerializable;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\InitialStateService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use function json_encode;

class InitialStateServiceTest extends TestCase {
	private InitialStateService $service;
	protected LoggerInterface&MockObject $logger;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->service = new InitialStateService(
			$this->logger,
			$this->createMock(Coordinator::class),
			$this->createMock(ContainerInterface::class)
		);
	}

	public static function staticData(): array {
		return [
			['string'],
			[23],
			[2.3],
			[new class implements JsonSerializable {
				#[\Override]
				public function jsonSerialize(): int {
					return 3;
				}
			}],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('staticData')]
	public function testStaticData(mixed $value): void {
		$this->service->provideInitialState('test', 'key', $value);
		$data = $this->service->getInitialStatesJson();

		$this->assertEquals(
			['test' => ['key' => json_decode(json_encode($value))]],
			json_decode($data, true, flags: JSON_THROW_ON_ERROR)
		);
	}

	public function testValidDataButFailsToJSONEncode(): void {
		$this->logger->expects($this->once())
			->method('error');

		$this->service->provideInitialState('test', 'key', ['upload' => INF]);
		$data = $this->service->getInitialStatesJson();

		$this->assertSame('{}', $data);
	}

	public function testStaticButInvalidData(): void {
		$this->logger->expects($this->once())
			->method('warning');

		$this->service->provideInitialState('test', 'key', new stdClass());
		$data = $this->service->getInitialStatesJson();

		$this->assertSame('{}', $data);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('staticData')]
	public function testLazyData(mixed $value): void {
		$this->service->provideLazyInitialState('test', 'key', function () use ($value) {
			return $value;
		});
		$data = $this->service->getInitialStatesJson();

		$this->assertEquals(
			['test' => ['key' => json_decode(json_encode($value))]],
			json_decode($data, true, flags: JSON_THROW_ON_ERROR)
		);
	}

	public function testGroupsByAppAndKey(): void {
		$this->service->provideInitialState('core', 'config', ['version' => '36.0.0']);
		$this->service->provideInitialState('unified-search', 'live-search', true);
		$this->service->provideInitialState('core', 'min-search-length', 1);
		$this->service->provideInitialState('app', '0', 'numeric key');

		$this->assertSame(
			'{"core":{"config":{"version":"36.0.0"},"min-search-length":1},"unified-search":{"live-search":true},"app":{"0":"numeric key"}}',
			$this->service->getInitialStatesJson()
		);
	}

	public function testSafeToEmbedInScriptElement(): void {
		$this->service->provideInitialState('test', 'html', '</script><!--<script>');
		$this->service->provideInitialState('<app>', '<key>', ['<b>' => '>']);
		$data = $this->service->getInitialStatesJson();

		$this->assertStringNotContainsString('<', $data);
		$this->assertStringNotContainsString('>', $data);
		$this->assertSame(
			['test' => ['html' => '</script><!--<script>'], '<app>' => ['<key>' => ['<b>' => '>']]],
			json_decode($data, true, flags: JSON_THROW_ON_ERROR)
		);
	}

	public function testNoInitialState(): void {
		$this->assertSame('{}', $this->service->getInitialStatesJson());
	}
}
