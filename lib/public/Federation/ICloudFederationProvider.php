<?php
/**
 * @copyright Copyright (c) 2018 Bjoern Schiessle <bjoern@schiessle.org>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Federation;

use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\Exceptions\ShareNotFoundException;

/**
 * Interface ICloudFederationProvider
 *
 * Enable apps to create their own cloud federation provider
 *
 * @since 14.0.0
 *
 * @package OCP\Federation
 */

interface ICloudFederationProvider {

	/**
	 * get the name of the share type, handled by this provider
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getShareType();

	/**
	 * send new share to another server
	 *
	 * @since 14.0.0
	 */
	public function sendShare();

	/**
	 * share received from another server
	 *
	 * @param ICloudFederationShare $share
	 * @return string provider specific unique ID of the share
	 *
	 * @throws ProviderCouldNotAddShareException
	 *
	 * @since 14.0.0
	 */
	public function shareReceived(ICloudFederationShare $share);

	/**
	 * notification received from another server
	 *
	 * @param string $id unique ID of a already existing share
	 * @param array $notification provider specific notification
	 *
	 * @throws ShareNotFoundException
	 *
	 * @since 14.0.0
	 */
	public function notificationReceived($id, $notification);

}
