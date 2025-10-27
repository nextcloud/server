<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\CSRF;

use OCP\IRequest;
use OCP\Security\CSRF\ICsrfValidator;

class CsrfValidator implements ICsrfValidator {
	public function __construct(
		private CsrfTokenManager $csrfTokenManager
	) {
	}

	public function validate(IRequest $request): bool {
		if (!$request->passesStrictCookieCheck()) {
			return false;
		}

		if ($request->getHeader('OCS-APIRequest') !== '') {
			return true;
		}

		$token = $request->getParam('requesttoken', '');
		if ($token === '') {
			$token = $request->getHeader('REQUESTTOKEN');
		}
		if ($token === '') {
			return false;
		}

		$token = new CsrfToken($token);

		return $this->csrfTokenManager->isTokenValid($token);
	}
}
