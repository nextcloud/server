<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\LargeFileHelper;

class LargeFileHelperTest extends TestCase {
	protected $helper;

	protected function setUp(): void {
		parent::setUp();
		$this->helper = new LargeFileHelper;
	}

	public function testFormatUnsignedIntegerFloat(): void {
		$this->assertSame(
			'9007199254740992',
			$this->helper->formatUnsignedInteger((float)9007199254740992)
		);
	}

	public function testFormatUnsignedIntegerInt(): void {
		$this->assertSame(
			PHP_INT_SIZE === 4 ? '4294967295' : '18446744073709551615',
			$this->helper->formatUnsignedInteger(-1)
		);
	}

	public function testFormatUnsignedIntegerString(): void {
		$this->assertSame(
			'9007199254740993',
			$this->helper->formatUnsignedInteger('9007199254740993')
		);
	}

	
	public function testFormatUnsignedIntegerStringException(): void {
		$this->expectException(\UnexpectedValueException::class);

		$this->helper->formatUnsignedInteger('900ABCD254740993');
	}
}
