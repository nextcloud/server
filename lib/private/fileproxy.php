<?php

/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2011 Robin Appelman icewind1991@gmail.com
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
 * Class for manipulating filesystem requests
 *
 * Manipulation happens by using 2 kind of proxy operations, pre and post proxies
 * that manipulate the filesystem call and the result of the call respectively
 *
 * A pre-proxy recieves the filepath as arugments (or 2 filespaths in case of
 * operations like copy or move) and return a boolean
 * If a pre-proxy returns false the file operation will be canceled
 * All filesystem operations have a pre-proxy
 *
 * A post-proxy recieves 2 arguments, the filepath and the result of the operation.
 * The return value of the post-proxy will be used as the new result of the operation
 * The operations that have a post-proxy are:
 * file_get_contents, is_file, is_dir, file_exists, stat, is_readable,
 * is_writable, filemtime, filectime, file_get_contents,
 * getMimeType, hash, fopen, free_space and search
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
