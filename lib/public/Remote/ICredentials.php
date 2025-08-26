<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Remote;

/**
 * The credentials for a remote user
 *
 * @since 13.0.0
 * @deprecated 23.0.0
 */
interface ICredentials {
	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getUsername();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getPassword();
}
