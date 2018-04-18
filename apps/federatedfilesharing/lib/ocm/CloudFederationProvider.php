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

namespace OCA\FederatedFileSharing\OCM;

use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationShare;

class CloudFederationProvider implements ICloudFederationProvider {

	/** @var string */
	private $shareType;

	/**
	 * CloudFederationProvider constructor.
	 *
	 * @param string $shareType
	 */
	public function __construct($shareType) {
		$this->shareType = $shareType;
	}

	/**
	 * @return string
	 */
	public function getShareType() {
		return $this->shareType;
	}

	/**
	 * send new share to another server
	 *
	 * @since 14.0.0
	 */
	public function sendShare() {
		// TODO: Implement sendShare() method.
	}

	/**
	 * share received from another server
	 *
	 * @param ICloudFederationShare $share
	 * @return string provider specific unique ID of the share
	 *
	 * @since 14.0.0
	 */
	public function shareReceived(ICloudFederationShare $share) {
		// TODO: Implement shareReceived() method.
	}

	/**
	 * notification received from another server
	 *
	 * @param string $id unique ID of a already existing share
	 * @param array $notification provider specific notification
	 *
	 * @throws \OCP\Federation\Exceptions\ShareNotFoundException
	 *
	 * @since 14.0.0
	 */
	public function notificationReceived($id, $notification) {
		// TODO: Implement notificationReceived() method.
	}
}
