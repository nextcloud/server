<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

namespace Test\OCS;

use OC\OCS\Provider;

class ProviderTest extends \Test\TestCase {
	/** @var \OCP\IRequest */
	private $request;
	/** @var \OCP\App\IAppManager */
	private $appManager;
	/** @var Provider */
	private $ocsProvider;

	public function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('\\OCP\\IRequest')->getMock();
		$this->appManager = $this->getMockBuilder('\\OCP\\App\\IAppManager')->getMock();
		$this->ocsProvider = new Provider('ocs_provider', $this->request, $this->appManager);
	}

	public function testBuildProviderListWithoutAnythingEnabled() {
		$this->appManager
			->expects($this->at(0))
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(false));
		$this->appManager
			->expects($this->at(1))
			->method('isEnabledForUser')
			->with('activity')
			->will($this->returnValue(false));
		$this->appManager
			->expects($this->at(2))
			->method('isEnabledForUser')
			->with('provisioning_api')
			->will($this->returnValue(false));

		$expected = new \OCP\AppFramework\Http\JSONResponse(
			[
				'version' => 2,
				'services' => [
					'PRIVATE_DATA' => [
						'version' => 1,
						'endpoints' => [
							'store' => '/ocs/v2.php/privatedata/setattribute',
							'read' => '/ocs/v2.php/privatedata/getattribute',
							'delete' => '/ocs/v2.php/privatedata/deleteattribute',
						],
					],
				],
			]
		);

		$this->assertEquals($expected, $this->ocsProvider->buildProviderList());
	}

	public function testBuildProviderListWithSharingEnabled() {
		$this->appManager
			->expects($this->at(0))
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(true));
		$this->appManager
			->expects($this->at(1))
			->method('isEnabledForUser')
			->with('activity')
			->will($this->returnValue(false));
		$this->appManager
			->expects($this->at(2))
			->method('isEnabledForUser')
			->with('provisioning_api')
			->will($this->returnValue(false));

		$expected = new \OCP\AppFramework\Http\JSONResponse(
			[
				'version' => 2,
				'services' => [
					'PRIVATE_DATA' => [
						'version' => 1,
						'endpoints' => [
							'store' => '/ocs/v2.php/privatedata/setattribute',
							'read' => '/ocs/v2.php/privatedata/getattribute',
							'delete' => '/ocs/v2.php/privatedata/deleteattribute',
						],
					],
					'FEDERATED_SHARING' => [
						'version' => 1,
						'endpoints' => [
							'share' => '/ocs/v2.php/cloud/shares',
							'webdav' => '/public.php/webdav/',
						],
					],
					'SHARING' => [
						'version' => 1,
						'endpoints' => [
							'share' => '/ocs/v2.php/apps/files_sharing/api/v1/shares',
						],
					],
				],
			]
		);

		$this->assertEquals($expected, $this->ocsProvider->buildProviderList());
	}

	public function testBuildProviderListWithEverythingEnabled() {
		$this->appManager
			->expects($this->any())
			->method('isEnabledForUser')
			->will($this->returnValue(true));

		$expected = new \OCP\AppFramework\Http\JSONResponse(
			[
				'version' => 2,
				'services' => [
					'PRIVATE_DATA' => [
						'version' => 1,
						'endpoints' => [
							'store' => '/ocs/v2.php/privatedata/setattribute',
							'read' => '/ocs/v2.php/privatedata/getattribute',
							'delete' => '/ocs/v2.php/privatedata/deleteattribute',
						],
					],
					'FEDERATED_SHARING' => [
						'version' => 1,
						'endpoints' => [
							'share' => '/ocs/v2.php/cloud/shares',
							'webdav' => '/public.php/webdav/',
						],
					],
					'SHARING' => [
						'version' => 1,
						'endpoints' => [
							'share' => '/ocs/v2.php/apps/files_sharing/api/v1/shares',
						],
					],
					'ACTIVITY' => [
						'version' => 1,
						'endpoints' => [
							'list' => '/ocs/v2.php/cloud/activity',
						],
					],
					'PROVISIONING' => [
						'version' => 1,
						'endpoints' => [
							'user' => '/ocs/v2.php/cloud/users',
							'groups' => '/ocs/v2.php/cloud/groups',
							'apps' => '/ocs/v2.php/cloud/apps',
						],
					],
				],
			]
		);

		$this->assertEquals($expected, $this->ocsProvider->buildProviderList());
	}
}
