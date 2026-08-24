<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Validator;

use OCP\Validator\IValidator;
use OCP\Validator\Violation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

class Validator implements IValidator {
	private ValidatorInterface $validator;

	public function __construct() {
		$this->validator = (new ValidatorBuilder())
			->addLoader(new AttributeLoader())
			->getValidator();
	}

	#[\Override]
	public function validate(mixed $data, string|array|null $groups = null): array {
		$violations = $this->validator->validate($data, null, $groups);

		return array_map(
			static fn (ConstraintViolationInterface $violation): Violation => new Violation(
				propertyPath: $violation->getPropertyPath(),
				message: (string)$violation->getMessage(),
				invalidValue: $violation->getInvalidValue(),
			),
			iterator_to_array($violations),
		);
	}
}
