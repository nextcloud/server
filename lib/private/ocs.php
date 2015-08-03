<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
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
use OCP\API;

/**
 * Class to handle open collaboration services API requests
 *
 */
class OC_OCS {

	/**
	* reads input data from get/post and converts the date to a special data-type
	*
	* @param string $method HTTP method to read the key from
	* @param string $key Parameter to read
	* @param string $type Variable type to format data
	* @param string $default Default value to return if the key is not found
	* @return string Data or if the key is not found and no default is set it will exit with a 400 Bad request
	*/
	public static function readData($method, $key, $type = 'raw', $default = null) {
		$data = false;
		if ($method == 'get') {
			if (isset($_GET[$key])) {
				$data = $_GET[$key];
			} else if (isset($default)) {
				return $default;
			} else {
				$data = false;
			}
		} else if ($method == 'post') {
			if (isset($_POST[$key])) {
				$data = $_POST[$key];
			} else if (isset($default)) {
				return $default;
			} else {
				$data = false;
			}
		}
		if ($data === false) {
			throw new \OC\OCS\Exception(new OC_OCS_Result(null, 400, 'Bad request. Please provide a valid '.$key));
		} else {
			// NOTE: Is the raw type necessary? It might be a little risky without sanitization
			if ($type == 'raw') return $data;
			elseif ($type == 'text') return OC_Util::sanitizeHTML($data);
			elseif ($type == 'int')  return (int) $data;
			elseif ($type == 'float') return (float) $data;
			elseif ($type == 'array') return OC_Util::sanitizeHTML($data);
			else return OC_Util::sanitizeHTML($data);
		}
	}

	public static function notFound() {
		$format = OC_API::requestedFormat();
		$txt='Invalid query, please check the syntax. API specifications are here:'
		.' http://www.freedesktop.org/wiki/Specifications/open-collaboration-services. DEBUG OUTPUT:'."\n";
		$txt.=OC_OCS::getDebugOutput();

		OC_API::respond(new OC_OCS_Result(null, API::RESPOND_UNKNOWN_ERROR, $txt), $format);
	}

	/**
	* generated some debug information to make it easier to find failed API calls
	* @return string data
	*/
	private static function getDebugOutput() {
		$txt='';
		$txt.="debug output:\n";
		if(isset($_SERVER['REQUEST_METHOD'])) $txt.='http request method: '.$_SERVER['REQUEST_METHOD']."\n";
		if(isset($_SERVER['REQUEST_URI'])) $txt.='http request uri: '.$_SERVER['REQUEST_URI']."\n";
		if(isset($_GET)) foreach($_GET as $key=>$value) $txt.='get parameter: '.$key.'->'.$value."\n";
		if(isset($_POST)) foreach($_POST as $key=>$value) $txt.='post parameter: '.$key.'->'.$value."\n";
		return($txt);
	}
}
