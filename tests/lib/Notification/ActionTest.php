<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace Test\Notification;


use OC\Notification\Action;
use OCP\Notification\IAction;
use Test\TestCase;

class ActionTest extends TestCase {
	/** @var IAction */
	protected $action;

	public function setUp() {
		parent::setUp();
		$this->action = new Action();
	}

	public function dataSetLabel() {
		return [
			['test1'],
			[str_repeat('a', 1)],
			[str_repeat('a', 32)],
		];
	}

	/**
	 * @dataProvider dataSetLabel
	 * @param string $label
	 */
	public function testSetLabel($label) {
		$this->assertSame('', $this->action->getLabel());
		$this->assertSame($this->action, $this->action->setLabel($label));
		$this->assertSame($label, $this->action->getLabel());
	}

	public function dataSetLabelInvalid() {
		return [
			[true],
			[false],
			[0],
			[1],
			[''],
			[str_repeat('a', 33)],
			[[]],
			[[str_repeat('a', 33)]],
		];
	}

	/**
	 * @dataProvider dataSetLabelInvalid
	 * @param mixed $label
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetLabelInvalid($label) {
		$this->action->setLabel($label);
	}

	public function dataSetParsedLabel() {
		return [
			['test1'],
			[str_repeat('a', 1)],
			[str_repeat('a', 32)],
		];
	}

	/**
	 * @dataProvider dataSetParsedLabel
	 * @param string $label
	 */
	public function testSetParsedLabel($label) {
		$this->assertSame('', $this->action->getParsedLabel());
		$this->assertSame($this->action, $this->action->setParsedLabel($label));
		$this->assertSame($label, $this->action->getParsedLabel());
	}

	public function dataSetParsedLabelInvalid() {
		return [
			[true],
			[false],
			[0],
			[1],
			[''],
			[[]],
			[[str_repeat('a', 33)]],
		];
	}

	/**
	 * @dataProvider dataSetParsedLabelInvalid
	 * @param mixed $label
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetParsedLabelInvalid($label) {
		$this->action->setParsedLabel($label);
	}

	public function dataSetLink() {
		return [
			['test1', 'GET'],
			['test2', 'POST'],
			[str_repeat('a', 1), 'PUT'],
			[str_repeat('a', 256), 'DELETE'],
		];
	}

	/**
	 * @dataProvider dataSetLink
	 * @param string $link
	 * @param string $type
	 */
	public function testSetLink($link, $type) {
		$this->assertSame('', $this->action->getLink());
		$this->assertSame($this->action, $this->action->setLink($link, $type));
		$this->assertSame($link, $this->action->getLink());
		$this->assertSame($type, $this->action->getRequestType());
	}

	public function dataSetLinkInvalid() {
		return [
			// Invalid link
			[true, 'GET'],
			[false, 'GET'],
			[0, 'GET'],
			[1, 'GET'],
			['', 'GET'],
			[str_repeat('a', 257), 'GET'],
			[[], 'GET'],
			[[str_repeat('a', 257)], 'GET'],

			// Invalid type
			['url', 'notGET'],
			['url', true],
			['url', false],
			['url', 0],
			['url', 1],
			['url', []],
			['url', ['GET']],
		];
	}

	/**
	 * @dataProvider dataSetLinkInvalid
	 * @param mixed $link
	 * @param mixed $type
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetLinkInvalid($link, $type) {
		$this->action->setLink($link, $type);
	}

	public function dataSetPrimary() {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider dataSetPrimary
	 * @param bool $primary
	 */
	public function testSetPrimary($primary) {
		$this->assertSame(false, $this->action->isPrimary());
		$this->assertSame($this->action, $this->action->setPrimary($primary));
		$this->assertSame($primary, $this->action->isPrimary());
	}

	public function dataSetPrimaryInvalid() {
		return [
			[0],
			[1],
			[''],
			[str_repeat('a', 257)],
			[[]],
			[[str_repeat('a', 257)]],
		];
	}

	/**
	 * @dataProvider dataSetPrimaryInvalid
	 * @param mixed $primary
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetPrimaryInvalid($primary) {
		$this->action->setPrimary($primary);
	}

	public function testIsValid() {
		$this->assertFalse($this->action->isValid());
		$this->assertFalse($this->action->isValidParsed());
		$this->action->setLabel('label');
		$this->assertFalse($this->action->isValid());
		$this->assertFalse($this->action->isValidParsed());
		$this->action->setLink('link', 'GET');
		$this->assertTrue($this->action->isValid());
		$this->assertFalse($this->action->isValidParsed());
	}

	public function testIsValidParsed() {
		$this->assertFalse($this->action->isValid());
		$this->assertFalse($this->action->isValidParsed());
		$this->action->setParsedLabel('label');
		$this->assertFalse($this->action->isValid());
		$this->assertFalse($this->action->isValidParsed());
		$this->action->setLink('link', 'GET');
		$this->assertFalse($this->action->isValid());
		$this->assertTrue($this->action->isValidParsed());
	}
}
