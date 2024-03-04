<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP;

/**
 * Class to generate URLs
 * @since 6.0.0
 */
interface IURLGenerator {
	/**
	 * Regex for matching http(s) urls
	 *
	 * This is a copy of the frontend regex in core/src/OCP/comments.js, make sure to adjust both when changing
	 *
	 * @since 25.0.0
	 */
	public const URL_REGEX = '/' . self::URL_REGEX_NO_MODIFIERS . '/mi';

	/**
	 * Regex for matching http(s) urls (without modifiers for client compatibility)
	 *
	 * This is a copy of the frontend regex in core/src/OCP/comments.js, make sure to adjust both when changing
	 *
	 * @since 25.0.0
	 */
	public const URL_REGEX_NO_MODIFIERS = '(\s|\n|^)(https?:\/\/)((?:[-A-Z0-9+_]+\.)+[-A-Z]+(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|\n|$)';

	/**
	 * Returns the URL for a route
	 * @param string $routeName the name of the route
	 * @param array $arguments an array with arguments which will be filled into the url
	 * @return string the url
	 * @since 6.0.0
	 */
	public function linkToRoute(string $routeName, array $arguments = []): string;

	/**
	 * Returns the absolute URL for a route
	 * @param string $routeName the name of the route
	 * @param array $arguments an array with arguments which will be filled into the url
	 * @return string the absolute url
	 * @since 8.0.0
	 */
	public function linkToRouteAbsolute(string $routeName, array $arguments = []): string;

	/**
	 * @param string $routeName
	 * @param array $arguments
	 * @return string
	 * @since 15.0.0
	 */
	public function linkToOCSRouteAbsolute(string $routeName, array $arguments = []): string;

	/**
	 * Returns an URL for an image or file
	 * @param string $appName the name of the app
	 * @param string $file the name of the file
	 * @param array $args array with param=>value, will be appended to the returned url
	 *    The value of $args will be urlencoded
	 * @return string the url
	 * @since 6.0.0
	 */
	public function linkTo(string $appName, string $file, array $args = []): string;

	/**
	 * Returns the link to an image, like linkTo but only with prepending img/
	 * @param string $appName the name of the app
	 * @param string $file the name of the file
	 * @return string the url
	 * @throws \RuntimeException If the image does not exist
	 * @since 6.0.0
	 */
	public function imagePath(string $appName, string $file): string;


	/**
	 * Makes an URL absolute
	 * @param string $url the url in the ownCloud host
	 * @return string the absolute version of the url
	 * @since 6.0.0
	 */
	public function getAbsoluteURL(string $url): string;

	/**
	 * @param string $key
	 * @return string url to the online documentation
	 * @since 8.0.0
	 */
	public function linkToDocs(string $key): string;

	/**
	 * Returns the URL of the default page based on the system configuration
	 * and the apps visible for the current user
	 * @return string
	 * @since 23.0.0
	 */
	public function linkToDefaultPageUrl(): string;

	/**
	 * @return string base url of the current request
	 * @since 13.0.0
	 */
	public function getBaseUrl(): string;

	/**
	 * @return string webroot part of the base url
	 * @since 23.0.0
	 */
	public function getWebroot(): string;
}
