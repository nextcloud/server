<?php
/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\GlobalScale;

/**
 * Interface IConfig
 *
 * Configuration of the global scale architecture
 *
 * @package OCP\GlobalScale
 * @since 12.0.1
 */
interface IConfig {


	const INCOMING = 'incoming';
	const OUTGOING = 'outgoing';


	/**
	 * check if global scale is enabled
	 *
	 * @return bool
	 * @since 12.0.1
	 */
	public function isGlobalScaleEnabled();

	/**
	 * check if federation should only be used internally in a global scale setup
	 *
	 * @param string $type since 19.0.0
	 *
	 * @return bool
	 * @since 12.0.1
	 */
	public function onlyInternalFederation(string $type);


	/**
	 * check if the outgoing federation is allowed, based on the $remote address
	 * If $token+$key is provided, only check the $token+$key (mainly for reading file purpose)
	 *
	 * @param string $remote
	 * @param string $token
	 * @param string $key
	 *
	 * @return bool
	 * @since 19.0.0
	 */
	public function allowedOutgoingFederation(string $remote, string $token = '', string $key = ''): bool;


	/**
	 * check if the incoming federation is allowed, based on the $token+$key.
	 * ~! If $remote is provided, only check the $remote (mainly for re-share purposing) !~
	 *
	 * @param string $remote
	 * @param string $token
	 * @param string $key
	 *
	 * @return bool
	 * @since 19.0.0
	 */
	public function allowedIncomingFederation(string $remote, string $token, string $key): bool;


	/**
	 * generate the key to confirm internal federation.
	 *
	 * @param $token
	 *
	 * @return string
	 */
	public function generateInternalKey(string $token): string;


	/**
	 * check that remote instance is internal.
	 *
	 * @param string $remote
	 *
	 * @return bool
	 */
	public function remoteIsInternal(string $remote): bool;

}

