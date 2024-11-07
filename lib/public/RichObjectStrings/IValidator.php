<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\RichObjectStrings;

/**
 * Class Validator
 *
 * @since 11.0.0
 */
interface IValidator {
	/**
	 * Only alphanumeric, dash, underscore and got are allowed, starting with a character
	 * @since 31.0.0
	 */
	public const PLACEHOLDER_REGEX = '[A-Za-z][A-Za-z0-9\-_.]+';

	/**
	 * @param string $subject
	 * @param array<non-empty-string, array<non-empty-string, string>> $parameters
	 * @throws InvalidObjectExeption
	 * @since 11.0.0
	 */
	public function validate(string $subject, array $parameters): void;
}
