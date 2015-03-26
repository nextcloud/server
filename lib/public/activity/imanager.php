<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

/**
 * Public interface of ownCloud for apps to use.
 * Activity/IManager interface
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP\Activity;

interface IManager {

	/**
	 * @param $app
	 * @param $subject
	 * @param $subjectParams
	 * @param $message
	 * @param $messageParams
	 * @param $file
	 * @param $link
	 * @param $affectedUser
	 * @param $type
	 * @param $priority
	 * @return mixed
	 */
	function publishActivity($app, $subject, $subjectParams, $message, $messageParams, $file, $link, $affectedUser, $type, $priority);

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of \OCP\Activity\IConsumer
	 *
	 * @param \Closure $callable
	 * @return void
	 */
	function registerConsumer(\Closure $callable);

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of \OCP\Activity\IExtension
	 *
	 * @param \Closure $callable
	 * @return void
	 */
	function registerExtension(\Closure $callable);

	/**
	 * Will return additional notification types as specified by other apps
	 * @param string $languageCode
	 * @return array
	 */
	function getNotificationTypes($languageCode);

	/**
	 * @param string $method
	 * @return array
	 */
	function getDefaultTypes($method);

	/**
	 * @param string $type
	 * @return string
	 */
	function getTypeIcon($type);

	/**
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param boolean $stripPath
	 * @param boolean $highlightParams
	 * @param string $languageCode
	 * @return string|false
	 */
	function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode);

	/**
	 * @param string $app
	 * @param string $text
	 * @return array|false
	 */
	function getSpecialParameterList($app, $text);

	/**
	 * @param array $activity
	 * @return integer|false
	 */
	function getGroupParameter($activity);

	/**
	 * @return array
	 */
	function getNavigation();

	/**
	 * @param string $filterValue
	 * @return boolean
	 */
	function isFilterValid($filterValue);

	/**
	 * @param array $types
	 * @param string $filter
	 * @return array
	 */
	function filterNotificationTypes($types, $filter);

	/**
	 * @param string $filter
	 * @return array
	 */
	function getQueryForFilter($filter);
}
