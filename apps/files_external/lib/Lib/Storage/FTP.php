<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Senorsen <senorsen.zhang@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Lib\Storage;

use Icewind\Streams\RetryWrapper;

class FTP extends StreamWrapper{
	private $password;
	private $user;
	private $host;
	private $secure;
	private $root;

	private static $tempFiles=array();

	public function __construct($params) {
		if (isset($params['host']) && isset($params['user']) && isset($params['password'])) {
			$this->host=$params['host'];
			$this->user=$params['user'];
			$this->password=$params['password'];
			if (isset($params['secure'])) {
				$this->secure = $params['secure'];
			} else {
				$this->secure = false;
			}
			$this->root=isset($params['root'])?$params['root']:'/';
			if ( ! $this->root || $this->root[0]!='/') {
				$this->root='/'.$this->root;
			}
			if (substr($this->root, -1) !== '/') {
				$this->root .= '/';
			}
		} else {
			throw new \Exception('Creating FTP storage failed');
		}
		
	}

	public function getId(){
		return 'ftp::' . $this->user . '@' . $this->host . '/' . $this->root;
	}

	/**
	 * construct the ftp url
	 * @param string $path
	 * @return string
	 */
	public function constructUrl($path) {
		$url='ftp';
		if ($this->secure) {
			$url.='s';
		}
		$url.='://'.urlencode($this->user).':'.urlencode($this->password).'@'.$this->host.$this->root.$path;
		return $url;
	}

	/**
	 * Unlinks file or directory
	 * @param string $path
	 */
	public function unlink($path) {
		if ($this->is_dir($path)) {
			return $this->rmdir($path);
		}
		else {
			$url = $this->constructUrl($path);
			$result = unlink($url);
			clearstatcache(true, $url);
			return $result;
		}
	}
	public function fopen($path,$mode) {
		switch($mode) {
			case 'r':
			case 'rb':
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
				//these are supported by the wrapper
				$context = stream_context_create(array('ftp' => array('overwrite' => true)));
				$handle = fopen($this->constructUrl($path), $mode, false, $context);
				return RetryWrapper::wrap($handle);
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				//emulate these
				if (strrpos($path, '.')!==false) {
					$ext=substr($path, strrpos($path, '.'));
				} else {
					$ext='';
				}
				$tmpFile=\OCP\Files::tmpFile($ext);
				\OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
				if ($this->file_exists($path)) {
					$this->getFile($path, $tmpFile);
				}
				self::$tempFiles[$tmpFile]=$path;
				return fopen('close://'.$tmpFile, $mode);
		}
		return false;
	}

	public function writeBack($tmpFile) {
		if (isset(self::$tempFiles[$tmpFile])) {
			$this->uploadFile($tmpFile, self::$tempFiles[$tmpFile]);
			unlink($tmpFile);
		}
	}

	/**
	 * check if php-ftp is installed
	 */
	public static function checkDependencies() {
		if (function_exists('ftp_login')) {
			return(true);
		} else {
			return array('ftp');
		}
	}

}
