<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;

/**
 * @since 13.0.0
 */
abstract class SimpleMigrationStep implements IMigrationStep {
	/**
	 * Human-readable name of the migration step
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function name(): string {
		return '';
	}

	/**
	 * Human-readable description of the migration step
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function description(): string {
		return '';
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @psalm-param Closure():ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @psalm-param Closure():ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		return null;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @psalm-param Closure():ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}
}
