<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCP\Route;

interface IRoute {
	/**
	 * Specify PATCH as the method to use with this route
	 * @return \OCP\Route\IRoute
	 */
	public function patch();

	/**
	 * Specify the method when this route is to be used
	 *
	 * @param string $method HTTP method (uppercase)
	 * @return \OCP\Route\IRoute
	 */
	public function method($method);

	/**
	 * The action to execute when this route matches, includes a file like
	 * it is called directly
	 *
	 * @param string $file
	 * @return void
	 */
	public function actionInclude($file);

	/**
	 * Specify GET as the method to use with this route
	 * @return \OCP\Route\IRoute
	 */
	public function get();

	/**
	 * Specify POST as the method to use with this route
	 * @return \OCP\Route\IRoute
	 */
	public function post();

	/**
	 * Specify DELETE as the method to use with this route
	 * @return \OCP\Route\IRoute
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
	 */
	public function action($class, $function = null);

	/**
	 * Defaults to use for this route
	 *
	 * @param array $defaults The defaults
	 * @return \OCP\Route\IRoute
	 */
	public function defaults($defaults);

	/**
	 * Requirements for this route
	 *
	 * @param array $requirements The requirements
	 * @return \OCP\Route\IRoute
	 */
	public function requirements($requirements);

	/**
	 * Specify PUT as the method to use with this route
	 * @return \OCP\Route\IRoute
	 */
	public function put();
}
