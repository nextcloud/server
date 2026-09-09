<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCS;

use OC\OCS\CoreCapabilities;
use OCP\IConfig;
use OCP\IPreview;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CoreCapabilitiesTest extends TestCase {
	private IConfig&MockObject $config;
	private IPreview&MockObject $preview;
	private CoreCapabilities $capabilities;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->preview = $this->createMock(IPreview::class);
		$this->capabilities = new CoreCapabilities($this->config, $this->preview);
	}

	public function testEnabledPreviewProvidersAreReported(): void {
		$this->preview->method('getProviders')
			->willReturn([
				'/image\/jpeg/' => [],
				'/image\/hei(f|c)/' => [],
			]);

		$capabilities = $this->capabilities->getCapabilities();

		$this->assertSame(
			['/image\/jpeg/', '/image\/hei(f|c)/'],
			$capabilities['core']['previews']['enabled_providers'],
		);
	}

	public function testNoPreviewProvidersAreReportedWhenPreviewsAreOff(): void {
		// The manager returns nothing at all when `enable_previews` is off
		$this->preview->method('getProviders')->willReturn([]);

		$capabilities = $this->capabilities->getCapabilities();

		$this->assertSame([], $capabilities['core']['previews']['enabled_providers']);
	}
}
