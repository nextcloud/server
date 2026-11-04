<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Icons;

use OC\Icons\IconManager;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class IconManagerTest extends TestCase {
	private IURLGenerator&MockObject $urlGenerator;
	private string $imagePath;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->urlGenerator->method('getWebroot')
			->willReturn('/nextcloud');
		$this->urlGenerator->method('getAbsoluteURL')
			->willReturnCallback(fn (string $url): string => 'https://cloud.example.com' . $url);

		$this->imagePath = Server::get(ITempManager::class)->getTemporaryFolder();
		mkdir($this->imagePath . '/icons');
		file_put_contents($this->imagePath . '/icons/close.svg', '<svg/>');
		file_put_contents($this->imagePath . '/icons/star_filled.svg', '<svg/>');
		file_put_contents($this->imagePath . '/icons.json', json_encode([
			'hash' => 'abc12345',
			'sprite' => ['close', '../invalid', 42],
		]));
	}

	public static function dataIconExists(): array {
		return [
			'existing icon' => ['close', true],
			'existing icon with underscore' => ['star_filled', true],
			'unknown icon' => ['unknown', false],
			'path traversal' => ['../icons/close', false],
			'uppercase' => ['Close', false],
			'empty' => ['', false],
		];
	}

	#[DataProvider('dataIconExists')]
	public function testIconExists(string $name, bool $expected): void {
		$manager = new IconManager($this->urlGenerator, $this->imagePath);
		self::assertSame($expected, $manager->iconExists($name));
	}

	public function testGetIconUrl(): void {
		$manager = new IconManager($this->urlGenerator, $this->imagePath);
		self::assertSame('/nextcloud/core/img/icons/close.svg?v=abc12345', $manager->getIconUrl('close'));
		self::assertSame('https://cloud.example.com/nextcloud/core/img/icons/close.svg?v=abc12345', $manager->getIconUrl('close', true));
		self::assertNull($manager->getIconUrl('unknown'));
	}

	public function testGetInitialState(): void {
		$manager = new IconManager($this->urlGenerator, $this->imagePath);
		self::assertSame([
			'iconUrl' => '/nextcloud/core/img/icons/{name}.svg?v=abc12345',
			'spriteUrl' => '/nextcloud/core/img/icons.svg?v=abc12345',
			'spriteIcons' => ['close'],
		], $manager->getInitialState());
	}

	public function testGetInitialStateWithoutManifest(): void {
		unlink($this->imagePath . '/icons.json');
		$manager = new IconManager($this->urlGenerator, $this->imagePath);
		self::assertSame([
			'iconUrl' => '/nextcloud/core/img/icons/{name}.svg?v=',
			'spriteUrl' => '/nextcloud/core/img/icons.svg?v=',
			'spriteIcons' => [],
		], $manager->getInitialState());
	}

	public function testUsesShippedIcons(): void {
		$manager = new IconManager($this->urlGenerator);
		$state = $manager->getInitialState();

		self::assertNotEmpty($state['spriteIcons']);
		self::assertMatchesRegularExpression('/\?v=[0-9a-f]{8}$/', $state['spriteUrl']);
		foreach ($state['spriteIcons'] as $name) {
			self::assertTrue($manager->iconExists($name), "Sprite icon $name has no single icon file");
		}
	}
}
