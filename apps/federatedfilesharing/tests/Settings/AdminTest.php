<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\FederatedFileSharing\Tests\Settings;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\GlobalScale\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;
	/** @var \OCA\FederatedFileSharing\FederatedShareProvider */
	private $federatedShareProvider;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $gsConfig;
	/** @var IInitialState|\PHPUnit\Framework\MockObject\MockObject */
	private $initialState;

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

	public function sharingStateProvider() {
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
	 * @param bool $state
	 */
	public function testGetForm($state) {
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
		$this->gsConfig->expects($this->once())->method('onlyInternalFederation')
			->willReturn($state);

		$this->initialState->expects($this->exactly(9))
			->method('provideInitialState')
			->withConsecutive(
				['internalOnly', $state],
				['sharingFederatedDocUrl', 'doc-link'],
				['outgoingServer2serverShareEnabled', $state],
				['incomingServer2serverShareEnabled', $state],
				['federatedGroupSharingSupported', $state],
				['outgoingServer2serverGroupShareEnabled', $state],
				['incomingServer2serverGroupShareEnabled', $state],
				['lookupServerEnabled', $state],
				['lookupServerUploadEnabled', $state],
			);

		$expected = new TemplateResponse('federatedfilesharing', 'settings-admin', [], '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('sharing', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(20, $this->admin->getPriority());
	}
}
