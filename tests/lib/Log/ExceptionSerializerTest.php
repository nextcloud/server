<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Log;

use OC\Log\ExceptionSerializer;
use OC\SystemConfig;
use Test\TestCase;

class ExceptionSerializerTest extends TestCase {
	private ExceptionSerializer $serializer;

	public function setUp(): void {
		parent::setUp();

		$config = $this->createMock(SystemConfig::class);
		$this->serializer = new ExceptionSerializer($config);
	}

	private function emit($arguments) {
		\call_user_func_array([$this, 'bind'], $arguments);
	}

	private function bind(array &$myValues): void {
		throw new \Exception('my exception');
	}

	private function customMagicAuthThing(string $login, string $parole): void {
		throw new \Exception('expected custom auth exception');
	}

	/**
	 * this test ensures that the serializer does not overwrite referenced
	 * variables. It is crafted after a scenario we experienced: the DAV server
	 * emitting the "validateTokens" event, of which later on a handled
	 * exception was passed to the logger. The token was replaced, the original
	 * variable overwritten.
	 */
	public function testSerializer(): void {
		try {
			$secret = ['Secret'];
			$this->emit([&$secret]);
		} catch (\Exception $e) {
			$serializedData = $this->serializer->serializeException($e);
			$this->assertSame(['Secret'], $secret);
			$this->assertSame(ExceptionSerializer::SENSITIVE_VALUE_PLACEHOLDER, $serializedData['Trace'][0]['args'][0]);
		}
	}

	public function testSerializerWithRegisteredMethods(): void {
		$this->serializer->enlistSensitiveMethods(self::class, ['customMagicAuthThing']);
		try {
			$this->customMagicAuthThing('u57474', 'Secret');
		} catch (\Exception $e) {
			$serializedData = $this->serializer->serializeException($e);
			$this->assertSame('customMagicAuthThing', $serializedData['Trace'][0]['function']);
			$this->assertSame(ExceptionSerializer::SENSITIVE_VALUE_PLACEHOLDER, $serializedData['Trace'][0]['args'][0]);
			$this->assertFalse(isset($serializedData['Trace'][0]['args'][1]));
		}
	}
}
