<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Tests\Backend;

use \OCA\Files_External\Lib\Backend\LegacyBackend;
use \OCA\Files_External\Lib\DefinitionParameter;

class LegacyBackendTest extends \Test\TestCase {

	public function testConstructor() {
		$auth = $this->getMockBuilder('\OCA\Files_External\Lib\Auth\NullMechanism')
			->disableOriginalConstructor()
			->getMock();

		$class = '\OC\Files\Storage\SMB';
		$definition = [
			'configuration' => [
				'textfield' => 'Text field',
				'passwordfield' => '*Password field',
				'checkbox' => '!Checkbox',
				'hiddenfield' => '#Hidden field',
				'optionaltext' => '&Optional text field',
				'optionalpassword' => '&*Optional password field',
			],
			'backend' => 'Backend text',
			'priority' => 123,
			'custom' => 'foo/bar.js',
			'has_dependencies' => true,
		];

		$backend = new LegacyBackend($class, $definition, $auth);

		$this->assertEquals('\OC\Files\Storage\SMB', $backend->getStorageClass());
		$this->assertEquals('Backend text', $backend->getText());
		$this->assertEquals(123, $backend->getPriority());
		$this->assertEquals('foo/bar.js', $backend->getCustomJs());
		$this->assertEquals(true, $backend->hasDependencies());
		$this->assertArrayHasKey('null', $backend->getAuthSchemes());
		$this->assertEquals($auth, $backend->getLegacyAuthMechanism());

		$parameters = $backend->getParameters();
		$this->assertEquals('Text field', $parameters['textfield']->getText());
		$this->assertEquals(DefinitionParameter::VALUE_TEXT, $parameters['textfield']->getType());
		$this->assertEquals(DefinitionParameter::FLAG_NONE, $parameters['textfield']->getFlags());
		$this->assertEquals('Password field', $parameters['passwordfield']->getText());
		$this->assertEquals(DefinitionParameter::VALUE_PASSWORD, $parameters['passwordfield']->getType());
		$this->assertEquals(DefinitionParameter::FLAG_NONE, $parameters['passwordfield']->getFlags());
		$this->assertEquals('Checkbox', $parameters['checkbox']->getText());
		$this->assertEquals(DefinitionParameter::VALUE_BOOLEAN, $parameters['checkbox']->getType());
		$this->assertEquals(DefinitionParameter::FLAG_NONE, $parameters['checkbox']->getFlags());
		$this->assertEquals('Hidden field', $parameters['hiddenfield']->getText());
		$this->assertEquals(DefinitionParameter::VALUE_HIDDEN, $parameters['hiddenfield']->getType());
		$this->assertEquals(DefinitionParameter::FLAG_NONE, $parameters['hiddenfield']->getFlags());
		$this->assertEquals('Optional text field', $parameters['optionaltext']->getText());
		$this->assertEquals(DefinitionParameter::VALUE_TEXT, $parameters['optionaltext']->getType());
		$this->assertEquals(DefinitionParameter::FLAG_OPTIONAL, $parameters['optionaltext']->getFlags());
		$this->assertEquals('Optional password field', $parameters['optionalpassword']->getText());
		$this->assertEquals(DefinitionParameter::VALUE_PASSWORD, $parameters['optionalpassword']->getType());
		$this->assertEquals(DefinitionParameter::FLAG_OPTIONAL, $parameters['optionalpassword']->getFlags());
	}

}
