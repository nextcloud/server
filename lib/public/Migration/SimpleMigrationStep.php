<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration;

use Override;

/**
 * Abstract class implementing migration step.
 *
 * @since 13.0.0
 */
abstract class SimpleMigrationStep implements IMigrationStep {
	#[Override]
	public function name(): string {
		return '';
	}

	#[Override]
	public function description(): string {
		return '';
	}

	#[Override]
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}

	#[Override]
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		return null;
	}

	#[Override]
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}
}
