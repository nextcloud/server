<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata\Schema;

use JsonSerializable;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Option of a metadata field of type {@see MetadataFieldType::ENUM}
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class MetadataFieldOption implements JsonSerializable {
	/**
	 * @param string $value value stored when the option is chosen
	 * @param string $label translated label displayed to users
	 * @since 36.0.0
	 */
	public function __construct(
		private readonly string $value,
		private readonly string $label,
	) {
	}

	/**
	 * @since 36.0.0
	 */
	public function getValue(): string {
		return $this->value;
	}

	/**
	 * @since 36.0.0
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * @return array{value: string, label: string}
	 * @since 36.0.0
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'value' => $this->value,
			'label' => $this->label,
		];
	}
}
