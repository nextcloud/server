<?php
/**
 * Copyright (c) 2015 University of Edinburgh <Ross.Nicoll@ed.ac.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_External\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;

class AjaxController extends Controller {
	public function __construct($appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	private function generateSshKeys() {
		$rsa = new \Crypt_RSA();
		$rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_OPENSSH);
		$rsa->setPassword(\OC::$server->getConfig()->getSystemValue('secret', ''));

		$key = $rsa->createKey();
		// Replace the placeholder label with a more meaningful one
		$key['publicKey'] = str_replace('phpseclib-generated-key', gethostname(), $key['publickey']);

		return $key;
	}

	/**
	 * Generates an SSH public/private key pair.
	 *
	 * @NoAdminRequired
	 */
	public function getSshKeys() {
		$key = $this->generateSshKeys();
		return new JSONResponse(
			array('data' => array(
				'private_key' => $key['privatekey'],
				'public_key' => $key['publickey']
			),
			'status' => 'success'
		));
	}

}
