<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Backend;

/**
 * @since 17.0.0
 */
interface IGetRealUIDBackend {
	/**
	 * Some backends accept different UIDs than what is the internal UID to be used.
	 * For example the database backend accepts different cased UIDs in all the functions
	 * but the internal UID that is to be used should be correctly cased.
	 *
	 * This little function makes sure that the used UID will be correct when using the user object
	 *
	 * @since 17.0.0
	 * @param string $uid
	 * @return string
	 */
	public function getRealUID(string $uid): string;
}
