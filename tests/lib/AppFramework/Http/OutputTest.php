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

	/** The buffer has to survive the flush so error handling can still use it */
	public function testFinishRequestKeepsTheOutputBuffer(): void {
		$this->skipIfConnectionCanBeClosed();
		$output = new Output('');

		ob_start();
		$levelBefore = ob_get_level();
		$closed = $output->finishRequest();
		$levelAfter = ob_get_level();
		ob_end_clean();

		$this->assertFalse($closed);
		$this->assertSame($levelBefore, $levelAfter);
	}

	/** Draining a handler that refuses to be flushed in a loop would never end */
	public function testFinishRequestWithNonFlushableBuffer(): void {
		$this->skipIfConnectionCanBeClosed();
		$output = new Output('');

		ob_start(
			static fn (string $buffer): string => $buffer,
			0,
			PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE,
		);
		$levelBefore = ob_get_level();
		$closed = $output->finishRequest();
		$levelAfter = ob_get_level();
		ob_end_clean();

		$this->assertFalse($closed);
		$this->assertSame($levelBefore, $levelAfter);
	}

	public function testFinishRequestWithoutAnyOutputBuffer(): void {
		$this->skipIfConnectionCanBeClosed();
		$output = new Output('');

		$level = ob_get_level();
		$this->assertFalse($output->finishRequest());
		$this->assertSame($level, ob_get_level());
	}

	private function skipIfConnectionCanBeClosed(): void {
		if (function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request')) {
			$this->markTestSkipped('This SAPI closes the connection, which would tear down the output buffer of the test runner');
		}
	}
}
