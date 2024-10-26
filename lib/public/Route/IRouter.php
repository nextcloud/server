<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Route;

/**
 * Interface IRouter
 *
 * @since 7.0.0
 * @deprecated 9.0.0
 */
interface IRouter {
	/**
	 * Create a \OCP\Route\IRoute.
	 *
	 * @param string $name Name of the route to create.
	 * @param string $pattern The pattern to match
	 * @param array $defaults An array of default parameter values
	 * @param array $requirements An array of requirements for parameters (regexes)
	 * @return \OCP\Route\IRoute
	 * @since 7.0.0
	 * @deprecated 9.0.0
	 */
	public function create($name, $pattern, array $defaults = [], array $requirements = []);
}
