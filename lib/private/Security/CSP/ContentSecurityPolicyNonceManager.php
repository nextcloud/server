<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
				// Get the token from the CSRF token, we only use the "shared secret" part
				// as the first part does not add any security / entropy to the token
				// so it can be ignored to keep the nonce short while keeping the same randomness
				$csrfSecret = explode(':', ($this->csrfTokenManager->getToken()->getEncryptedValue()));
				$this->nonce = end($csrfSecret);
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
		$browserBlocklist = [
			Request::USER_AGENT_IE,
		];

		if ($this->request->isUserAgent($browserBlocklist)) {
			return false;
		}

		return true;
	}
}
