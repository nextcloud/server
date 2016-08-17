<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FederatedFileSharing\Tests\Settings;

use OCA\FederatedFileSharing\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;
	/** @var \OCA\FederatedFileSharing\FederatedShareProvider */
	private $federatedShareProvider;

	public function setUp() {
		parent::setUp();
		$this->federatedShareProvider = $this->getMockBuilder('\OCA\FederatedFileSharing\FederatedShareProvider')->disableOriginalConstructor()->getMock();
		$this->admin = new Admin(
			$this->federatedShareProvider
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

		$params = [
			'outgoingServer2serverShareEnabled' => $state,
			'incomingServer2serverShareEnabled' => $state,
		];
		$expected = new TemplateResponse('federatedfilesharing', 'settings-admin', $params, '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('sharing', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(20, $this->admin->getPriority());
	}
}
