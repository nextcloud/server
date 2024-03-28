<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCP\AppFramework\Db;

use ReflectionClass;
use function get_class;

class TypedEntity extends Entity {

	/** @var string[][] */
	static $reflectedFieldTypes = [];

	public function __construct() {
		$class = get_class($this);
		$cachedTypes = self::$reflectedFieldTypes[get_class($this)] ?? null;
		if ($cachedTypes !== null) {
			foreach ($cachedTypes as $name => $type) {
				$this->addType($name, $type);
			}
		} else {
			$reflectedSelf = new ReflectionClass($this);
			$fieldTypes = $this->getFieldTypes();
			self::$reflectedFieldTypes[$class] = [];
			foreach ($reflectedSelf->getProperties() as $property) {
				if (isset($fieldTypes[$property->getName()])) {
					// Don't override
					continue;
				}
				$propertyType = $property->getType();
				if ($propertyType === null) {
					// Can't derive
					continue;
				}

				if (!$propertyType->isBuiltin()) {
					// Complex type is not supported
					continue;
				}

				$this->addType($property->getName(), $propertyType->getName());
				self::$reflectedFieldTypes[$class][$property->getName()] = $propertyType->getName();
			}
		}
	}

}
