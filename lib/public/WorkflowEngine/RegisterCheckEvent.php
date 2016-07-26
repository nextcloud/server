<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
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

namespace OCP\WorkflowEngine;


use Symfony\Component\EventDispatcher\Event;

/**
 * Class RegisterCheckEvent
 *
 * @package OCP\WorkflowEngine
 * @since 9.1
 */
class RegisterCheckEvent extends Event {

	/** @var array[] */
	protected $checks = [];

	/**
	 * @param string $class
	 * @param string $name
	 * @param string[] $operators
	 * @throws \OutOfBoundsException when the check class is already registered
	 * @throws \OutOfBoundsException when the provided information is invalid
	 * @since 9.1
	 */
	public function addCheck($class, $name, array $operators) {
		if (!is_string($class)) {
			throw new \OutOfBoundsException('Given class name is not a string');
		}

		if (isset($this->checks[$class])) {
			throw new \OutOfBoundsException('Duplicate check class "' . $class . '"');
		}

		if (!is_string($name)) {
			throw new \OutOfBoundsException('Given check name is not a string');
		}

		foreach ($operators as $operator) {
			if (!is_string($operator)) {
				throw new \OutOfBoundsException('At least one of the operators is not a string');
			}
		}

		$this->checks[$class] = [
			'class' => $class,
			'name' => $name,
			'operators' => $operators,
		];
	}

	/**
	 * @return array[]
	 * @since 9.1
	 */
	public function getChecks() {
		return array_values($this->checks);
	}
}
