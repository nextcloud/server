<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Http;

use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Centralized Content-Disposition header value generation.
 *
 * Thin wrapper around Symfony's HeaderUtils::makeDisposition() that
 * auto-generates a multibyte-safe ASCII fallback filename.
 *
 * Fallback generation is adapted from Symfony's BinaryFileResponse::setContentDisposition().
 * @see https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/HttpFoundation/BinaryFileResponse.php
 */
class ContentDisposition {

	/**
	 * Generate a Content-Disposition header value.
	 *
	 * @param string $disposition 'attachment' or 'inline'
	 * @param string $filename The desired filename (UTF-8)
	 * @return string The complete header value, e.g. 'attachment; filename="report.pdf"'
	 */
	public static function make(string $disposition, string $filename): string {
		$fallback = self::toAsciiFallback($filename);
		return HeaderUtils::makeDisposition($disposition, $filename, $fallback);
	}

	/**
	 * Generate an ASCII-safe fallback filename.
	 *
	 * Uses multibyte-aware iteration so that one logical character
	 * (even if multi-byte) maps to exactly one '_' replacement.
	 *
	 * @param string $filename UTF-8 filename
	 * @return string ASCII-only fallback filename
	 */
	private static function toAsciiFallback(string $filename): string {
		// Pure ASCII and no '%' — usable as-is
		if (preg_match('/^[\x20-\x7E]*$/', $filename) && !str_contains($filename, '%')) {
			return $filename;
		}

		$fallback = '';
		$length = mb_strlen($filename, 'UTF-8');
		for ($i = 0; $i < $length; ++$i) {
			$char = mb_substr($filename, $i, 1, 'UTF-8');

			if ($char === '%') {
				$fallback .= '_';
			} elseif (preg_match('/^[\x20-\x7E]$/', $char) === 1) {
				$fallback .= $char;
			} else {
				$fallback .= '_';
			}
		}

		return $fallback;
	}
}
