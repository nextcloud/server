<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Kernel;

use OCP\AppFramework\Http\Response;
use OCP\IRequest;

class HttpKernel extends Kernel implements IHttpKernel {
	public function handle(IRequest $request, bool $catch = true): Response {

	}

	public function boot(): void {
		$this->handleAuthHeaders();
		parent::boot();
	}

	private function handleAuthHeaders(): void {
		//copy http auth headers for apache+php-fcgid work around
		if (isset($_SERVER['HTTP_XAUTHORIZATION']) && !isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['HTTP_XAUTHORIZATION'];
		}

		// Extract PHP_AUTH_USER/PHP_AUTH_PW from other headers if necessary.
		$vars = [
			'HTTP_AUTHORIZATION', // apache+php-cgi work around
			'REDIRECT_HTTP_AUTHORIZATION', // apache+php-cgi alternative
		];
		foreach ($vars as $var) {
			if (isset($_SERVER[$var]) && is_string($_SERVER[$var]) && preg_match('/Basic\s+(.*)$/i', $_SERVER[$var], $matches)) {
				$credentials = explode(':', base64_decode($matches[1]), 2);
				if (count($credentials) === 2) {
					$_SERVER['PHP_AUTH_USER'] = $credentials[0];
					$_SERVER['PHP_AUTH_PW'] = $credentials[1];
					break;
				}
			}
		}
	}
}
