<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Snowflake;

use OC\Snowflake\ISequence;
use Test\TestCase;

/**
 * @package Test
 */
abstract class ISequenceBase extends TestCase {
	protected ISequence $sequence;

	public function testGenerator(): void {
		if (!$this->sequence->isAvailable()) {
			$this->markTestSkipped('Sequence ID generator ' . get_class($this->sequence) . 'is’nt available. Skip');
		}

		$nb = 1000;
		$ids = [];
		$server = 42;
		for ($i = 0; $i < $nb; ++$i) {
			$time = explode('.', (string)microtime(true));
			$seconds = (int)$time[0];
			$milliseconds = str_pad(substr($time[1] ?? '0', 0, 3), 3, '0');
			$id = $this->sequence->nextId($server, $seconds, (int)$milliseconds);
			$ids[] = sprintf('%d_%s_%d', $seconds, $milliseconds, $id);
			usleep(100);
		}

		// Is it unique?
		$this->assertCount($nb, array_unique($ids));
		// Is it sequential?
		$sortedIds = $ids;
		natsort($sortedIds);
		$this->assertSame($sortedIds, $ids);
	}
}
