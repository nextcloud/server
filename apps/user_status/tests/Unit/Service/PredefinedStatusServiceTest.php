<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Service;

use OCA\UserStatus\Service\PredefinedStatusService;
use OCP\IL10N;
use Test\TestCase;

class PredefinedStatusServiceTest extends TestCase {

	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	protected $l10n;

	/** @var PredefinedStatusService */
	protected $service;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);

		$this->service = new PredefinedStatusService($this->l10n);
	}

	public function testGetDefaultStatuses(): void {
		$this->l10n->expects($this->exactly(7))
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$actual = $this->service->getDefaultStatuses();
		$this->assertEquals([
			[
				'id' => 'meeting',
				'icon' => '📅',
				'message' => 'In a meeting',
				'clearAt' => [
					'type' => 'period',
					'time' => 3600,
				],
			],
			[
				'id' => 'commuting',
				'icon' => '🚌',
				'message' => 'Commuting',
				'clearAt' => [
					'type' => 'period',
					'time' => 1800,
				],
			],
			[
				'id' => 'remote-work',
				'icon' => '🏡',
				'message' => 'Working remotely',
				'clearAt' => [
					'type' => 'end-of',
					'time' => 'day',
				],
			],
			[
				'id' => 'sick-leave',
				'icon' => '🤒',
				'message' => 'Out sick',
				'clearAt' => [
					'type' => 'end-of',
					'time' => 'day',
				],
			],
			[
				'id' => 'vacationing',
				'icon' => '🌴',
				'message' => 'Vacationing',
				'clearAt' => null,
			],
			[
				'id' => 'call',
				'icon' => '💬',
				'message' => 'In a call',
				'clearAt' => null,
				'visible' => false,
			],
			[
				'id' => 'out-of-office',
				'icon' => '🛑',
				'message' => 'Out of office',
				'clearAt' => null,
				'visible' => false,
			],
		], $actual);
	}

	/**
	 * @param string $id
	 * @param string|null $expectedIcon
	 *
	 * @dataProvider getIconForIdDataProvider
	 */
	public function testGetIconForId(string $id, ?string $expectedIcon): void {
		$actual = $this->service->getIconForId($id);
		$this->assertEquals($expectedIcon, $actual);
	}

	/**
	 * @return array
	 */
	public function getIconForIdDataProvider(): array {
		return [
			['meeting', '📅'],
			['commuting', '🚌'],
			['sick-leave', '🤒'],
			['vacationing', '🌴'],
			['remote-work', '🏡'],
			['call', '💬'],
			['unknown-id', null],
		];
	}

	/**
	 * @param string $id
	 * @param string|null $expected
	 *
	 * @dataProvider getTranslatedStatusForIdDataProvider
	 */
	public function testGetTranslatedStatusForId(string $id, ?string $expected): void {
		$this->l10n->method('t')
			->willReturnArgument(0);

		$actual = $this->service->getTranslatedStatusForId($id);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @return array
	 */
	public function getTranslatedStatusForIdDataProvider(): array {
		return [
			['meeting', 'In a meeting'],
			['commuting', 'Commuting'],
			['sick-leave', 'Out sick'],
			['vacationing', 'Vacationing'],
			['remote-work', 'Working remotely'],
			['call', 'In a call'],
			['unknown-id', null],
		];
	}

	/**
	 * @param string $id
	 * @param bool $expected
	 *
	 * @dataProvider isValidIdDataProvider
	 */
	public function testIsValidId(string $id, bool $expected): void {
		$actual = $this->service->isValidId($id);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @return array
	 */
	public function isValidIdDataProvider(): array {
		return [
			['meeting', true],
			['commuting', true],
			['sick-leave', true],
			['vacationing', true],
			['remote-work', true],
			['call', true],
			['unknown-id', false],
		];
	}

	public function testGetDefaultStatusById(): void {
		$this->l10n->expects($this->exactly(7))
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$this->assertEquals([
			'id' => 'call',
			'icon' => '💬',
			'message' => 'In a call',
			'clearAt' => null,
			'visible' => false,
		], $this->service->getDefaultStatusById('call'));
	}

	public function testGetDefaultStatusByUnknownId(): void {
		$this->assertNull($this->service->getDefaultStatusById('unknown'));
	}
}
