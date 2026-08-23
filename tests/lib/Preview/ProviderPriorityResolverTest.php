<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\HEIC;
use OC\Preview\Image;
use OC\Preview\Imaginary;
use OC\Preview\JPEG;
use OC\Preview\PNG;
use OC\Preview\PreviewAdminConfig;
use OC\Preview\ProviderPriorityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ProviderPriorityResolverTest extends TestCase {
	private PreviewAdminConfig&MockObject $adminConfig;
	private ProviderPriorityResolver $resolver;

	protected function setUp(): void {
		parent::setUp();
		$this->adminConfig = $this->createMock(PreviewAdminConfig::class);
		$this->resolver = new ProviderPriorityResolver($this->adminConfig);
	}

	public function testNoOverridesUsesGlobalOrder(): void {
		$this->adminConfig->method('getEnabledPreviewProviders')->willReturn([PNG::class, JPEG::class]);
		$this->adminConfig->method('getMimePriority')->willReturn([]);
		$this->adminConfig->method('getMimeDeny')->willReturn([]);

		$this->assertSame(
			[PNG::class, JPEG::class],
			$this->resolver->sortMatchingProviders('image/png', [JPEG::class, PNG::class]),
		);
	}

	public function testHeicOverrideImaginaryThenHeic(): void {
		$this->adminConfig->method('getEnabledPreviewProviders')->willReturn([HEIC::class, Imaginary::class, PNG::class]);
		$this->adminConfig->method('getMimePriority')->willReturn([
			'image/heic' => [Imaginary::class, HEIC::class],
		]);
		$this->adminConfig->method('getMimeDeny')->willReturn([]);

		$this->assertSame(
			[Imaginary::class, HEIC::class],
			$this->resolver->sortMatchingProviders('image/heic', [HEIC::class, Imaginary::class]),
		);
	}

	public function testDisabledProviderInOverrideIsSkipped(): void {
		$this->adminConfig->method('getEnabledPreviewProviders')->willReturn([HEIC::class]);
		$this->adminConfig->method('getMimePriority')->willReturn([
			'image/heic' => [Imaginary::class, HEIC::class],
		]);
		$this->adminConfig->method('getMimeDeny')->willReturn([]);

		$this->assertSame(
			[HEIC::class],
			$this->resolver->sortMatchingProviders('image/heic', [HEIC::class]),
		);
	}

	public function testMimeDenyExcludesProvider(): void {
		$this->adminConfig->method('getEnabledPreviewProviders')->willReturn([Image::class, Imaginary::class, HEIC::class]);
		$this->adminConfig->method('getMimePriority')->willReturn([]);
		$this->adminConfig->method('getMimeDeny')->willReturn([
			'image/heic' => [Image::class],
		]);

		$this->assertSame(
			[Imaginary::class, HEIC::class],
			$this->resolver->sortMatchingProviders('image/heic', [Image::class, Imaginary::class, HEIC::class]),
		);
	}

	public function testUnknownMimeFallsBackToGlobalList(): void {
		$this->adminConfig->method('getEnabledPreviewProviders')->willReturn([PNG::class, JPEG::class]);
		$this->adminConfig->method('getMimePriority')->willReturn([
			'image/heic' => [Imaginary::class],
		]);
		$this->adminConfig->method('getMimeDeny')->willReturn([]);

		$this->assertSame(
			[PNG::class, JPEG::class],
			$this->resolver->sortMatchingProviders('image/unknown', [JPEG::class, PNG::class]),
		);
	}

	public function testEmptyOverrideDoesNotWipeGlobalProviders(): void {
		$this->adminConfig->method('getEnabledPreviewProviders')->willReturn([PNG::class, JPEG::class]);
		$this->adminConfig->method('getMimePriority')->willReturn([
			'image/jpeg' => [],
		]);
		$this->adminConfig->method('getMimeDeny')->willReturn([]);

		$this->assertSame(
			[PNG::class, JPEG::class],
			$this->resolver->sortMatchingProviders('image/jpeg', [JPEG::class, PNG::class]),
		);
	}
}
