<?php
/**
 * Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http\Output;

class OutputTest extends \Test\TestCase {
	public function testSetOutput() {
		$this->expectOutputString('foo');
		$output = new Output('');
		$output->setOutput('foo');
	}

	public function testSetReadfile() {
		$this->expectOutputString(file_get_contents(__FILE__));
		$output = new Output('');
		$output->setReadfile(__FILE__);
	}

	public function testSetReadfileStream() {
		$this->expectOutputString(file_get_contents(__FILE__));
		$output = new Output('');
		$output->setReadfile(fopen(__FILE__, 'r'));
	}
}
