<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Paginate;

use OCA\DAV\Paginate\LimitedCopyIterator;
use Test\TestCase;

class LimitedCopyIteratorTest extends TestCase {
	public function testBasic() {
		$data = [1, 2, 3, 4, 5, 6, 7];

		$copy = new LimitedCopyIterator(new \ArrayIterator($data), 5, 0);
		$this->assertEquals([1, 2, 3, 4, 5], iterator_to_array($copy->getRequestedItems()));
		$this->assertTrue($copy->hasOthers());
		$this->assertEquals($data, iterator_to_array($copy));

		$copy = new LimitedCopyIterator(new \ArrayIterator($data), 15, 0);
		$this->assertEquals([1, 2, 3, 4, 5, 6, 7], iterator_to_array($copy->getRequestedItems()));
		$this->assertFalse($copy->hasOthers());
		$this->assertEquals($data, iterator_to_array($copy));

		$copy = new LimitedCopyIterator(new \ArrayIterator($data), 15, 1);
		$this->assertEquals([1 => 2, 3, 4, 5, 6, 7], iterator_to_array($copy->getRequestedItems()));
		$this->assertTrue($copy->hasOthers());
		$this->assertEquals($data, iterator_to_array($copy));
	}

}
