<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

use OCP\Files\Template\Fields\CheckBoxField;
use OCP\Files\Template\Fields\RichTextField;

/**
 * @since 30.0.0
 */
class FieldFactory {
	/**
	 * @since 30.0.0
	 */
	public static function createField(
		string $index,
		FieldType $type,
	): Field {
		return match ($type) {
			FieldType::RichText => new RichTextField($index, $type),
			FieldType::CheckBox => new CheckBoxField($index, $type),
			default => throw new InvalidFieldTypeException(),
		};
	}
}
