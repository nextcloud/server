<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

class FTP extends \OC\Files\Storage\StreamWrapper{
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
				if (is_string($params['secure'])) {
					$this->secure = ($params['secure'] === 'true');
				} else {
					$this->secure = (bool)$params['secure'];
				}
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
			throw new \Exception('Creating \OC\Files\Storage\FTP storage failed');
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
		$url.='://'.$this->user.':'.$this->password.'@'.$this->host.$this->root.$path;
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
				return fopen($this->constructUrl($path), $mode, false, $context);
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
