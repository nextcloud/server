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
use OCP\IConfig;

class SecurityTxtHandler implements IHandler {
	private const DEFAULT_SECURITY_TXT = 'Contact: https://hackerone.com/nextcloud
Expires: 2027-03-31T23:00:00.000Z
Acknowledgments: https://hackerone.com/nextcloud/thanks
Acknowledgments: https://github.com/nextcloud/security-advisories/security/advisories
Policy: https://hackerone.com/nextcloud
Preferred-Languages: en
';

	public function __construct(
		private IConfig $config,
	) {
	}

	#[\Override]
	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'security.txt') {
			return $previousResponse;
		}

		$response = trim($this->config->getSystemValueString('security_txt', ''));
		if ($response === '') {
			$response = self::DEFAULT_SECURITY_TXT;
		} else {
			$response .= "\n";
		}

		return new GenericResponse(new TextPlainResponse($response, 200));
	}
}
