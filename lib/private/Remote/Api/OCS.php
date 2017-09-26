<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
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

namespace OC\Remote\Api;


use OC\ForbiddenException;
use OC\Remote\User;
use OCP\API;

class OCS extends ApiBase {
	/**
	 * @param string $method
	 * @param string $url
	 * @param array $body
	 * @param array $query
	 * @param array $headers
	 * @return array
	 * @throws ForbiddenException
	 * @throws NotFoundException
	 * @throws \Exception
	 */
	protected function request($method, $url, array $body = [], array $query = [], array $headers = []) {
		$response = json_decode(parent::request($method, '/ocs/v2.php/' . $url, $body, $query, $headers), true);
		if (!isset($result['ocs']) || !isset($result['ocs']['meta'])) {
			throw new \Exception('Invalid ocs response');
		}
		if ($response['ocs']['meta']['statuscode'] === API::RESPOND_UNAUTHORISED) {
			throw new ForbiddenException();
		}
		if ($response['ocs']['meta']['statuscode'] === API::RESPOND_NOT_FOUND) {
			throw new NotFoundException();
		}
		if ($response['ocs']['meta']['status'] !== 'ok') {
			throw new \Exception('Unknown ocs error ' . $response['ocs']['meta']['message']);
		}

		return $response['ocs']['data'];
	}

	public function getUser($userId) {
		return new User($this->request('get', 'cloud/users/' . $userId));
	}

	public function getCapabilities() {
		return $this->request('get', 'cloud/capabilities');
	}
}
