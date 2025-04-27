<?php

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
	 * @param string $subject
	 * @param array[] $parameters
	 * @throws InvalidObjectExeption
	 * @since 11.0.0
	 */
	public function validate($subject, array $parameters);
}
