<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OCP\AppFramework\Http\Response;
use OCP\IConfig;

/**
 * Builds Cache-Control values for preview HTTP responses.
 *
 * Missing config keys keep the caller-supplied cacheFor() defaults so
 * authenticated previews stay private unless an admin opts in.
 */
class PreviewCachePolicy {
	public const AUTHENTICATED = 'authenticated';
	public const PUBLIC = 'public';

	public function __construct(
		private readonly IConfig $config,
	) {
	}

	/**
	 * @return array{visibility: string, max_age: int, s_maxage: ?int, immutable: bool, cache_control: string}
	 */
	public static function defaultAuthenticated(): array {
		return [
			'visibility' => 'private',
			'max_age' => 86400,
			's_maxage' => null,
			'immutable' => true,
			'cache_control' => '',
		];
	}

	/**
	 * @return array{visibility: string, max_age: int, s_maxage: ?int, immutable: bool, cache_control: string}
	 */
	public static function defaultPublic(): array {
		return [
			'visibility' => 'private',
			'max_age' => 86400,
			's_maxage' => null,
			'immutable' => false,
			'cache_control' => '',
		];
	}

	/**
	 * @param array{visibility?: string, max_age?: int, s_maxage?: ?int, immutable?: bool, cache_control?: string} $policy
	 */
	public static function buildCacheControl(array $policy): string {
		$raw = trim((string)($policy['cache_control'] ?? ''));
		if ($raw !== '') {
			return $raw;
		}

		$maxAge = (int)($policy['max_age'] ?? 0);
		if ($maxAge <= 0) {
			return 'no-cache, no-store, must-revalidate';
		}

		$visibility = ($policy['visibility'] ?? 'private') === 'public' ? 'public' : 'private';
		$parts = [
			$visibility,
			'max-age=' . $maxAge,
		];
		$sMaxAge = $policy['s_maxage'] ?? null;
		if (is_int($sMaxAge) && $sMaxAge >= 0) {
			$parts[] = 's-maxage=' . $sMaxAge;
		}
		$parts[] = !empty($policy['immutable']) ? 'immutable' : 'must-revalidate';
		return implode(', ', $parts);
	}

	public function apply(
		Response $response,
		string $audience,
		int $fallbackMaxAge,
		bool $fallbackPublic = false,
		bool $fallbackImmutable = false,
		?int $maxAgeCap = null,
	): void {
		$policy = $this->getConfiguredPolicy($audience);
		if ($policy === null) {
			$maxAge = $maxAgeCap !== null ? min($fallbackMaxAge, $maxAgeCap) : $fallbackMaxAge;
			$response->cacheFor($maxAge, $fallbackPublic, $fallbackImmutable);
			return;
		}

		if ($maxAgeCap !== null) {
			$policy['max_age'] = min($policy['max_age'], $maxAgeCap);
		}

		$control = self::buildCacheControl($policy);
		$response->addHeader('Cache-Control', $control);
		if ($policy['max_age'] > 0 && ($policy['cache_control'] ?? '') === '') {
			// Keep Expires aligned with max-age when we are not using a raw override.
			$response->cacheFor(
				$policy['max_age'],
				$policy['visibility'] === 'public',
				(bool)$policy['immutable'],
			);
			$response->addHeader('Cache-Control', $control);
		}
	}

	/**
	 * @return array{visibility: string, max_age: int, s_maxage: ?int, immutable: bool, cache_control: string}|null
	 */
	public function getConfiguredPolicy(string $audience): ?array {
		$key = $audience === self::PUBLIC ? 'preview_cache_public' : 'preview_cache_authenticated';
		$unset = new \stdClass();
		$value = $this->config->getSystemValue($key, $unset);
		if ($value === $unset || !is_array($value)) {
			return null;
		}

		$visibility = is_string($value['visibility'] ?? null) ? strtolower($value['visibility']) : 'private';
		if (!in_array($visibility, ['private', 'public'], true)) {
			$visibility = 'private';
		}
		$maxAge = isset($value['max_age']) && is_numeric($value['max_age']) ? (int)$value['max_age'] : 86400;
		if ($maxAge < 0) {
			$maxAge = 0;
		}
		$sMaxAge = null;
		if (isset($value['s_maxage']) && $value['s_maxage'] !== null && $value['s_maxage'] !== '' && is_numeric($value['s_maxage'])) {
			$sMaxAge = (int)$value['s_maxage'];
			if ($sMaxAge < 0) {
				$sMaxAge = null;
			}
		}
		$raw = is_string($value['cache_control'] ?? null) ? trim($value['cache_control']) : '';

		return [
			'visibility' => $visibility,
			'max_age' => $maxAge,
			's_maxage' => $sMaxAge,
			'immutable' => (bool)($value['immutable'] ?? false),
			'cache_control' => $raw,
		];
	}
}
