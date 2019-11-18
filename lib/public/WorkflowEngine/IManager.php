<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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

/**
 * Interface IManager
 *
 * @package OCP\WorkflowEngine
 * @since 9.1
 */
interface IManager {

	const SCOPE_ADMIN = 0;
	const SCOPE_USER = 1;

	const EVENT_NAME_REG_OPERATION = 'OCP\WorkflowEngine::registerOperations';
	const EVENT_NAME_REG_ENTITY = 'OCP\WorkflowEngine::registerEntities';
	const EVENT_NAME_REG_CHECK = 'OCP\WorkflowEngine::registerChecks';

	/**
	 * Listen to `\OCP\WorkflowEngine::EVENT_NAME_REG_ENTITY` at the
	 * EventDispatcher for registering your entities.
	 *
	 * @since 18.0.0
	 */
	public function registerEntity(IEntity $entity): void;

	/**
	 * Listen to `\OCP\WorkflowEngine::EVENT_NAME_REG_OPERATION` at the
	 * EventDispatcher for registering your operators.
	 *
	 * @since 18.0.0
	 */
	public function registerOperation(IOperation $operator): void;

	/**
	 * Listen to `\OCP\WorkflowEngine::EVENT_NAME_REG_CHECK` at the
	 * EventDispatcher for registering your operators.
	 *
	 * @since 18.0.0
	 */
	public function registerCheck(ICheck $check): void;

	/**
	 * @since 18.0.0
	 */
	public function getRuleMatcher(): IRuleMatcher;
}
