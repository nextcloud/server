<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
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

namespace OC\AppFramework\Http;

/**
 * A restricted version of IRequest that only contains the request parameters
 */
class RequestVars {
	private array $items = [];

	public function __construct(array $vars) {
		if (!array_key_exists('method', $vars)) {
			$vars['method'] = 'GET';
		}

		foreach (Request::ALLOWED_KEYS as $name) {
			$this->items[$name] = $vars[$name] ?? [];
		}

		$this->items['parameters'] = array_merge(
			$this->items['get'],
			$this->items['post'],
			$this->items['urlParams']
		);
	}

	/**
	 * Returns the value for a specific http header.
	 *
	 * This method returns an empty string if the header did not exist.
	 *
	 * @param string $name
	 * @return string
	 */
	public function getHeader(string $name): string {
		$server = $this->items['server'];
		$name = strtoupper(str_replace('-', '_', $name));
		if (isset($server['HTTP_' . $name])) {
			return $server['HTTP_' . $name];
		}

		// There's a few headers that seem to end up in the top-level
		// server array.
		switch ($name) {
			case 'CONTENT_TYPE':
			case 'CONTENT_LENGTH':
			case 'REMOTE_ADDR':
				if (isset($server[$name])) {
					return $server[$name];
				}
				break;
		}

		return '';
	}

	/**
	 * Returns the method of the request
	 *
	 * @return string the method of the request (POST, GET, etc)
	 */
	public function getMethod(): string {
		return $this->items['method'];
	}

	/**
	 * Lets you access post and get parameters by the index
	 * In case of json requests the encoded json body is accessed
	 *
	 * @param string $key the key which you want to access in the URL Parameter
	 *                     placeholder, $_POST or $_GET array.
	 *                     The priority how they're returned is the following:
	 *                     1. URL parameters
	 *                     2. POST parameters
	 *                     3. GET parameters
	 * @param mixed $default If the key is not found, this value will be returned
	 * @return mixed the content of the array
	 */
	public function getParam(string $key, $default = null) {
		$parameters = $this->items['parameters'];
		return $parameters[$key] ?? $default;
	}
}
