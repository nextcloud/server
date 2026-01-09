<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\Tests\Command;

use OCA\Files_Versions\Command\Expire;
use Test\TestCase;

/**
 * Class ExpireTest
 *
 *
 * @package OCA\Files_Versions\Tests\Command
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ExpireTest extends TestCase {
	public function testExpireNonExistingUser(): void {
		$command = new Expire($this->getUniqueID('test'), '');
		$command->handle();

		$this->addToAssertionCount(1);
	}
}
