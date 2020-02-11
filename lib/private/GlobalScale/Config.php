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

namespace OC\GlobalScale;


use OCP\GlobalScale\IConfig as IGlobalScaleConfig;
use OCP\Http\Client\IClientService;
use OCP\IConfig;


class Config implements IGlobalScaleConfig {


	/** @var IClientService */
	private $clientService;

	/** @var IConfig */
	private $config;


	/**
	 * Config constructor.
	 *
	 * @param IClientService $clientService
	 * @param IConfig $config
	 */
	public function __construct(IClientService $clientService, IConfig $config) {
		$this->clientService = $clientService;
		$this->config = $config;
	}

	/**
	 * check if global scale is enabled
	 *
	 * @return bool
	 * @since 12.0.1
	 */
	public function isGlobalScaleEnabled() {
		$enabled = $this->config->getSystemValue('gs.enabled', false);

		return $enabled !== false;
	}

	/**
	 * check if federation should only be used internally in a global scale setup
	 *
	 * @param string $type since 19.0.0
	 *
	 * @return bool
	 * @since 12.0.1
	 */
	public function onlyInternalFederation(string $type) {
		// if global scale is disabled federation works always globally
		$gsEnabled = $this->isGlobalScaleEnabled();
		if ($gsEnabled === false) {
			return false;
		}

		$enabled = $this->config->getSystemValue('gs.federation', 'internal');

		$type = strtolower($type);
		$typeEnabled = 'internal';
		if (in_array($type, [IGlobalScaleConfig::INCOMING, IGlobalScaleConfig::OUTGOING])) {
			$typeEnabled = $this->config->getSystemValue('gs.federation.' . $type, 'internal');
		}

		return $enabled === 'internal' && $typeEnabled === 'internal';
	}


	/**
	 * @param string $remote
	 * @param string $token
	 * @param string $key
	 *
	 * @return bool
	 * @since 19.0.0
	 */
	public function allowedOutgoingFederation(string $remote, string $token = '', string $key = ''): bool {
		if (!$this->onlyInternalFederation(self::OUTGOING)) {
			return true;
		}

		if ($key !== '' && $token !== '') {
			return $this->keyIsInternal($token, $key);
		}

		return $this->remoteIsInternal($remote);
	}


	/**
	 * @param string $remote
	 * @param string $token
	 * @param string $key
	 *
	 * @return bool
	 * @since 19.0.0
	 */
	public function allowedIncomingFederation(string $remote, string $token, string $key): bool {
		if (!$this->onlyInternalFederation(self::INCOMING)) {
			return true;
		}

		if (!$this->remoteIsInternal($remote)) {
			return false;
		}

		return $this->keyIsInternal($token, $key);
	}


	/**
	 * @param string $token
	 *
	 * @return string
	 */
	public function generateInternalKey(string $token): string {
		$jwt = $this->config->getSystemValue('gss.jwt.key', '');
		if ($jwt === '' || $token === '') {
			return '';
		}

		return md5($token . '-' . $jwt);
	}


	/**
	 * @param string $remote
	 *
	 * @return bool
	 */
	public function remoteIsInternal(string $remote): bool {
		if (!$this->isGlobalScaleEnabled()) {
			return false;
		}

		$tmp = parse_url($remote, PHP_URL_HOST);
		$remote = ($tmp === null) ? $remote : $tmp;

		if (in_array($remote, $this->getGSInstances())) {
			return true;
		}

		return false;
	}


	/**
	 * @param string $token
	 * @param string $key
	 *
	 * @return bool
	 */
	private function keyIsInternal(string $token, string $key): bool {
		if ($key === $this->generateInternalKey($token)) {
			return true;
		}

		return false;
	}


	/**
	 * @return array
	 */
	public function getGSInstances(): array {
		/** @var string $lookup */
		$lookup = $this->config->getSystemValue('lookup_server', '');

		if ($lookup === '') {
			return [];
		}

		$client = $this->clientService->newClient();
		$url = rtrim($lookup, '/') . '/instances';

		try {
			$response = $client->get($url, ['connect_timeout' => 10]);
		} catch (\Exception $e) {
			\OC::$server->getLogger()
						->warning('Issue while retrieving instances from lookup: ' . $e->getMessage());

			return [];
		}

		return json_decode($response->getBody(), true);
	}

}

