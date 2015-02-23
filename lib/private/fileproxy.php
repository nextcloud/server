<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
class OC_FileProxy{
	private static $proxies=array();
	public static $enabled=true;

	/**
	 * fallback function when a proxy operation is not implemented
	 * @param string $function the name of the proxy operation
	 * @param mixed $arguments
	 *
	 * this implements a dummy proxy for all operations
	 */
	public function __call($function, $arguments) {
		if(substr($function, 0, 3)=='pre') {
			return true;
		}else{
			return $arguments[1];
		}
	}

	/**
	 * register a proxy to be used
	 * @param OC_FileProxy $proxy
	 */
	public static function register($proxy) {
		self::$proxies[]=$proxy;
	}

	/**
	 * @param string $operation
	 */
	public static function getProxies($operation = null) {
		if ($operation === null) {
			// return all
			return self::$proxies;
		}
		$proxies=array();
		foreach(self::$proxies as $proxy) {
			if(method_exists($proxy, $operation)) {
				$proxies[]=$proxy;
			}
		}
		return $proxies;
	}

	/**
	 * @param string $operation
	 * @param string|boolean $filepath
	 */
	public static function runPreProxies($operation,&$filepath,&$filepath2=null) {
		if(!self::$enabled) {
			return true;
		}
		$operation='pre'.$operation;
		$proxies=self::getProxies($operation);
		foreach($proxies as $proxy) {
			if(!is_null($filepath2)) {
				if($proxy->$operation($filepath, $filepath2)===false) {
					return false;
				}
			}else{
				if($proxy->$operation($filepath)===false) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * @param string $operation
	 * @param string|boolean $path
	 *
	 * @return string
	 */
	public static function runPostProxies($operation, $path, $result) {
		if(!self::$enabled) {
			return $result;
		}
		$operation='post'.$operation;
		$proxies=self::getProxies($operation);
		foreach($proxies as $proxy) {
			$result=$proxy->$operation($path, $result);
		}
		return $result;
	}

	public static function clearProxies() {
		self::$proxies=array();
	}
}
