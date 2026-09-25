<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

/**
 * Default and recommended `enabledPreviewProviders` lists, shared between
 * {@see \OC\PreviewManager} and the admin settings UI.
 */
class PreviewProviderDefaults {
	/**
	 * Built-in defaults when ``enabledPreviewProviders`` is unset.
	 *
	 * @return list<class-string>
	 */
	public static function getBuiltinDefaultProviders(): array {
		return [
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
			AVIF::class,
		];
	}

	/**
	 * Nextcloud-encouraged provider list.
	 *
	 * Matches core defaults, plus Imaginary first when it is configured (server
	 * tuning / AIO). Native HEIC is appended as a fallback because Imaginary
	 * does not handle every HEIC/HEIF file.
	 *
	 * @return list<class-string>
	 */
	public static function getRecommendedEnabledProviders(bool $imaginaryConfigured, bool $heicFallback = false): array {
		$providers = self::getBuiltinDefaultProviders();
		if (!$imaginaryConfigured) {
			return $providers;
		}
		$providers = array_merge([Imaginary::class], $providers);
		if ($heicFallback) {
			$providers[] = HEIC::class;
		}
		return array_values(array_unique($providers));
	}
}
