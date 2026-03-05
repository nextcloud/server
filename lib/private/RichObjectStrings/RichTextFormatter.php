<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\RichObjectStrings;

use OCP\RichObjectStrings\IRichTextFormatter;

class RichTextFormatter implements IRichTextFormatter {
	/**
	 * @throws \InvalidArgumentException if a parameter has no name or no type
	 */
	public function richToParsed(string $message, array $parameters): string {
		$placeholders = [];
		$replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			foreach (['name','type'] as $requiredField) {
				if (!isset($parameter[$requiredField]) || !is_string($parameter[$requiredField])) {
					throw new \InvalidArgumentException("Invalid rich object, {$requiredField} field is missing");
				}
			}
			$replacements[] = match($parameter['type']) {
				'user' => '@' . $parameter['name'],
				'file' => $parameter['path'] ?? $parameter['name'],
				default => $parameter['name'],
			};
		}
		return str_replace($placeholders, $replacements, $message);
	}
}
