<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace OC;


use OCP\Capabilities\ICapability;

class CapabilitiesManager {

	/**
	 * @var \Closure[]
	 */
	private $capabilities = array();

	/**
	 * Get an array of al the capabilities that are registered at this manager
     *
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function getCapabilities() {
		$capabilities = [];
		foreach($this->capabilities as $capability) {
			$c = $capability();
			if ($c instanceof ICapability) {
				$capabilities = array_replace_recursive($capabilities, $c->getCapabilities());
			} else {
				throw new \InvalidArgumentException('The given Capability (' . get_class($c) . ') does not implement the ICapability interface');
			}
		}

		return $capabilities;
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * capabilities are actually requested
	 *
	 * $callable has to return an instance of OCP\Capabilities\ICapability
	 *
	 * @param \Closure $callable
	 */
	public function registerCapability(\Closure $callable) {
		array_push($this->capabilities, $callable);
	}
}
