<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Validator;

use OCP\Validator\Constraints\Choice;
use OCP\Validator\Constraints\Count;
use OCP\Validator\Constraints\Email;
use OCP\Validator\Constraints\Length;
use OCP\Validator\Constraints\NotBlank;
use OCP\Validator\Constraints\Range;
use OCP\Validator\Constraints\Regex;

class ValidatorTestDto {
	public function __construct(
		#[NotBlank]
		#[Length(min: 2, max: 20)]
		public string $name,
		#[Email(groups: ['detailed'])]
		public string $email,
		#[Range(min: 0, max: 150)]
		public int $age,
		#[Choice(choices: ['admin', 'member', 'guest'])]
		public string $role,
		#[Regex('/^[a-z0-9_]+$/i')]
		public string $username,
		/** @var string[] */
		#[Count(min: 1, max: 3)]
		public array $tags,
	) {
	}
}
