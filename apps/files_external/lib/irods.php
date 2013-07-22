<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

set_include_path(get_include_path() . PATH_SEPARATOR .
	\OC_App::getAppPath('files_external') . '/3rdparty/irodsphp/prods/src');

require_once 'ProdsConfig.inc.php';
require_once 'ProdsStreamer.class.php';

class iRODS extends \OC\Files\Storage\StreamWrapper{
	private $password;
	private $user;
	private $host;
	private $port;
	private $zone;
	private $root;
	private $use_logon_credentials;
	private $auth_mode;

	public function __construct($params) {
		if (isset($params['host']) && isset($params['user']) && isset($params['password'])) {
			$this->host = $params['host'];
			$this->port = $params['port'];
			$this->user = $params['user'];
			$this->password = $params['password'];
			$this->use_logon_credentials = $params['use_logon_credentials'];
			$this->zone = $params['zone'];
			$this->auth_mode = isset($params['auth_mode']) ? $params['auth_mode'] : '';

			$this->root = isset($params['root']) ? $params['root'] : '/';
			if ( ! $this->root || $this->root[0] !== '/') {
				$this->root='/'.$this->root;
			}

			// take user and password from the session
			if ($this->use_logon_credentials && isset($_SESSION['irods-credentials']) )
			{
				$this->user = $_SESSION['irods-credentials']['uid'];
				$this->password = $_SESSION['irods-credentials']['password'];
			}

			//create the root folder if necessary
			if ( ! $this->is_dir('')) {
				$this->mkdir('');
			}
		} else {
			throw new \Exception();
		}
		
	}

	public static function login( $params ) {
		$_SESSION['irods-credentials'] = $params;
	}

	public function getId(){
		return 'irods::' . $this->user . '@' . $this->host . '/' . $this->root;
	}

	/**
	 * construct the rods url
	 * @param string $path
	 * @return string
	 */
	public function constructUrl($path) {
		$path = rtrim($path,'/');
		if ( $path === '' || $path[0] !== '/') {
			$path = '/'.$path;
		}

		// adding auth method
		$userWithZone = $this->user.'.'.$this->zone;
		if ($this->auth_mode !== '') {
			$userWithZone .= '.'.$this->auth_mode;
		}

		// url wrapper schema is named rods
		return 'rods://'.$userWithZone.':'.$this->password.'@'.$this->host.':'.$this->port.$this->root.$path;
	}

	public function filetype($path) {
		return @filetype($this->constructUrl($path));
	}

	public function mkdir($path) {
		return @mkdir($this->constructUrl($path));
	}

	public function touch($path, $mtime=null) {

		// we cannot set a time
		if ($mtime != null) {
			return false;
		}

		$path = $this->constructUrl($path);

		// if the file doesn't exist we create it
		if (!file_exists($path)) {
			file_put_contents($path, '');
			return true;
		}

		// mtime updates are not supported
		return false;
	}

	/**
	 * check if a file or folder has been updated since $time
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path,$time) {
		// this it a work around for folder mtimes -> we loop it's content
		if ( $this->is_dir($path)) {
			$actualTime=$this->collectionMTime($path);
			return $actualTime>$time;
		}

		$actualTime=$this->filemtime($path);
		return $actualTime>$time;
	}

	/**
	 * get the best guess for the modification time of an iRODS collection
	 */
	private function collectionMTime($path) {
		$dh = $this->opendir($path);
		$lastCTime = $this->filemtime($path);
		while ($file = readdir($dh)) {
			if ($file != '.' and $file != '..') {
				$time = $this->filemtime($file);
				if ($time > $lastCTime) {
					$lastCTime = $time;
				}
			}
		}
		return $lastCTime;
	}

}
