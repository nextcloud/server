<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\DefinitionParameter as Param;

class DefinitionParameterTest extends \Test\TestCase {
	public function testJsonSerialization(): void {
		$param = new Param('foo', 'bar');
		$this->assertEquals([
			'value' => 'bar',
			'flags' => 0,
			'type' => 0,
			'tooltip' => '',
		], $param->jsonSerialize());

		$param->setType(Param::VALUE_BOOLEAN);
		$param->setDefaultValue(true);
		$this->assertEquals([
			'value' => 'bar',
			'flags' => 0,
			'type' => Param::VALUE_BOOLEAN,
			'tooltip' => '',
			'defaultValue' => true,
		], $param->jsonSerialize());

		$param->setType(Param::VALUE_PASSWORD);
		$param->setFlag(Param::FLAG_OPTIONAL);
		$param->setDefaultValue(null);
		$this->assertEquals([
			'value' => 'bar',
			'flags' => Param::FLAG_OPTIONAL,
			'type' => Param::VALUE_PASSWORD,
			'tooltip' => '',
		], $param->jsonSerialize());

		$param->setType(Param::VALUE_TEXT);
		$param->setFlags(Param::FLAG_HIDDEN);
		$this->assertEquals([
			'value' => 'bar',
			'flags' => Param::FLAG_HIDDEN,
			'type' => Param::VALUE_TEXT,
			'tooltip' => '',
		], $param->jsonSerialize());
	}

	public static function validateValueProvider(): array {
		return [
			[Param::VALUE_TEXT, Param::FLAG_NONE, 'abc', true],
			[Param::VALUE_TEXT, Param::FLAG_NONE, '', false],
			[Param::VALUE_TEXT, Param::FLAG_OPTIONAL, '', true],
			[Param::VALUE_TEXT, Param::FLAG_HIDDEN, '', false],

			[Param::VALUE_BOOLEAN, Param::FLAG_NONE, false, true],
			[Param::VALUE_BOOLEAN, Param::FLAG_NONE, 123, false],
			// conversion from string to boolean
			[Param::VALUE_BOOLEAN, Param::FLAG_NONE, 'false', true, false],
			[Param::VALUE_BOOLEAN, Param::FLAG_NONE, 'true', true, true],

			[Param::VALUE_PASSWORD, Param::FLAG_NONE, 'foobar', true],
			[Param::VALUE_PASSWORD, Param::FLAG_NONE, '', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'validateValueProvider')]
	public function testValidateValue($type, $flags, $value, $success, $expectedValue = null): void {
		$param = new Param('foo', 'bar');
		$param->setType($type);
		$param->setFlags($flags);

		$this->assertEquals($success, $param->validateValue($value));
		if (isset($expectedValue)) {
			$this->assertEquals($expectedValue, $value);
		}
	}
}
