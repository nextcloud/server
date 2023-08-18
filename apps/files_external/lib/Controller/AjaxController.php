<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Ross Nicoll <jrn@jrn.me.uk>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Controller;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\Auth\PublicKey\RSA;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class AjaxController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private RSA $rsaMechanism,
		private GlobalAuth $globalAuth,
		private IUserSession $userSession
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param int $keyLength
	 * @return array
	 */
	private function generateSshKeys(int $keyLength): array {
		$key = $this->rsaMechanism->createKey($keyLength);
		// Replace the placeholder label with a more meaningful one
		$key['publickey'] = str_replace('phpseclib-generated-key', gethostname(), $key['publickey']);

		return $key;
	}

	/**
	 * Generates an SSH public/private key pair.
	 *
	 * @NoAdminRequired
	 *
	 * @param int $keyLength
	 * @return JSONResponse
	 */
	public function getSshKeys(int $keyLength = 1024): JSONResponse {
		$key = $this->generateSshKeys($keyLength);
		return new JSONResponse(
			['data' => [
				'private_key' => $key['privatekey'],
				'public_key' => $key['publickey']
			],
				'status' => 'success'
			]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $uid
	 * @param string $user
	 * @param string $password
	 * @return bool
	 */
	public function saveGlobalCredentials(string $uid, string $user, string $password): bool {
		if ($this->userSession->getUser()->getUID() !== $uid) {
			return false;
		}

		$this->globalAuth->saveAuth($uid, $user, $password);

		return true;
	}
}
