<?php
/**
 * Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;


use Icewind\SMB\Exception\AccessDeniedException;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Server;

class SMB_OC extends SMB {
	private $username_as_share;

	/**
	 * @param array $params
	 * @throws \Exception
	 */
	public function __construct($params) {
		if (isset($params['host'])) {
			$host = $params['host'];
			$this->username_as_share = ($params['username_as_share'] === 'true');

			// dummy credentials, unused, to satisfy constructor
			$user = 'foo';
			$password = 'bar';
			if (\OC::$server->getSession()->exists('smb-credentials')) {
				$params_auth = json_decode(\OC::$server->getCrypto()->decrypt(\OC::$server->getSession()->get('smb-credentials')), true);
				$user = \OC::$server->getSession()->get('loginname');
				$password = $params_auth['password'];
			} else {
				// assume we are testing from the admin section
			}

			$root = isset($params['root']) ? $params['root'] : '/';
			$share = '';

			if ($this->username_as_share) {
				$share = '/' . $user;
			} elseif (isset($params['share'])) {
				$share = $params['share'];
			} else {
				throw new \Exception();
			}
			parent::__construct(array(
				"user" => $user,
				"password" => $password,
				"host" => $host,
				"share" => $share,
				"root" => $root
			));
		} else {
			throw new \Exception();
		}
	}


	/**
	 * Intercepts the user credentials on login and stores them
	 * encrypted inside the session if SMB_OC storage is enabled.
	 * @param array $params
	 */
	public static function login($params) {
		$mountpoints = \OC_Mount_Config::getAbsoluteMountPoints($params['uid']);
		$mountpointClasses = array();
		foreach($mountpoints as $mountpoint) {
			$mountpointClasses[$mountpoint['class']] = true;
		}
		if(isset($mountpointClasses['\OC\Files\Storage\SMB_OC'])) {
			\OC::$server->getSession()->set('smb-credentials', \OC::$server->getCrypto()->encrypt(json_encode($params)));
		}
	}

	/**
	 * @param string $path
	 * @return boolean
	 */
	public function isSharable($path) {
		return false;
	}

	/**
	 * @param bool $isPersonal
	 * @return bool
	 */
	public function test($isPersonal = true) {
		if ($isPersonal) {
			if ($this->stat('')) {
				return true;
			}
			return false;
		} else {
			$server = new Server($this->server->getHost(), '', '');

			try {
				$server->listShares();
				return true;
			} catch (AccessDeniedException $e) {
				// expected due to anonymous login
				return true;
			} catch (Exception $e) {
				return false;
			}
		}
	}
}
