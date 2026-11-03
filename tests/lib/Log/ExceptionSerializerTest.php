<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Log;

use OC\Log\ExceptionSerializer;
use OC\SystemConfig;
use PHPUnit\Framework\Attributes\RequiresPhp;
use Test\TestCase;

class LazyObjectFixture {
	public string $value = 'value';
}

class ExceptionSerializerTest extends TestCase {
	private ExceptionSerializer $serializer;

	#[\Override]
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
		$this->throwsException($parole);
	}

	private function customSensitiveParameter(
		string $login,
		#[\SensitiveParameter]
		string $password,
	): void {
		$this->throwsException($password);
	}

	private function throwsException(string $maybeSecret): void {
		throw new \Exception('expected exception');
	}

	private function throwsWithObject(object $object): void {
		throw new \Exception('expected exception');
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
			$this->assertSame('throwsException', $serializedData['Trace'][0]['function']);
			$this->assertSame(ExceptionSerializer::SENSITIVE_VALUE_PLACEHOLDER, $serializedData['Trace'][0]['args'][0]);
			$this->assertSame('customMagicAuthThing', $serializedData['Trace'][1]['function']);
			$this->assertSame(ExceptionSerializer::SENSITIVE_VALUE_PLACEHOLDER, $serializedData['Trace'][1]['args'][0]);
			$this->assertFalse(isset($serializedData['Trace'][1]['args'][1]));
		}
	}

	public function testSerializerWithSensitiveParameter(): void {
		try {
			$this->customSensitiveParameter('login', 'Secret');
		} catch (\Exception $e) {
			$serializedData = $this->serializer->serializeException($e);
			$this->assertSame('throwsException', $serializedData['Trace'][0]['function']);
			$this->assertSame(ExceptionSerializer::SENSITIVE_VALUE_PLACEHOLDER, $serializedData['Trace'][0]['args'][0]);
			$this->assertSame('customSensitiveParameter', $serializedData['Trace'][1]['function']);
			$this->assertSame('login', $serializedData['Trace'][1]['args'][0]);
			$this->assertSame(ExceptionSerializer::SENSITIVE_VALUE_PLACEHOLDER, $serializedData['Trace'][1]['args'][1]);
		}
	}

	#[RequiresPhp('>= 8.4')]
	public function testSerializerDoesNotInitializeLazyObjects(): void {
		$initialized = false;
		$ghost = (new \ReflectionClass(LazyObjectFixture::class))->newLazyGhost(function () use (&$initialized): void {
			$initialized = true;
			throw new \RuntimeException('initializer must not run');
		});

		$ignoreArgs = ini_set('zend.exception_ignore_args', '0');
		try {
			$this->throwsWithObject($ghost);
		} catch (\Exception $e) {
			$serializedData = $this->serializer->serializeException($e);
		} finally {
			ini_set('zend.exception_ignore_args', (string)$ignoreArgs);
		}

		$this->assertFalse($initialized);
		$this->assertSame(LazyObjectFixture::class, $serializedData['Trace'][0]['args'][0]['__class__']);
	}
}
