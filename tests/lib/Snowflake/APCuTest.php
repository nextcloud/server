<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Snowflake;

use OC\Snowflake\APCuSequence;

/**
 * @package Test
 */
class APCuTest extends ISequenceBase {
	private string $path;

	public function setUp():void {
		$this->sequence = new APCuSequence();
	}
}
