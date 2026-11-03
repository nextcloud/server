<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\AVIF;
use OC\Preview\BMP;
use OC\Preview\CDR;
use OC\Preview\GIF;
use OC\Preview\HEIC;
use OC\Preview\Imaginary;
use OC\Preview\JPEG;
use OC\Preview\Krita;
use OC\Preview\MarkDown;
use OC\Preview\OpenDocument;
use OC\Preview\PNG;
use OC\Preview\PreviewProviderDefaults;
use OC\Preview\TXT;
use OC\Preview\WebP;
use OC\Preview\XBitmap;
use Test\TestCase;

class PreviewProviderDefaultsTest extends TestCase {
	private const LEGACY_DEFAULTS = [
		MarkDown::class,
		TXT::class,
		OpenDocument::class,
		PNG::class,
		JPEG::class,
		GIF::class,
		BMP::class,
		XBitmap::class,
		Krita::class,
		WebP::class,
		CDR::class,
		AVIF::class,
	];

	public function testBuiltinDefaultsKeepTheUnsetDefault(): void {
		$this->assertSame(self::LEGACY_DEFAULTS, PreviewProviderDefaults::getBuiltinDefaultProviders());
	}

	public function testRecommendedWithoutImaginaryIsTheBuiltinDefault(): void {
		$this->assertSame(self::LEGACY_DEFAULTS, PreviewProviderDefaults::getRecommendedEnabledProviders(false, true));
	}

	public function testRecommendedWithImaginaryTriesItFirst(): void {
		$this->assertSame(
			[Imaginary::class, ...self::LEGACY_DEFAULTS],
			PreviewProviderDefaults::getRecommendedEnabledProviders(true),
		);
	}

	public function testRecommendedWithImaginaryAppendsHeicFallback(): void {
		$this->assertSame(
			[Imaginary::class, ...self::LEGACY_DEFAULTS, HEIC::class],
			PreviewProviderDefaults::getRecommendedEnabledProviders(true, true),
		);
	}
}
