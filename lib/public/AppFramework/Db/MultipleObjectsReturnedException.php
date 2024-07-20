<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Db;

/**
 * This is returned or should be returned when a find request finds more than one
 * row
 * @since 7.0.0
 */
class MultipleObjectsReturnedException extends \Exception implements IMapperException {
	/**
	 * Constructor
	 * @param string $msg the error message
	 * @since 7.0.0
	 */
	public function __construct($msg) {
		parent::__construct($msg);
	}
}
