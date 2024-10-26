<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http\Output;

class OutputTest extends \Test\TestCase {
	public function testSetOutput(): void {
		$this->expectOutputString('foo');
		$output = new Output('');
		$output->setOutput('foo');
	}

	public function testSetReadfile(): void {
		$this->expectOutputString(file_get_contents(__FILE__));
		$output = new Output('');
		$output->setReadfile(__FILE__);
	}

	public function testSetReadfileStream(): void {
		$this->expectOutputString(file_get_contents(__FILE__));
		$output = new Output('');
		$output->setReadfile(fopen(__FILE__, 'r'));
	}
}
