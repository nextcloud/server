<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Route;

/**
 * Interface IRoute
 *
 * @since 7.0.0
 */
interface IRoute {
	/**
	 * Specify PATCH as the method to use with this route
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 */
	public function patch();

	/**
	 * Specify the method when this route is to be used
	 *
	 * @param string $method HTTP method (uppercase)
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 */
	public function method($method);

	/**
	 * The action to execute when this route matches, includes a file like
	 * it is called directly
	 *
	 * @param string $file
	 * @return $this
	 * @since 7.0.0
	 * @deprecated 32.0.0 Use a proper controller instead
	 */
	public function actionInclude($file);

	/**
	 * Specify GET as the method to use with this route
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 */
	public function get();

	/**
	 * Specify POST as the method to use with this route
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 */
	public function post();

	/**
	 * Specify DELETE as the method to use with this route
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 */
	public function delete();

	/**
	 * The action to execute when this route matches
	 *
	 * @param string|callable $class the class or a callable
	 * @param string $function the function to use with the class
	 * @return \OCP\Route\IRoute
	 *
	 * This function is called with $class set to a callable or
	 * to the class with $function
	 * @since 7.0.0
	 * @deprecated 32.0.0 Use a proper controller instead
	 */
	public function action($class, $function = null);

	/**
	 * Defaults to use for this route
	 *
	 * @param array $defaults The defaults
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 */
	public function defaults($defaults);

	/**
	 * Requirements for this route
	 *
	 * @param array $requirements The requirements
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 */
	public function requirements($requirements);

	/**
	 * Specify PUT as the method to use with this route
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 */
	public function put();
}
