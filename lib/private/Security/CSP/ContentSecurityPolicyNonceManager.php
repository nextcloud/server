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

use OC\Security\CSRF\CsrfTokenManager;

/**
 * @package OC\Security\CSP
 */
class ContentSecurityPolicyNonceManager {
	/** @var CsrfTokenManager */
	private $csrfTokenManager;
	/** @var string */
	private $nonce = '';

	/**
	 * @param CsrfTokenManager $csrfTokenManager
	 */
	public function __construct(CsrfTokenManager $csrfTokenManager) {
		$this->csrfTokenManager = $csrfTokenManager;
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
}
