<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Tests\Settings;

use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AdminTest extends TestCase {

	/** @var Admin|MockObject */
	private $admin;

	/** @var IInitialState|MockObject */
	private $initialState;

	/** @var ClientMapper|MockObject */
	private $clientMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->initialState = $this->createMock(IInitialState::class);
		$this->clientMapper = $this->createMock(ClientMapper::class);

		$this->admin = new Admin(
			$this->initialState,
			$this->clientMapper,
			$this->createMock(IURLGenerator::class),
			$this->createMock(LoggerInterface::class)
		);
	}

	public function testGetForm(): void {
		$expected = new TemplateResponse(
			'oauth2',
			'admin',
			[],
			''
		);
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('security', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(100, $this->admin->getPriority());
	}
}
