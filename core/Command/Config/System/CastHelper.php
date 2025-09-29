<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Config\System;

class CastHelper {
	/**
	 * @return array{value: mixed, readable-value: string}
	 */
	public function castValue(?string $value, string $type): array {
		switch ($type) {
			case 'integer':
			case 'int':
				if (!is_numeric($value)) {
					throw new \InvalidArgumentException('Non-numeric value specified');
				}
				return [
					'value' => (int)$value,
					'readable-value' => 'integer ' . (int)$value,
				];

			case 'double':
			case 'float':
				if (!is_numeric($value)) {
					throw new \InvalidArgumentException('Non-numeric value specified');
				}
				return [
					'value' => (float)$value,
					'readable-value' => 'double ' . (float)$value,
				];

			case 'boolean':
			case 'bool':
				$value = strtolower($value);
				return match ($value) {
					'true' => [
						'value' => true,
						'readable-value' => 'boolean ' . $value,
					],
					'false' => [
						'value' => false,
						'readable-value' => 'boolean ' . $value,
					],
					default => throw new \InvalidArgumentException('Unable to parse value as boolean'),
				};

			case 'null':
				return [
					'value' => null,
					'readable-value' => 'null',
				];

			case 'string':
				$value = (string)$value;
				return [
					'value' => $value,
					'readable-value' => ($value === '') ? 'empty string' : 'string ' . $value,
				];

			case 'json':
				$value = json_decode($value, true);
				return [
					'value' => $value,
					'readable-value' => 'json ' . json_encode($value),
				];

			default:
				throw new \InvalidArgumentException('Invalid type');
		}
	}
}
