<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Db;

use function json_decode;
use function json_encode;
use \JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCP\Profile\ParameterDoesNotExistException;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getConfig()
 * @method void setConfig(string $config)
 */
class ProfileConfig extends Entity implements JsonSerializable {
	/** @var string */
	protected $userId;

	/** @var string */
	protected $config;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('config', 'string');
	}

	/**
	 * Returns the config in an associative array
	 */
	public function getConfigArray(): array {
		return json_decode($this->config, true);
	}

	/**
	 * Set the config
	 */
	public function setConfigArray(array $config): void {
		$this->setConfig(json_encode($config));
	}

	/**
	 * Returns the visibility map in an associative array
	 */
	public function getVisibilityMap(): array {
		$config = $this->getConfigArray();
		$visibilityMap = [];
		foreach ($config as $paramId => $paramConfig) {
			$visibilityMap[$paramId] = $paramConfig['visibility'];
		}

		return $visibilityMap;
	}

	/**
	 * Set the visibility map
	 */
	public function setVisibilityMap(array $visibilityMap): void {
		$config = $this->getConfigArray();
		foreach ($visibilityMap as $paramId => $visibility) {
			$config[$paramId] = array_merge(
				$config[$paramId] ?: [],
				['visibility' => $visibility],
			);
		}

		$this->setConfigArray($config);
	}

	/**
	 * Returns the visibility of the parameter
	 *
	 * @throws ParameterDoesNotExistException
	 */
	public function getVisibility(string $paramId): string {
		$visibilityMap = $this->getVisibilityMap();
		if (isset($visibilityMap[$paramId])) {
			return $visibilityMap[$paramId];
		}
		throw new ParameterDoesNotExistException($paramId);
	}

	/**
	 * Set the visibility of the parameter
	 */
	public function setVisibility(string $paramId, string $visibility): void {
		$visibilityMap = $this->getVisibilityMap();
		$visibilityMap[$paramId] = $visibility;
		$this->setVisibilityMap($visibilityMap);
	}

	public function jsonSerialize(): array {
		return [
			'userId' => $this->userId,
			'config' => $this->getConfigArray(),
		];
	}
}
