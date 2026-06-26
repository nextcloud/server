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
 * This interface represents a database migration step.
 *
 * To implement a migration step, you must extend \OCP\Migration\SimpleMigrationStep
 *
 * You should additionally add some attributes found in the
 * \OCP\Migration\Attributes namespace to the migration, to describe the change
 * that will be done by the migration step to the admin.
 *
 * @since 13.0.0
 */
interface IMigrationStep {
	/**
	 * Human-readable name of the migration step
	 *
	 * @since 14.0.0
	 */
	public function name(): string;

	/**
	 * Human-readable description of the migration step
	 *
	 * @since 14.0.0
	 */
	public function description(): string;

	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array{tablePrefix?: string} $options
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options);

	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array{tablePrefix?: string} $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options);

	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array{tablePrefix?: string} $options
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options);
}
