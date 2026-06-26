<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Token;

interface INamedToken extends IToken {
	/**
	 * Set token name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName(string $name): void;
}
