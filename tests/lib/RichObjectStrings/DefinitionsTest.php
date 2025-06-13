<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\RichObjectStrings;

use OCP\RichObjectStrings\Definitions;
use OCP\RichObjectStrings\InvalidObjectExeption;
use Test\TestCase;

class DefinitionsTest extends TestCase {
	public static function dataGetDefinition() {
		$definitions = new Definitions();
		$testsuite = [];
		foreach ($definitions->definitions as $type => $definition) {
			$testsuite[] = [$type, $definition];
		}
		return $testsuite;
	}


	public function testGetDefinitionNotExisting(): void {
		$this->expectException(InvalidObjectExeption::class);
		$this->expectExceptionMessage('Object type is undefined');

		$definitions = new Definitions();
		$definitions->getDefinition('NotExistingType');
	}

	/**
	 * @dataProvider dataGetDefinition
	 * @param string $type
	 * @param array $expected
	 */
	public function testGetDefinition($type, array $expected): void {
		$definitions = new Definitions();
		$definition = $definitions->getDefinition($type);

		$this->assertEquals($expected, $definition);
		$this->assertArrayHasKey('author', $definition);
		$this->assertNotEquals('', $definition['author'], 'Author of definition must not be empty');
		$this->assertArrayHasKey('app', $definition);
		$this->assertNotEquals('', $definition['app'], 'App of definition must not be empty');
		$this->assertArrayHasKey('since', $definition);
		$this->assertNotEmpty($definition['since'], 'Since of definition must not be empty');
		$this->assertArrayHasKey('parameters', $definition);
		$this->assertTrue(is_array($definition['parameters']), 'Parameters of definition must be of type array');
		$this->assertNotEmpty($definition['parameters'], 'Parameters of definition must not be empty');


		$this->assertArrayHasKey('id', $definition['parameters'], 'Parameter ID must be defined');
		$this->assertArrayHasKey('name', $definition['parameters'], 'Parameter name must be defined');

		foreach ($definition['parameters'] as $parameter => $data) {
			$this->validateParameter($parameter, $data);
		}
	}

	public function validateParameter($parameter, $data) {
		$this->assertTrue(is_array($data), 'Parameter ' . $parameter . ' is invalid');
		$this->assertArrayHasKey('since', $data);
		$this->assertNotEmpty($data['since'], 'Since of parameter ' . $parameter . ' must not be empty');
		$this->assertArrayHasKey('required', $data);
		$this->assertTrue(is_bool($data['required']), 'Required of parameter ' . $parameter . ' must be a boolean');
		if ($parameter === 'id' || $parameter === 'name') {
			$this->assertTrue($data['required'], 'Parameter ' . $parameter . ' must be required');
		}

		$this->assertArrayHasKey('description', $data);
		$this->assertNotEquals('', $data['description'], 'Description of parameter ' . $parameter . ' must not be empty');
		$this->assertArrayHasKey('example', $data);
		$this->assertNotEquals('', $data['example'], 'Example of parameter ' . $parameter . ' must not be empty');
	}
}
