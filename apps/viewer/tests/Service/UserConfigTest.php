<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests\Service;

use OCA\Viewer\Service\UserConfig;
use OCP\Config\IUserConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class UserConfigTest extends TestCase {
	private UserConfig $config;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createInstanceWithMocks(UserConfig::class);
	}

	private function signIn(): void {
		$user = $this->createStub(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->getAutoMock(IUserSession::class)->method('getUser')->willReturn($user);
	}

	public static function dataInRange(): array {
		return [
			'the shortest delay' => ['slideshow_delay', 1],
			'a delay' => ['slideshow_delay', 10],
			'the longest delay' => ['slideshow_delay', 60],
			'no volume' => ['volume', 0],
			'full volume' => ['volume', 100],
		];
	}

	#[DataProvider('dataInRange')]
	public function testKeepsANumberInRange(string $key, int $value): void {
		$this->signIn();
		$this->getAutoMock(IUserConfig::class)->expects($this->once())
			->method('setValueInt')
			->with('alice', 'viewer', $key, $value);

		$this->config->setConfig($key, $value);
	}

	public function testKeepsAToggle(): void {
		$this->signIn();
		$this->getAutoMock(IUserConfig::class)->expects($this->once())
			->method('setValueBool')
			->with('alice', 'viewer', 'muted', true);

		$this->config->setConfig('muted', true);
	}

	public static function dataRefused(): array {
		return [
			'too short' => ['slideshow_delay', 0, 'Invalid config value'],
			'too long' => ['slideshow_delay', 61, 'Invalid config value'],
			'too loud' => ['volume', 101, 'Invalid config value'],
			'a toggle for a number' => ['volume', true, 'Invalid config value'],
			'a number for a toggle' => ['muted', 1, 'Invalid config value'],
			'an unknown setting' => ['theme', 1, 'Unknown config key'],
		];
	}

	#[DataProvider('dataRefused')]
	public function testRefusesWhatItDoesNotKnow(string $key, int|bool $value, string $message): void {
		$this->signIn();
		$this->getAutoMock(IUserConfig::class)->expects($this->never())->method('setValueInt');
		$this->getAutoMock(IUserConfig::class)->expects($this->never())->method('setValueBool');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage($message);
		$this->config->setConfig($key, $value);
	}

	public function testGivesTheDefaultsForWhatWasNeverSet(): void {
		$this->signIn();
		$this->getAutoMock(IUserConfig::class)->method('getValueInt')->willReturnArgument(3);
		$this->getAutoMock(IUserConfig::class)->method('getValueBool')->willReturnArgument(3);

		$this->assertSame(['slideshow_delay' => 5, 'volume' => 100, 'muted' => false], $this->config->getConfigs());
	}

	public function testNeedsAUser(): void {
		$this->expectException(\RuntimeException::class);
		$this->config->getConfigs();
	}
}
