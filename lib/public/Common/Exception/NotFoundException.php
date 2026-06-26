<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Common\Exception;

/**
 * This is thrown whenever something was expected to exist but doesn't
 *
 * @since 27.1.0
 */
class NotFoundException extends \Exception {
	/**
	 * Constructor
	 * @param string $msg the error message
	 * @since 27.1.0
	 */
	public function __construct(string $msg) {
		parent::__construct($msg);
	}
}
