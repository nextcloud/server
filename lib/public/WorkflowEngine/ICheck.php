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


use OCP\Files\Storage\IStorage;

/**
 * Interface ICheck
 *
 * @package OCP\WorkflowEngine
 * @since 9.1
 */
interface ICheck {
	/**
	 * @param IStorage $storage
	 * @param string $path
	 * @since 9.1
	 */
	public function setFileInfo(IStorage $storage, $path);

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 * @since 9.1
	 */
	public function executeCheck($operator, $value);

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 * @since 9.1
	 */
	public function validateCheck($operator, $value);
}
