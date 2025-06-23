<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Notification;

use OC\Notification\Action;
use OCP\Notification\IAction;
use Test\TestCase;

class ActionTest extends TestCase {
	/** @var IAction */
	protected $action;

	protected function setUp(): void {
		parent::setUp();
		$this->action = new Action();
	}

	public static function dataSetLabel(): array {
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
	public function testSetLabel($label): void {
		$this->assertSame('', $this->action->getLabel());
		$this->assertSame($this->action, $this->action->setLabel($label));
		$this->assertSame($label, $this->action->getLabel());
	}

	public static function dataSetLabelInvalid(): array {
		return [
			[''],
			[str_repeat('a', 33)],
		];
	}

	/**
	 * @dataProvider dataSetLabelInvalid
	 * @param mixed $label
	 *
	 */
	public function testSetLabelInvalid($label): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->action->setLabel($label);
	}

	public static function dataSetParsedLabel(): array {
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
	public function testSetParsedLabel($label): void {
		$this->assertSame('', $this->action->getParsedLabel());
		$this->assertSame($this->action, $this->action->setParsedLabel($label));
		$this->assertSame($label, $this->action->getParsedLabel());
	}

	public static function dataSetParsedLabelInvalid(): array {
		return [
			[''],
		];
	}

	/**
	 * @dataProvider dataSetParsedLabelInvalid
	 * @param mixed $label
	 *
	 */
	public function testSetParsedLabelInvalid($label): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->action->setParsedLabel($label);
	}

	public static function dataSetLink(): array {
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
	public function testSetLink($link, $type): void {
		$this->assertSame('', $this->action->getLink());
		$this->assertSame($this->action, $this->action->setLink($link, $type));
		$this->assertSame($link, $this->action->getLink());
		$this->assertSame($type, $this->action->getRequestType());
	}

	public static function dataSetLinkInvalid(): array {
		return [
			// Invalid link
			['', 'GET'],
			[str_repeat('a', 257), 'GET'],

			// Invalid type
			['url', 'notGET'],
		];
	}

	/**
	 * @dataProvider dataSetLinkInvalid
	 * @param mixed $link
	 * @param mixed $type
	 *
	 */
	public function testSetLinkInvalid($link, $type): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->action->setLink($link, $type);
	}

	public static function dataSetPrimary(): array {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider dataSetPrimary
	 * @param bool $primary
	 */
	public function testSetPrimary($primary): void {
		$this->assertSame(false, $this->action->isPrimary());
		$this->assertSame($this->action, $this->action->setPrimary($primary));
		$this->assertSame($primary, $this->action->isPrimary());
	}

	public function testIsValid(): void {
		$this->assertFalse($this->action->isValid());
		$this->assertFalse($this->action->isValidParsed());
		$this->action->setLabel('label');
		$this->assertFalse($this->action->isValid());
		$this->assertFalse($this->action->isValidParsed());
		$this->action->setLink('link', 'GET');
		$this->assertTrue($this->action->isValid());
		$this->assertFalse($this->action->isValidParsed());
	}

	public function testIsValidParsed(): void {
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
