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


namespace OCA\CloudFederationAPI;
use OCP\GlobalScale\IConfig as IGsConfig;
use OCP\IConfig;


/**
 * Class config
 *
 * handles all the config parameters
 *
 * @package OCA\CloudFederationAPI
 */
class Config {

	/** @var IGsConfig  */
	private $gsConfig;

	/** @var IConfig */
	private $config;

	public function __construct(IGsConfig $globalScaleConfig, IConfig $config) {
		$this->gsConfig = $globalScaleConfig;
		$this->config = $config;
	}

	public function incomingRequestsEnabled() {
		if ($this->gsConfig->onlyInternalFederation()) {
			return false;
		}
		$result = $this->config->getAppValue('files_sharing', 'incoming_server2server_share_enabled', 'yes');
		return ($result === 'yes');
	}

	public function outgoingRequestsEnabled() {

		if ($this->gsConfig->onlyInternalFederation()) {
			return false;
		}
		$result = $this->config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes');
		return ($result === 'yes');

	}

}
