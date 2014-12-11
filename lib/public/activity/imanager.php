<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller deepdiver@owncloud.com
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
	 * @param array $types
	 * @param string $filter
	 * @return array
	 */
	function filterNotificationTypes($types, $filter);

	/**
	 * @param string $method
	 * @return array
	 */
	function getDefaultTypes($method);

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
	 * @param string $type
	 * @return string
	 */
	function getTypeIcon($type);

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
	 * @param string $filter
	 * @return array
	 */
	function getQueryForFilter($filter);
}
