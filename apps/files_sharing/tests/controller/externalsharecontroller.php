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

namespace OCA\Files_Sharing\Controllers;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;
use OCP\IRequest;

/**
 * Class ExternalShareControllerTest
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ExternalShareControllerTest extends \Test\TestCase {
	/** @var IRequest */
	private $request;
	/** @var \OCA\Files_Sharing\External\Manager */
	private $externalManager;
	/** @var IClientService */
	private $clientService;

	public function setUp() {
		$this->request = $this->getMockBuilder('\\OCP\\IRequest')
			->disableOriginalConstructor()->getMock();
		$this->externalManager = $this->getMockBuilder('\\OCA\\Files_Sharing\\External\\Manager')
			->disableOriginalConstructor()->getMock();
		$this->clientService = $this->getMockBuilder('\\OCP\Http\\Client\\IClientService')
			->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return ExternalSharesController
	 */
	public function getExternalShareController() {
		return new ExternalSharesController(
			'files_sharing',
			$this->request,
			$this->externalManager,
			$this->clientService
		);
	}

	public function testIndex() {
		$this->externalManager
			->expects($this->once())
			->method('getOpenShares')
			->will($this->returnValue(['MyDummyArray']));

		$this->assertEquals(new JSONResponse(['MyDummyArray']), $this->getExternalShareController()->index());
	}

	public function testCreate() {
		$this->externalManager
			->expects($this->once())
			->method('acceptShare')
			->with(4);

		$this->assertEquals(new JSONResponse(), $this->getExternalShareController()->create(4));
	}

	public function testDestroy() {
		$this->externalManager
			->expects($this->once())
			->method('declineShare')
			->with(4);

		$this->assertEquals(new JSONResponse(), $this->getExternalShareController()->destroy(4));
	}
}
