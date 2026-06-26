<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Talk;

use OC\Talk\ConversationOptions;
use Test\TestCase;

class ConversationOptionsTest extends TestCase {
	public function testDefaults(): void {
		ConversationOptions::default();

		$this->addToAssertionCount(1);
	}
}
