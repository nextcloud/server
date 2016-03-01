<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Ross Nicoll <jrn@jrn.me.uk>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_External\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Files_External\Lib\Auth\PublicKey\RSA;

class AjaxController extends Controller {
	/** @var RSA */
	private $rsaMechanism;

	public function __construct($appName, IRequest $request, RSA $rsaMechanism) {
		parent::__construct($appName, $request);
		$this->rsaMechanism = $rsaMechanism;
	}

	private function generateSshKeys() {
		$key = $this->rsaMechanism->createKey();
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
