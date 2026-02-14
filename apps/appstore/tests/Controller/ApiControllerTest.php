<?php

declare(strict_types=1);
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Appstore\Tests\Controller;

use OC\App\AppManager;
use OC\App\AppStore\Bundles\BundleFetcher;
use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OC\App\DependencyAnalyzer;
use OC\Installer;
use OCA\Appstore\Controller\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\L10N\IFactory;
use OCP\Support\Subscription\IRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class ApiControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private AppManager&MockObject $appManager;
	private DependencyAnalyzer&MockObject $dependencyAnalyzer;
	private CategoryFetcher&MockObject $categoryFetcher;
	private AppFetcher&MockObject $appFetcher;
	private IFactory&MockObject $l10nFactory;
	private BundleFetcher&MockObject $bundleFetcher;
	private Installer&MockObject $installer;
	private IRegistry&MockObject $subscriptionRegistry;
	private LoggerInterface&MockObject $logger;

	private ApiController $apiController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appManager = $this->createMock(AppManager::class);
		$this->dependencyAnalyzer = $this->createMock(DependencyAnalyzer::class);
		$this->categoryFetcher = $this->createMock(CategoryFetcher::class);
		$this->appFetcher = $this->createMock(AppFetcher::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->bundleFetcher = $this->createMock(BundleFetcher::class);
		$this->installer = $this->createMock(Installer::class);
		$this->subscriptionRegistry = $this->createMock(IRegistry::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->apiController = new ApiController(
			$this->request,
			$this->config,
			$this->appConfig,
			$this->appManager,
			$this->dependencyAnalyzer,
			$this->categoryFetcher,
			$this->appFetcher,
			$this->l10nFactory,
			$this->bundleFetcher,
			$this->installer,
			$this->subscriptionRegistry,
			$this->logger,
		);
	}

	public function testListCategories(): void {
		$json = file_get_contents(__DIR__ . '/../fixtures/categories.json');
		$this->categoryFetcher
			->expects($this->once())
			->method('get')
			->willReturn(json_decode($json, true)['data']);

		$response = $this->apiController->listCategories();
		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(200, $response->getStatus());

		$jsonResponse = json_encode($response->getData());
		$this->assertJsonStringEqualsJsonFile(__DIR__ . '/../fixtures/categories-api-response.json', $jsonResponse);
	}
}
