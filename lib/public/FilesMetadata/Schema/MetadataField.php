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
 * Definition of a metadata field registered by an app.
 *
 * @see IMetadataSchemaProvider::getFields()
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class MetadataField implements JsonSerializable {
	/**
	 * @param string $app the app managing the metadata
	 * @param string $key unique metadata key
	 * @param MetadataFieldType $type value type
	 * @param string $label translated label displayed to users
	 * @param string $description translated description displayed to users
	 * @param list<MetadataFieldOption> $options options to choose from, required for {@see MetadataFieldType::ENUM}
	 * @since 36.0.0
	 */
	public function __construct(
		private readonly string $app,
		private readonly string $key,
		private readonly MetadataFieldType $type,
		private readonly string $label,
		private readonly string $description = '',
		private readonly ?array $options = null,
	) {
	}

	/**
	 * @since 36.0.0
	 */
	public function getApp(): string {
		return $this->app;
	}

	/**
	 * @since 36.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @since 36.0.0
	 */
	public function getType(): MetadataFieldType {
		return $this->type;
	}

	/**
	 * @since 36.0.0
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * @since 36.0.0
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * @return null|list<MetadataFieldOption>
	 * @since 36.0.0
	 */
	public function getOptions(): ?array {
		return $this->options;
	}

	/**
	 * @return array{key: string, type: string, label: string, description: string, options: null|list<MetadataFieldOption>}
	 * @since 36.0.0
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'key' => $this->key,
			'type' => $this->type->value,
			'label' => $this->label,
			'description' => $this->description,
			'options' => $this->options,
		];
	}
}
