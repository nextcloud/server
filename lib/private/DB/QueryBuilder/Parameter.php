<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder;

use OCP\DB\QueryBuilder\IParameter;

class Parameter implements IParameter {
	/** @var mixed */
	protected $name;

	public function __construct($name) {
		$this->name = $name;
	}

	public function __toString(): string {
		return (string)$this->name;
	}
}
