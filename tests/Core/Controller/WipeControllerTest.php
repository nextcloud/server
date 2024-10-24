<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\RemoteWipe;
use OC\Core\Controller\WipeController;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class WipeControllerTest extends TestCase {
	/** @var RemoteWipe|MockObject */
	private $remoteWipe;

	/** @var WipeController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->remoteWipe = $this->createMock(RemoteWipe::class);
		$this->controller = new WipeController(
			'core',
			$this->createMock(IRequest::class),
			$this->remoteWipe);
	}

	public function dataTest() {
		return [
			// valid token, could perform operation, valid result
			[ true,  true,  true],
			[ true, false, false],
			[false,  true, false],
			[false, false, false],
		];
	}

	/**
	 * @param bool $valid
	 * @param bool $couldPerform
	 * @param bool $result
	 *
	 * @dataProvider dataTest
	 */
	public function testCheckWipe(bool $valid, bool $couldPerform, bool $result): void {
		if (!$valid) {
			$this->remoteWipe->method('start')
				->with('mytoken')
				->willThrowException(new InvalidTokenException());
		} else {
			$this->remoteWipe->method('start')
				->with('mytoken')
				->willReturn($couldPerform);
		}

		$result = $this->controller->checkWipe('mytoken');

		if (!$valid || !$couldPerform) {
			$this->assertSame(Http::STATUS_NOT_FOUND, $result->getStatus());
			$this->assertSame([], $result->getData());
		} else {
			$this->assertSame(Http::STATUS_OK, $result->getStatus());
			$this->assertSame(['wipe' => true], $result->getData());
		}
	}

	/**
	 * @param bool $valid
	 * @param bool $couldPerform
	 * @param bool $result
	 *
	 * @dataProvider dataTest
	 */
	public function testWipeDone(bool $valid, bool $couldPerform, bool $result): void {
		if (!$valid) {
			$this->remoteWipe->method('finish')
				->with('mytoken')
				->willThrowException(new InvalidTokenException());
		} else {
			$this->remoteWipe->method('finish')
				->with('mytoken')
				->willReturn($couldPerform);
		}

		$result = $this->controller->wipeDone('mytoken');

		if (!$valid || !$couldPerform) {
			$this->assertSame(Http::STATUS_NOT_FOUND, $result->getStatus());
			$this->assertSame([], $result->getData());
		} else {
			$this->assertSame(Http::STATUS_OK, $result->getStatus());
			$this->assertSame([], $result->getData());
		}
	}
}
