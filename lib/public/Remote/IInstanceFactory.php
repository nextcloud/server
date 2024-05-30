<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Remote;

/**
 * @since 13.0.0
 * @deprecated 23.0.0
 */
interface IInstanceFactory {
	/**
	 * @param string $url
	 * @return IInstance
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getInstance($url);
}
