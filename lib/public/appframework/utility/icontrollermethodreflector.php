<?php
/**
 * ownCloud - App Framework
 *
 * @author Olivier Paroz <owncloud@oparoz.com>
 * @copyright Olivier Paroz 2014
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\AppFramework\Utility;

/**
 * Interface ControllerMethodReflector
 *
 * Reads and parses annotations from doc comments
 *
 * @package OCP\AppFramework\Utility
 */
interface IControllerMethodReflector {

	/**
	 * @param object $object an object or classname
	 * @param string $method the method which we want to inspect
	 */
	public function reflect($object, $method);

	/**
	 * Inspects the PHPDoc parameters for types
	 *
	 * @param string $parameter the parameter whose type comments should be
	 * parsed
	 * @return string|null type in the type parameters (@param int $something)
	 * would return int or null if not existing
	 */
	public function getType($parameter);

	/**
	 * @return array the arguments of the method with key => default value
	 */
	public function getParameters();

	/**
	 * Check if a method contains an annotation
	 *
	 * @param string $name the name of the annotation
	 * @return bool true if the annotation is found
	 */
	public function hasAnnotation($name);

}