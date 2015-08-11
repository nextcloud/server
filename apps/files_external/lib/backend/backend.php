<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Lib\Backend;

use \OCA\Files_External\Lib\StorageConfig;
use \OCA\Files_External\Lib\VisibilityTrait;
use \OCA\Files_External\Lib\FrontendDefinitionTrait;
use \OCA\Files_External\Lib\PriorityTrait;
use \OCA\Files_External\Lib\DependencyTrait;
use \OCA\Files_External\Lib\StorageModifierTrait;

/**
 * Storage backend
 */
class Backend implements \JsonSerializable {

	use VisibilityTrait;
	use FrontendDefinitionTrait;
	use PriorityTrait;
	use DependencyTrait;
	use StorageModifierTrait;

	/** @var string storage class */
	private $storageClass;

	/**
	 * @return string
	 */
	public function getClass() {
		// return storage class for legacy compat
		return $this->getStorageClass();
	}

	/**
	 * @return string
	 */
	public function getStorageClass() {
		return $this->storageClass;
	}

	/**
	 * @param string $class
	 * @return self
	 */
	public function setStorageClass($class) {
		$this->storageClass = $class;
		return $this;
	}

	/**
	 * Serialize into JSON for client-side JS
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		$data = $this->jsonSerializeDefinition();

		$data['backend'] = $data['name']; // legacy compat
		$data['priority'] = $this->getPriority();

		return $data;
	}

	/**
	 * Check if parameters are satisfied in a StorageConfig
	 *
	 * @param StorageConfig $storage
	 * @return bool
	 */
	public function validateStorage(StorageConfig $storage) {
		return $this->validateStorageDefinition($storage);
	}

}

