<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\WellKnown;

use OCP\AppFramework\Http\TextPlainResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;

class SecurityTxtHandler implements IHandler {
	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'security.txt') {
			return $previousResponse;
		}

		$response = 'Contact: https://hackerone.com/nextcloud
Expires: 2025-08-31T23:00:00.000Z
Acknowledgments: https://hackerone.com/nextcloud/thanks
Acknowledgments: https://github.com/nextcloud/security-advisories/security/advisories
Policy: https://hackerone.com/nextcloud
Preferred-Languages: en
';

		return new GenericResponse(new TextPlainResponse($response, 200));
	}
}
