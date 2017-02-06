<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Security\CSP;

use OC\AppFramework\Http\Request;
use OC\Security\CSRF\CsrfTokenManager;
use OCP\IRequest;

/**
 * @package OC\Security\CSP
 */
class ContentSecurityPolicyNonceManager {
	/** @var CsrfTokenManager */
	private $csrfTokenManager;
	/** @var IRequest */
	private $request;
	/** @var string */
	private $nonce = '';

	/**
	 * @param CsrfTokenManager $csrfTokenManager
	 * @param IRequest $request
	 */
	public function __construct(CsrfTokenManager $csrfTokenManager,
								IRequest $request) {
		$this->csrfTokenManager = $csrfTokenManager;
		$this->request = $request;
	}

	/**
	 * Returns the current CSP nounce
	 *
	 * @return string
	 */
	public function getNonce() {
		if($this->nonce === '') {
			$this->nonce = base64_encode($this->csrfTokenManager->getToken()->getEncryptedValue());
		}

		return $this->nonce;
	}

	/**
	 * Check if the browser supports CSP v3
	 *
	 * @return bool
	 */
	public function browserSupportsCspV3() {
		$browserWhitelist = [
			Request::USER_AGENT_CHROME,
			// Firefox 45+
			'/^Mozilla\/5\.0 \([^)]+\) Gecko\/[0-9.]+ Firefox\/(4[5-9]|[5-9][0-9])\.[0-9.]+$/',
		];

		if($this->request->isUserAgent($browserWhitelist)) {
			return true;
		}

		return false;
	}
}
