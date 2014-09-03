<?php
/**
 * Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

require_once __DIR__ . '/../3rdparty/smb4php/smb.php';

class SMB_OC extends \OC\Files\Storage\SMB {
	private $username_as_share;

	public function __construct($params) {
		if (isset($params['host']) && \OC::$server->getSession()->exists('smb-credentials')) {
			$host=$params['host'];
			$this->username_as_share = ($params['username_as_share'] === 'true');

			$params_auth = \OC::$server->getSession()->get('smb-credentials');
			$user = \OC::$server->getSession()->get('loginname');
			$password = $params_auth['password'];

			$root=isset($params['root'])?$params['root']:'/';
			$share = '';

			if ($this->username_as_share) {
				$share = '/'.$user;
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

	public static function login( $params ) {
		\OC::$server->getSession()->set('smb-credentials', $params);
	}

	public function isSharable($path) {
		return false;
	}

	public function test($isPersonal = true) {
		if ($isPersonal) {
			if ($this->stat('')) {
				return true;
			}
			return false;
		} else {
			$smb = new \smb();
			$pu = $smb->parse_url($this->constructUrl(''));

			// Attempt to connect anonymously
			$pu['user'] = '';
			$pu['pass'] = '';

			// Share cannot be checked if dynamic
			if ($this->username_as_share) {
				if ($smb->look($pu)) {
					return true;
				} else {
					return false;
				}
			}
			if (!$pu['share']) {
				return false;
			}

			// The following error messages are expected due to anonymous login
			$regexp = array(
				'(NT_STATUS_ACCESS_DENIED)' => 'skip'
			) + $smb->getRegexp();

			if ($smb->client("-d 0 " . escapeshellarg('//' . $pu['host'] . '/' . $pu['share']) . " -c exit", $pu, $regexp)) {
				return true;
			} else {
				return false;
			}
		}
	}
}
