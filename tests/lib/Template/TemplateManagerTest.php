<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Template;

use Test\TestCase;

class TemplateManagerTest extends TestCase {
	/**
	 * PHPUnit buffers the test output, so headers are never sent inside the
	 * test itself. Run the code in a separate PHP process where output really starts.
	 */
	private function runAfterOutput(string $code): string {
		$autoload = \OC::$SERVERROOT . '/lib/composer/autoload.php';
		$script = 'require ' . var_export($autoload, true) . '; echo "output"; flush(); ' . $code;
		$command = escapeshellarg(PHP_BINARY) . ' -d display_errors=stderr -d error_reporting=-1 -r ' . escapeshellarg($script) . ' 2>&1';
		return (string)shell_exec($command);
	}

	public function testHttpResponseCodeWarnsAfterOutput(): void {
		$this->assertStringContainsString('headers already sent', $this->runAfterOutput('http_response_code(500);'));
	}

	public function testSetStatusCodeAfterOutput(): void {
		$output = $this->runAfterOutput(
			'$templateManager = (new ReflectionClass(\OC\Template\TemplateManager::class))->newInstanceWithoutConstructor();'
			. ' (new ReflectionMethod($templateManager, "setStatusCode"))->invoke($templateManager, 500);'
		);

		$this->assertSame('output', $output);
	}
}
