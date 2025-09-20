<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Accounts;

/**
 * Class PropertyDoesNotExistException
 *
 * @since 15.0.0
 *
 */
class PropertyDoesNotExistException extends \Exception {
	/**
	 * @since 15.0.0
	 */
	public function __construct(string $property) {
		parent::__construct('Property ' . $property . ' does not exist.');
	}
}
