<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Pavel Krasikov <klonishe@gmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sam Bull <aa6bs0@sambull.org>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
	private string $nonce = '';

	public function __construct(
		private CsrfTokenManager $csrfTokenManager,
		private IRequest $request,
	) {
	}

	/**
	 * Returns the current CSP nonce
	 */
	public function getNonce(): string {
		if ($this->nonce === '') {
			if (empty($this->request->server['CSP_NONCE'])) {
				$this->nonce = base64_encode($this->csrfTokenManager->getToken()->getEncryptedValue());
			} else {
				$this->nonce = $this->request->server['CSP_NONCE'];
			}
		}

		return $this->nonce;
	}

	/**
	 * Check if the browser supports CSP v3
	 */
	public function browserSupportsCspV3(): bool {
		$browserWhitelist = [
			Request::USER_AGENT_CHROME,
			Request::USER_AGENT_FIREFOX,
			Request::USER_AGENT_SAFARI,
			Request::USER_AGENT_MS_EDGE,
		];

		if ($this->request->isUserAgent($browserWhitelist)) {
			return true;
		}

		return false;
	}
}
