<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Http\Client;

/**
 * Interface IClientService
 *
 * @package OCP\Http
 */
interface IClientService {
	/**
	 * @return IClient
	 */
	public function newClient();
}
