<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security;

use IPLib\Factory;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class RemoteIpAddress {
	public const SETTING_NAME = 'allowed_admin_ranges';

	public function __construct(
		private IConfig $config,
		private IRequest $request,
		private LoggerInterface $logger,
	) {
	}

	public function allowsAdminActions(): bool {
		$allowedAdminRanges = $this->config->getSystemValue(self::SETTING_NAME, false);
		if ($allowedAdminRanges === false) {
			// No restriction applied
			return true;
		}

		if (!is_array($allowedAdminRanges)) {
			return true;
		}

		if (empty($allowedAdminRanges)) {
			return true;
		}

		$ipAddress = Factory::parseAddressString($this->request->getRemoteAddress());
		if ($ipAddress === null) {
			$this->logger->warning(
				'Unable to parse remote IP "{ip}"',
				['ip' => $ipAddress,]
			);

			return false;
		}

		foreach ($allowedAdminRanges as $rangeString) {
			$range = Factory::parseRangeString($rangeString);
			if ($range === null) {
				continue;
			}
			if ($range->contains($ipAddress)) {
				return true;
			}
		}

		return false;
	}
}
