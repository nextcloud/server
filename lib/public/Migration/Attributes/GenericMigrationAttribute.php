<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use OCP\AppFramework\Attribute\Consumable;

/**
 * generic entry, used to replace migration attribute not yet known in current version
 * but used in a future release
 */
#[Consumable(since: '30.0.0')]
class GenericMigrationAttribute extends MigrationAttribute {
	/**
	 * @param array $details
	 * @since 30.0.0
	 */
	public function __construct(
		private readonly array $details = [],
	) {
		parent::__construct(
			$details['table'] ?? '',
			$details['description'] ?? '',
			$details['notes'] ?? []
		);
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function definition(): string {
		return json_encode($this->jsonSerialize(), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * @return array
	 * @since 30.0.0
	 */
	public function jsonSerialize(): array {
		return $this->details;
	}
}
