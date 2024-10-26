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
use OCP\IServerContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use stdClass;
use function json_encode;

class InitialStateServiceTest extends TestCase {
	/** @var InitialStateService */
	private $service;
	/** @var MockObject|LoggerInterface|(LoggerInterface&MockObject) */
	protected $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->service = new InitialStateService(
			$this->logger,
			$this->createMock(Coordinator::class),
			$this->createMock(IServerContainer::class)
		);
	}

	public function staticData(): array {
		return [
			['string'],
			[23],
			[2.3],
			[new class implements JsonSerializable {
				public function jsonSerialize(): int {
					return 3;
				}
			}],
		];
	}

	/**
	 * @dataProvider staticData
	 */
	public function testStaticData(mixed $value): void {
		$this->service->provideInitialState('test', 'key', $value);
		$data = $this->service->getInitialStates();

		$this->assertEquals(
			['test-key' => json_encode($value)],
			$data
		);
	}

	public function testValidDataButFailsToJSONEncode(): void {
		$this->logger->expects($this->once())
			->method('error');

		$this->service->provideInitialState('test', 'key', ['upload' => INF]);
		$data = $this->service->getInitialStates();

		$this->assertEquals(
			[],
			$data
		);
	}

	public function testStaticButInvalidData(): void {
		$this->logger->expects($this->once())
			->method('warning');

		$this->service->provideInitialState('test', 'key', new stdClass());
		$data = $this->service->getInitialStates();

		$this->assertEquals(
			[],
			$data
		);
	}

	/**
	 * @dataProvider staticData
	 */
	public function testLazyData(mixed $value): void {
		$this->service->provideLazyInitialState('test', 'key', function () use ($value) {
			return $value;
		});
		$data = $this->service->getInitialStates();

		$this->assertEquals(
			['test-key' => json_encode($value)],
			$data
		);
	}
}
