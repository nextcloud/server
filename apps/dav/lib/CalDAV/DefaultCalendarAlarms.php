<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use JsonException;
use Sabre\DAV\Exception\BadRequest;

/**
 * Validates and serializes per-calendar default alarm templates stored as JSON.
 *
 * Each entry: {"trigger": <int seconds>, "action": "DISPLAY"|"EMAIL"}
 */
class DefaultCalendarAlarms {
	private const ALLOWED_ACTIONS = ['DISPLAY', 'EMAIL'];

	/**
	 * @throws BadRequest
	 * @throws \RuntimeException
	 */
	public static function validateAndEncode(mixed $value): ?string {
		if ($value === null || $value === '') {
			return null;
		}

		try {
			if (is_string($value)) {
				/** @var mixed $decoded */
				$decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
			} elseif (is_array($value)) {
				$decoded = $value;
			} else {
				throw new BadRequest('Default alarms must be a JSON array');
			}
		} catch (JsonException) {
			throw new BadRequest('Default alarms must be valid JSON');
		}

		if (!is_array($decoded)) {
			throw new BadRequest('Default alarms must be a JSON array');
		}

		if ($decoded === []) {
			return null;
		}

		$normalized = self::normalizeDecodedArray($decoded);

		try {
			return json_encode($normalized, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new \RuntimeException('Internal error encoding default alarms', 0, $e);
		}
	}

	/**
	 * Returns the JSON string exposed on CalDAV, synthesizing from legacy int when needed.
	 */
	public static function formatForCalDav(?string $json, ?int $legacyInt): ?string {
		if ($json !== null && $json !== '') {
			try {
				/** @var mixed $decoded */
				$decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
				if (!is_array($decoded) || $decoded === []) {
					return null;
				}

				return $json;
			} catch (JsonException) {
				return null;
			}
		}

		return self::encodeFromLegacyInt($legacyInt);
	}

	public static function legacyIntFromJson(?string $json): ?int {
		if ($json === null || $json === '') {
			return null;
		}

		try {
			/** @var mixed $decoded */
			$decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
			if (!is_array($decoded) || $decoded === []) {
				return null;
			}

			$first = $decoded[0];
			if (!is_array($first) || !array_key_exists('trigger', $first)) {
				return null;
			}

			return (int)$first['trigger'];
		} catch (JsonException) {
			return null;
		}
	}

	public static function encodeFromLegacyInt(?int $legacyInt): ?string {
		if ($legacyInt === null) {
			return null;
		}

		try {
			return json_encode([
				['trigger' => $legacyInt, 'action' => 'DISPLAY'],
			], JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return null;
		}
	}

	/**
	 * Applies a legacy single-int update without discarding additional stored alarms.
	 *
	 * Updates the first alarm's trigger (preserving its action when present).
	 * Falls back to a single DISPLAY alarm when no JSON list exists yet.
	 */
	public static function mergeLegacyIntIntoJson(?string $existingJson, ?int $legacyInt): ?string {
		if ($legacyInt === null) {
			return null;
		}

		if ($existingJson === null || $existingJson === '') {
			return self::encodeFromLegacyInt($legacyInt);
		}

		try {
			/** @var mixed $decoded */
			$decoded = json_decode($existingJson, true, 512, JSON_THROW_ON_ERROR);
			if (!is_array($decoded) || $decoded === []) {
				return self::encodeFromLegacyInt($legacyInt);
			}

			$first = $decoded[0];
			if (!is_array($first)) {
				return self::encodeFromLegacyInt($legacyInt);
			}

			$action = 'DISPLAY';
			if (isset($first['action']) && is_string($first['action']) && in_array($first['action'], self::ALLOWED_ACTIONS, true)) {
				$action = $first['action'];
			}

			$decoded[0] = [
				'trigger' => $legacyInt,
				'action' => $action,
			];

			return json_encode($decoded, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return self::encodeFromLegacyInt($legacyInt);
		}
	}

	/**
	 * @param array<int, mixed> $alarms
	 * @return list<array{trigger: int, action: string}>
	 * @throws BadRequest
	 */
	private static function normalizeDecodedArray(array $alarms): array {
		$normalized = [];
		foreach ($alarms as $alarm) {
			if (!is_array($alarm)) {
				throw new BadRequest('Each default alarm must be an object');
			}

			if (!array_key_exists('trigger', $alarm) || !array_key_exists('action', $alarm)) {
				throw new BadRequest('Each default alarm requires trigger and action');
			}

			if (!is_int($alarm['trigger']) && !is_float($alarm['trigger']) && !(is_string($alarm['trigger']) && is_numeric($alarm['trigger']))) {
				throw new BadRequest('Default alarm trigger must be an integer');
			}

			if (!is_string($alarm['action']) || !in_array($alarm['action'], self::ALLOWED_ACTIONS, true)) {
				throw new BadRequest('Default alarm action must be DISPLAY or EMAIL');
			}

			$normalized[] = [
				'trigger' => (int)$alarm['trigger'],
				'action' => $alarm['action'],
			];
		}

		return $normalized;
	}
}
