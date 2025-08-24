<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Tests\Command;

use OCA\Files_Trashbin\Command\Expire;
use Test\TestCase;

/**
 * Class ExpireTest
 *
 * @group DB
 *
 * @package OCA\Files_Trashbin\Tests\Command
 */
class ExpireTest extends TestCase {
	public function testExpireNonExistingUser(): void {
		$command = new Expire('test');
		$command->handle();

		$this->addToAssertionCount(1);
	}
}
