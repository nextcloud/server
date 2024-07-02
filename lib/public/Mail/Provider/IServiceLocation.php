<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Service Location Interface
 *
 * This interface is a base requirement of methods and functionality used to construct a mail service location.
 *
 * @since 30.0.0
 *
 */
interface IServiceLocation {

	/**
	 * A string identifiing this location type
	 *
	 * @since 30.0.0
	 *
	 * @return string
	 */
	public function type(): string;

}
