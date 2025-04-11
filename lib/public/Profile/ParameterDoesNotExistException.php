<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Profile;

/**
 * @since 23.0.0
 */
class ParameterDoesNotExistException extends \Exception {
	/**
	 * @since 23.0.0
	 */
	public function __construct($parameter) {
		parent::__construct("Parameter $parameter does not exist.");
	}
}
