<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\Controllers;

use OCA\Files_Sharing\Controller\ExternalSharesController;
use OCA\Files_Sharing\External\Manager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ExternalShareControllerTest
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ExternalShareControllerTest extends \Test\TestCase {
	private IRequest $request;
	private Manager $externalManager;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->externalManager = $this->createMock(Manager::class);
	}

	public function getExternalShareController(): ExternalSharesController {
		return new ExternalSharesController(
			'files_sharing',
			$this->request,
			$this->externalManager,
		);
	}

	public function testIndex(): void {
		$this->externalManager
			->expects($this->once())
			->method('getOpenShares')
			->willReturn(['MyDummyArray']);

		$this->assertEquals(new JSONResponse(['MyDummyArray']), $this->getExternalShareController()->index());
	}

	public function testCreate(): void {
		$this->externalManager
			->expects($this->once())
			->method('acceptShare')
			->with(4);

		$this->assertEquals(new JSONResponse(), $this->getExternalShareController()->create(4));
	}

	public function testDestroy(): void {
		$this->externalManager
			->expects($this->once())
			->method('declineShare')
			->with(4);

		$this->assertEquals(new JSONResponse(), $this->getExternalShareController()->destroy(4));
	}
}
