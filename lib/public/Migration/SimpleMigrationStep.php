<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration;

use OCP\DB\ISchemaWrapper;
use Override;

/**
 * Abstract class implementing migration step.
 *
 * @since 13.0.0
 */
abstract class SimpleMigrationStep implements IMigrationStep {
	/**
	 * Human-readable name of the migration step
	 *
	 * @since 14.0.0
	 */
	#[Override]
	public function name(): string {
		return '';
	}

	/**
	 * Human-readable description of the migration step
	 *
	 * @since 14.0.0
	 */
	#[Override]
	public function description(): string {
		return '';
	}

	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array{tablePrefix?: string} $options
	 * @since 13.0.0
	 */
	#[Override]
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}

	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array{tablePrefix?: string} $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	#[Override]
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		return null;
	}

	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array{tablePrefix?: string} $options
	 * @since 13.0.0
	 */
	#[Override]
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}
}
