<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OCP;

/**
 * Class to generate URLs
 */
interface IURLGenerator {
	/**
	 * Returns the URL for a route
	 * @param string $routeName the name of the route
	 * @param array $arguments an array with arguments which will be filled into the url
	 * @return string the url
	 */
	public function linkToRoute($routeName, $arguments = array());

	/**
	 * Returns an URL for an image or file
	 * @param string $appName the name of the app
	 * @param string $file the name of the file
	 * @return string the url
	 */
	public function linkTo($appName, $file);

	/**
	 * Returns the link to an image, like linkTo but only with prepending img/
	 * @param string $appName the name of the app
	 * @param string $file the name of the file
	 * @return string the url
	 */
	public function imagePath($appName, $file);


	/**
	 * Makes an URL absolute
	 * @param string $url the url in the owncloud host
	 * @return string the absolute version of the url
	 */
	public function getAbsoluteURL($url);
}
