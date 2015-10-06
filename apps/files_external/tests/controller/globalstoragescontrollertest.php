<?php
/**
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Vincent Petry <pvince81@owncloud.com>
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
namespace OCA\Files_external\Tests\Controller;

use \OCA\Files_external\Controller\GlobalStoragesController;
use \OCA\Files_external\Service\GlobalStoragesService;
use \OCP\AppFramework\Http;
use \OCA\Files_external\NotFoundException;
use \OCA\Files_External\Service\BackendService;

class GlobalStoragesControllerTest extends StoragesControllerTest {
	public function setUp() {
		parent::setUp();
		$this->service = $this->getMockBuilder('\OCA\Files_external\Service\GlobalStoragesService')
			->disableOriginalConstructor()
			->getMock();

		$this->service->method('getVisibilityType')
			->willReturn(BackendService::VISIBILITY_ADMIN);

		$this->controller = new GlobalStoragesController(
			'files_external',
			$this->getMock('\OCP\IRequest'),
			$this->getMock('\OCP\IL10N'),
			$this->service
		);
	}
}
