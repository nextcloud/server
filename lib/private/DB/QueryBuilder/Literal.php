<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder;

use OCP\DB\QueryBuilder\ILiteral;

class Literal implements ILiteral {
	/** @var mixed */
	protected $literal;

	public function __construct($literal) {
		$this->literal = $literal;
	}

	public function __toString(): string {
		return (string)$this->literal;
	}
}
