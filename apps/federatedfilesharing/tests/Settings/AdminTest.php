<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Tests\Settings;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\GlobalScale\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminTest extends TestCase {
	private FederatedShareProvider&MockObject $federatedShareProvider;
	private IConfig $gsConfig;
	private IInitialState&MockObject $initialState;
	private Admin $admin;

	protected function setUp(): void {
		parent::setUp();
		$this->federatedShareProvider = $this->createMock(FederatedShareProvider::class);
		$this->gsConfig = $this->createMock(IConfig::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->expects($this->any())
			->method('linkToDocs')
			->willReturn('doc-link');

		$this->admin = new Admin(
			$this->federatedShareProvider,
			$this->gsConfig,
			$this->createMock(IL10N::class),
			$urlGenerator,
			$this->initialState
		);
	}

	public static function sharingStateProvider(): array {
		return [
			[
				true,
			],
			[
				false,
			]
		];
	}

	/**
	 * @dataProvider sharingStateProvider
	 */
	public function testGetForm(bool $state): void {
		$this->federatedShareProvider
			->expects($this->once())
			->method('isOutgoingServer2serverShareEnabled')
			->willReturn($state);
		$this->federatedShareProvider
			->expects($this->once())
			->method('isIncomingServer2serverShareEnabled')
			->willReturn($state);
		$this->federatedShareProvider
			->expects($this->once())
			->method('isIncomingServer2serverShareEnabled')
			->willReturn($state);
		$this->federatedShareProvider
			->expects($this->once())
			->method('isLookupServerQueriesEnabled')
			->willReturn($state);
		$this->federatedShareProvider
			->expects($this->once())
			->method('isLookupServerUploadEnabled')
			->willReturn($state);
		$this->federatedShareProvider
			->expects($this->once())
			->method('isFederatedGroupSharingSupported')
			->willReturn($state);
		$this->federatedShareProvider
			->expects($this->once())
			->method('isOutgoingServer2serverGroupShareEnabled')
			->willReturn($state);
		$this->federatedShareProvider
			->expects($this->once())
			->method('isIncomingServer2serverGroupShareEnabled')
			->willReturn($state);
		$this->federatedShareProvider
			->expects($this->once())
			->method('isFederatedTrustedShareAutoAccept')
			->willReturn($state);
		$this->gsConfig->expects($this->once())->method('onlyInternalFederation')
			->willReturn($state);

		$calls = [
			['internalOnly', $state],
			['sharingFederatedDocUrl', 'doc-link'],
			['outgoingServer2serverShareEnabled', $state],
			['incomingServer2serverShareEnabled', $state],
			['federatedGroupSharingSupported', $state],
			['outgoingServer2serverGroupShareEnabled', $state],
			['incomingServer2serverGroupShareEnabled', $state],
			['lookupServerEnabled', $state],
			['lookupServerUploadEnabled', $state],
			['federatedTrustedShareAutoAccept', $state],
		];
		$this->initialState->expects($this->exactly(10))
			->method('provideInitialState')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertSame($expected, func_get_args());
			});

		$expected = new TemplateResponse('federatedfilesharing', 'settings-admin', [], '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('sharing', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(20, $this->admin->getPriority());
	}
}
