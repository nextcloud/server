<?php
/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\BruteForceSettings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class IPWhitelistController extends Controller {

	/** @var IConfig */
	private $config;

	/**
	 * IPWhitelistController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 */
	public function __construct($appName,
								IRequest $request,
								IConfig $config) {
		parent::__construct($appName, $request);

		$this->config = $config;
	}

	/**
	 * @return JSONResponse
	 */
	public function getAll() {
		$keys = $this->config->getAppKeys('bruteForce');
		$keys = array_filter($keys, function($key) {
			$regex = '/^whitelist_/S';
			return preg_match($regex, $key) === 1;
		});

		$result = [];

		foreach ($keys as $key) {
			$value = $this->config->getAppValue('bruteForce', $key);
			$values = explode('/', $value);

			$result[] = [
				'id' => (int)substr($key, 10),
				'ip' => $values[0],
				'mask' => $values[1],
			];
		}

		return new JSONResponse($result);
	}

	/**
	 * @param string $ip
	 * @param int $mask
	 * @return JSONResponse
	 */
	public function add($ip, $mask) {
		if (!filter_var($ip, FILTER_VALIDATE_IP) ||
			(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && ($mask < 0 || $mask > 32)) ||
			(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && ($mask < 0 || $mask > 128))) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$keys = $this->config->getAppKeys('bruteForce');
		$keys = array_filter($keys, function($key) {
			$regex = '/^whitelist_/S';
			return preg_match($regex, $key) === 1;
		});

		$id = 0;
		foreach ($keys as $key) {
			$tmp = (int)substr($key, 10);
			if ($tmp > $id) {
				$id = $tmp;
			}
		}
		$id++;

		$value = $ip . '/' . $mask;
		$this->config->setAppValue('bruteForce', 'whitelist_'.$id, $value);
		return new JSONResponse([
			'id' => $id,
			'ip' => $ip,
			'mask' => $mask,
		]);
	}

	/**
	 * @param int $id
	 * @return JSONResponse
	 */
	public function remove($id) {
		$this->config->deleteAppValue('bruteForce', 'whitelist_'.$id);

		return new JSONResponse([]);
	}
}
