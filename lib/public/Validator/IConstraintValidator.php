<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Validator;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Validates PHP data structures against constraints
 *
 * Constraints are declared with attributes from {@see \OCP\Validator\Constraints}, e.g.
 * {@see \OCP\Validator\Constraints\NotBlank} or {@see \OCP\Validator\Constraints\Length}.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IConstraintValidator {
	/**
	 * Validates `$data` against the constraints declared on its class
	 *
	 * @param mixed $data the data to validate, e.g. an object, or an array of objects
	 * @param string|string[]|null $groups only constraints tagged with one of these groups are
	 *                                     checked; `null` checks every constraint regardless of its groups
	 * @return Violation[] empty when `$data` is valid
	 * @since 36.0.0
	 */
	public function validate(mixed $data, string|array|null $groups = null): array;
}
