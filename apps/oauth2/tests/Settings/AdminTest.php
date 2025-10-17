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
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;
use OCP\Settings\ISettings;
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
	private IL10N&MockObject $l10n;


	protected function setUp(): void {
		parent::setUp();

		$this->initialState = $this->createMock(IInitialState::class);
		$this->clientMapper = $this->createMock(ClientMapper::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->admin = new Admin(
			$this->initialState,
			$this->clientMapper,
			$this->createMock(IURLGenerator::class),
			$this->createMock(LoggerInterface::class),
			$this->l10n
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

	public function testGetName(): void {
		$translatedName = 'OAuth 2.0 clients';
		$this->l10n->expects($this->once())
			->method('t')
			->with('OAuth 2.0 clients')
			->willReturn($translatedName);

		$this->assertSame($translatedName, $this->admin->getName());
	}

	public function testGetAuthorizedAppConfig(): void {
		$this->assertEquals([], $this->admin->getAuthorizedAppConfig());
		$this->assertIsArray($this->admin->getAuthorizedAppConfig());
	}

	public function testImplementsIDelegatedSettings(): void {
		$this->assertInstanceOf(IDelegatedSettings::class, $this->admin);
		$this->assertInstanceOf(ISettings::class, $this->admin);
	}

	public function testGetNameReturnsString(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with('OAuth 2.0 clients')
			->willReturn('Translated Name');

		$name = $this->admin->getName();
		$this->assertIsString($name);
		$this->assertNotEmpty($name);
	}
}
