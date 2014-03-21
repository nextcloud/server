<?php
/**
 * Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

class SMB_Auto extends \OC\Files\Storage\SMB{
	public function __construct($params) {
		if (isset($params['host']) && \OC::$session->exists('smb-credentials')) {
			$host=$params['host'];
			$username_as_share = ($params['username_as_share'] === 'true');

			$params_auth = \OC::$session->get('smb-credentials');
			$user = \OC_User::getDisplayName($params_auth['uid']);
			$password = $params_auth['password'];

			$root=isset($params['root'])?$params['root']:'/';
			$share = '';

			if ($username_as_share) {
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
		\OC::$session->set('smb-credentials', $params);
	}
}
