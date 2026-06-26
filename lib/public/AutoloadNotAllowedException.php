<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Exception for when a not allowed path is attempted to be autoloaded
 * @since 8.2.0
 */
class AutoloadNotAllowedException extends \DomainException {
	/**
	 * @param string $path
	 * @since 8.2.0
	 */
	public function __construct($path) {
		parent::__construct('Autoload path not allowed: ' . $path);
	}
}
