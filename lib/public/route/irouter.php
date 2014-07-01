<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Route;

interface IRouter {

	/**
	 * Get the files to load the routes from
	 *
	 * @return string[]
	 */
	public function getRoutingFiles();

	/**
	 * @return string
	 */
	public function getCacheKey();

	/**
	 * loads the api routes
	 * @return void
	 */
	public function loadRoutes($app = null);

	/**
	 * Sets the collection to use for adding routes
	 *
	 * @param string $name Name of the collection to use.
	 * @return void
	 */
	public function useCollection($name);

	/**
	 * returns the current collection name in use for adding routes
	 *
	 * @return string the collection name
	 */
	public function getCurrentCollection();

	/**
	 * Create a \OCP\Route\IRoute.
	 *
	 * @param string $name Name of the route to create.
	 * @param string $pattern The pattern to match
	 * @param array $defaults An array of default parameter values
	 * @param array $requirements An array of requirements for parameters (regexes)
	 * @return \OCP\Route\IRoute
	 */
	public function create($name, $pattern, array $defaults = array(), array $requirements = array());

	/**
	 * Find the route matching $url.
	 *
	 * @param string $url The url to find
	 * @throws \Exception
	 * @return void
	 */
	public function match($url);

	/**
	 * Get the url generator
	 *
	 */
	public function getGenerator();

	/**
	 * Generate url based on $name and $parameters
	 *
	 * @param string $name Name of the route to use.
	 * @param array $parameters Parameters for the route
	 * @param bool $absolute
	 * @return string
	 */
	public function generate($name, $parameters = array(), $absolute = false);

}
