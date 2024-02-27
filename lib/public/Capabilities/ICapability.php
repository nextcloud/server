<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\Capabilities;

/**
 * Minimal interface that has to be implemented for a class to be considered
 * a capability.
 *
 * In an application use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerCapability
 * to register capabilities.
 *
 * @since 8.2.0
 */
interface ICapability {
	/**
	 * Function an app uses to return the capabilities
	 *
	 * ```php
	 * return [
	 *     'myapp' => [
	 *         'awesomefeature' => true,
	 *         'featureversion' => 3,
	 *     ],
	 *     'morecomplex' => [
	 *         'a' => [1, 2],
	 *     ],
	 * ];
	 * ```
	 *
	 * @return array<string, array<string, mixed>> Indexed array containing the app's capabilities
	 * @since 8.2.0
	 */
	public function getCapabilities();
}
