<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Command;

use OCA\Theming\Command\UpdateConfig;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class UpdateConfigTest extends TestCase {
	private ThemingDefaults&MockObject $themingDefaults;
	private ImageManager&MockObject $imageManager;
	private IConfig&MockObject $config;
	private CommandTester $cmd;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->config = $this->createMock(IConfig::class);

		$command = new UpdateConfig($this->themingDefaults, $this->imageManager, $this->config);
		$this->cmd = new CommandTester($command);
	}

	public function testReadRegularKeyThatIsSet(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', '')
			->willReturn('My Cloud');

		$this->cmd->execute(['key' => 'name']);

		$this->assertStringContainsString('name is currently set to My Cloud', $this->cmd->getDisplay());
	}

	public function testReadImageKeyThatIsSetReadsFromMimeSuffixedStorageKey(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('image/png');

		$this->cmd->execute(['key' => 'logo']);

		$this->assertStringContainsString('logo is currently set to image/png', $this->cmd->getDisplay());
	}

	public function testReadImageKeyThatIsNotSet(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('');

		$this->cmd->execute(['key' => 'logo']);

		$this->assertStringContainsString('logo is currently not set', $this->cmd->getDisplay());
	}
}
