<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Ip;

use OCP\Security\Ip\IAddress;
use OCP\Security\Ip\IFactory;
use OCP\Security\Ip\IRange;

class Factory implements IFactory {
	public function rangeFromString(string $range): IRange {
		return new Range($range);
	}

	public function addressFromString(string $ip): IAddress {
		return new Address($ip);
	}
}
