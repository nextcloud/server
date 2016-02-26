<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCP\Activity\v2;


interface IManager extends IConsumer, IExtension {
	/**
	 * Generates a new IEvent object
	 *
	 * Make sure to call at least the following methods before sending it to the
	 * app with via the publish() method:
	 *  - setApp()
	 *  - setType()
	 *  - setAffectedUser()
	 *  - setSubject()
	 *
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function generateEvent();

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of \OCP\Activity\v2\IConsumer
	 *
	 * @param \Closure $callable
	 * @return void
	 * @since 6.0.0
	 */
	public function registerConsumer(\Closure $callable);

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of \OCP\Activity\v2\IExtension
	 *
	 * @param \Closure $callable
	 * @return void
	 * @since 8.0.0
	 */
	public function registerExtension(\Closure $callable);

	/**
	 * @param string $app
	 * @param string $type
	 * @param string $description
	 * @param string[] $supportedMethods {@see IExtension::METHOD_*}
	 * @param string[] $defaultMethods {@see IExtension::METHOD_*}
	 * @throws \OutOfBoundsException When the app-type combination is already taken
	 */
	public function registerSetting($app, $type, $description, array $supportedMethods, array $defaultMethods);

	/**
	 * @param string $id
	 * @param \Closure $callable has to return an instance of \OCP\Activity\v2\IFilter
	 * @throws \OutOfBoundsException When the filter id is already taken
	 */
	public function registerFilter($id, \Closure $callable);

	/**
	 * @return IFilter[]
	 */
	public function getAllFilters();

	/**
	 * @param string $id
	 * @return IFilter
	 * @throws \InvalidArgumentException when the filter is unknown
	 */
	public function getFilter($id);


	/**
	 * @param string $type
	 * @param int $id
	 * @since 8.2.0
	 */
	public function setFormattingObject($type, $id);

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	public function isFormattingFilteredObject();

	/**
	 * Get the user we need to use
	 *
	 * Either the user is logged in, or we try to get it from the token
	 *
	 * @return string
	 * @throws \UnexpectedValueException If the token is invalid, does not exist or is not unique
	 * @since 8.1.0
	 */
	public function getCurrentUserId();
}
