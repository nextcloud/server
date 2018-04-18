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


namespace OC\Federation;

use OCP\Federation\Exceptions\ProviderAlreadyExistsException;
use OCP\Federation\Exceptions\ProviderDoesNotExistsException;
use OCP\Federation\ICloudFederationNotification;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;

/**
 * Class Manager
 *
 * Manage Cloud Federation Providers
 *
 * @package OC\Federation
 */
class CloudFederationProviderManager implements ICloudFederationProviderManager {

	/** @var array list of available cloud federation providers */
	private $cloudFederationProvider;

	public function __construct() {
		$this->cloudFederationProvider= [];
	}


	/**
	 * Registers an callback function which must return an cloud federation provider
	 *
	 * @param string $shareType which share type does the provider handles
	 * @param string $displayName user facing name of the federated share provider
	 * @param callable $callback
	 */
	public function addCloudFederationProvider($shareType, $displayName, callable $callback) {
		\OC::$server->getRemoteApiFactory();

		$this->cloudFederationProvider[$shareType] = [
			'shareType' => $shareType,
			'displayName' => $displayName,
			'callback' => $callback,
		];

	}

	/**
	 * remove cloud federation provider
	 *
	 * @param string $providerId
	 */
	public function removeCloudFederationProvider($providerId) {
		unset($this->cloudFederationProvider[$providerId]);
	}

	/**
	 * get a list of all cloudFederationProviders
	 *
	 * @return array [id => ['id' => $id, 'displayName' => $displayName, 'callback' => callback]]
	 */
	public function getAllCloudFederationProviders() {
		return $this->cloudFederationProvider;
	}

	/**
	 * get a specific cloud federation provider
	 *
	 * @param string $shareType
	 * @return ICloudFederationProvider
	 * @throws ProviderDoesNotExistsException
	 */
	public function getCloudFederationProvider($shareType) {
		if (isset($this->cloudFederationProvider[$shareType])) {
			return call_user_func($this->cloudFederationProvider[$shareType]['callback']);
		} else {
			throw new ProviderDoesNotExistsException($shareType);
		}
	}

	public function sendShare(ICloudFederationShare $share) {
		// TODO: Implement sendShare() method.
	}

	public function sendNotification(ICloudFederationNotification $notification) {
		// TODO: Implement sendNotification() method.
	}

}
