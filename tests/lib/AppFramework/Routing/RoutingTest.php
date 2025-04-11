<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\AppFramework\Routing;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Routing\RouteConfig;
use OC\Route\Route;
use OC\Route\Router;
use OCP\App\IAppManager;
use OCP\Diagnostics\IEventLogger;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Route\IRouter;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RoutingTest extends \Test\TestCase {
	public function testSimpleRoute(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'GET']
		]];

		$this->assertSimpleRoute($routes, 'folders.open', 'GET', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open');
	}

	public function testSimpleRouteWithUnderScoreNames(): void {
		$routes = ['routes' => [
			['name' => 'admin_folders#open_current', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', 'root' => '']
		]];

		$this->assertSimpleRoute($routes, 'admin_folders.open_current', 'DELETE', '/folders/{folderId}/open', 'AdminFoldersController', 'openCurrent', [], [], '', true);
	}

	public function testSimpleOCSRoute(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'GET']
		]
		];

		$this->assertSimpleOCSRoute($routes, 'folders.open', 'GET', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open');
	}

	public function testSimpleRouteWithMissingVerb(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open']
		]];

		$this->assertSimpleRoute($routes, 'folders.open', 'GET', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open');
	}

	public function testSimpleOCSRouteWithMissingVerb(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open']
		]
		];

		$this->assertSimpleOCSRoute($routes, 'folders.open', 'GET', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open');
	}

	public function testSimpleRouteWithLowercaseVerb(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete']
		]];

		$this->assertSimpleRoute($routes, 'folders.open', 'DELETE', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open');
	}

	public function testSimpleOCSRouteWithLowercaseVerb(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete']
		]
		];

		$this->assertSimpleOCSRoute($routes, 'folders.open', 'DELETE', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open');
	}

	public function testSimpleRouteWithRequirements(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', 'requirements' => ['something']]
		]];

		$this->assertSimpleRoute($routes, 'folders.open', 'DELETE', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open', ['something']);
	}

	public function testSimpleOCSRouteWithRequirements(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', 'requirements' => ['something']]
		]
		];

		$this->assertSimpleOCSRoute($routes, 'folders.open', 'DELETE', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open', ['something']);
	}

	public function testSimpleRouteWithDefaults(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', [], 'defaults' => ['param' => 'foobar']]
		]];

		$this->assertSimpleRoute($routes, 'folders.open', 'DELETE', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open', [], ['param' => 'foobar']);
	}


	public function testSimpleOCSRouteWithDefaults(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', 'defaults' => ['param' => 'foobar']]
		]
		];

		$this->assertSimpleOCSRoute($routes, 'folders.open', 'DELETE', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open', [], ['param' => 'foobar']);
	}

	public function testSimpleRouteWithPostfix(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', 'postfix' => '_something']
		]];

		$this->assertSimpleRoute($routes, 'folders.open', 'DELETE', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open', [], [], '_something');
	}

	public function testSimpleOCSRouteWithPostfix(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', 'postfix' => '_something']
		]
		];

		$this->assertSimpleOCSRoute($routes, 'folders.open', 'DELETE', '/apps/app1/folders/{folderId}/open', 'FoldersController', 'open', [], [], '_something');
	}


	public function testSimpleRouteWithBrokenName(): void {
		$this->expectException(\UnexpectedValueException::class);

		$routes = ['routes' => [
			['name' => 'folders_open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete']
		]];

		/** @var IRouter|MockObject $router */
		$router = $this->getMockBuilder(Router::class)
			->onlyMethods(['create'])
			->setConstructorArgs([
				$this->createMock(LoggerInterface::class),
				$this->createMock(IRequest::class),
				$this->createMock(IConfig::class),
				$this->createMock(IEventLogger::class),
				$this->createMock(ContainerInterface::class),
				$this->createMock(IAppManager::class),
			])
			->getMock();

		// load route configuration
		$container = new DIContainer('app1');
		$config = new RouteConfig($container, $router, $routes);

		$config->register();
	}


	public function testSimpleOCSRouteWithBrokenName(): void {
		$this->expectException(\UnexpectedValueException::class);

		$routes = ['ocs' => [
			['name' => 'folders_open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete']
		]];

		/** @var IRouter|MockObject $router */
		$router = $this->getMockBuilder(Router::class)
			->onlyMethods(['create'])
			->setConstructorArgs([
				$this->createMock(LoggerInterface::class),
				$this->createMock(IRequest::class),
				$this->createMock(IConfig::class),
				$this->createMock(IEventLogger::class),
				$this->createMock(ContainerInterface::class),
				$this->createMock(IAppManager::class),
			])
			->getMock();

		// load route configuration
		$container = new DIContainer('app1');
		$config = new RouteConfig($container, $router, $routes);

		$config->register();
	}

	public function testSimpleOCSRouteWithUnderScoreNames(): void {
		$routes = ['ocs' => [
			['name' => 'admin_folders#open_current', 'url' => '/folders/{folderId}/open', 'verb' => 'delete']
		]];

		$this->assertSimpleOCSRoute($routes, 'admin_folders.open_current', 'DELETE', '/apps/app1/folders/{folderId}/open', 'AdminFoldersController', 'openCurrent');
	}

	public function testOCSResource(): void {
		$routes = ['ocs-resources' => ['account' => ['url' => '/accounts']]];

		$this->assertOCSResource($routes, 'account', '/apps/app1/accounts', 'AccountController', 'id');
	}

	public function testOCSResourceWithUnderScoreName(): void {
		$routes = ['ocs-resources' => ['admin_accounts' => ['url' => '/admin/accounts']]];

		$this->assertOCSResource($routes, 'admin_accounts', '/apps/app1/admin/accounts', 'AdminAccountsController', 'id');
	}

	public function testOCSResourceWithRoot(): void {
		$routes = ['ocs-resources' => ['admin_accounts' => ['url' => '/admin/accounts', 'root' => '/core/endpoint']]];

		$this->assertOCSResource($routes, 'admin_accounts', '/core/endpoint/admin/accounts', 'AdminAccountsController', 'id');
	}

	public function testResource(): void {
		$routes = ['resources' => ['account' => ['url' => '/accounts']]];

		$this->assertResource($routes, 'account', '/apps/app1/accounts', 'AccountController', 'id');
	}

	public function testResourceWithUnderScoreName(): void {
		$routes = ['resources' => ['admin_accounts' => ['url' => '/admin/accounts']]];

		$this->assertResource($routes, 'admin_accounts', '/apps/app1/admin/accounts', 'AdminAccountsController', 'id');
	}

	private function assertSimpleRoute($routes, $name, $verb, $url, $controllerName, $actionName, array $requirements = [], array $defaults = [], $postfix = '', $allowRootUrl = false): void {
		if ($postfix) {
			$name .= $postfix;
		}

		// route mocks
		$container = new DIContainer('app1');
		$route = $this->mockRoute($container, $verb, $controllerName, $actionName, $requirements, $defaults);

		/** @var IRouter|MockObject $router */
		$router = $this->getMockBuilder(Router::class)
			->onlyMethods(['create'])
			->setConstructorArgs([
				$this->createMock(LoggerInterface::class),
				$this->createMock(IRequest::class),
				$this->createMock(IConfig::class),
				$this->createMock(IEventLogger::class),
				$this->createMock(ContainerInterface::class),
				$this->createMock(IAppManager::class),
			])
			->getMock();

		// we expect create to be called once:
		$router
			->expects($this->once())
			->method('create')
			->with($this->equalTo('app1.' . $name), $this->equalTo($url))
			->willReturn($route);

		// load route configuration
		$config = new RouteConfig($container, $router, $routes);
		if ($allowRootUrl) {
			self::invokePrivate($config, 'rootUrlApps', [['app1']]);
		}

		$config->register();
	}

	/**
	 * @param $routes
	 * @param string $name
	 * @param string $verb
	 * @param string $url
	 * @param string $controllerName
	 * @param string $actionName
	 * @param array $requirements
	 * @param array $defaults
	 * @param string $postfix
	 */
	private function assertSimpleOCSRoute($routes,
		$name,
		$verb,
		$url,
		$controllerName,
		$actionName,
		array $requirements = [],
		array $defaults = [],
		$postfix = '') {
		if ($postfix) {
			$name .= $postfix;
		}

		// route mocks
		$container = new DIContainer('app1');
		$route = $this->mockRoute($container, $verb, $controllerName, $actionName, $requirements, $defaults);

		/** @var IRouter|MockObject $router */
		$router = $this->getMockBuilder(Router::class)
			->onlyMethods(['create'])
			->setConstructorArgs([
				$this->createMock(LoggerInterface::class),
				$this->createMock(IRequest::class),
				$this->createMock(IConfig::class),
				$this->createMock(IEventLogger::class),
				$this->createMock(ContainerInterface::class),
				$this->createMock(IAppManager::class),
			])
			->getMock();

		// we expect create to be called once:
		$router
			->expects($this->once())
			->method('create')
			->with($this->equalTo('ocs.app1.' . $name), $this->equalTo($url))
			->willReturn($route);

		// load route configuration
		$config = new RouteConfig($container, $router, $routes);

		$config->register();
	}

	/**
	 * @param array $yaml
	 * @param string $resourceName
	 * @param string $url
	 * @param string $controllerName
	 * @param string $paramName
	 */
	private function assertOCSResource($yaml, $resourceName, $url, $controllerName, $paramName): void {
		/** @var IRouter|MockObject $router */
		$router = $this->getMockBuilder(Router::class)
			->onlyMethods(['create'])
			->setConstructorArgs([
				$this->createMock(LoggerInterface::class),
				$this->createMock(IRequest::class),
				$this->createMock(IConfig::class),
				$this->createMock(IEventLogger::class),
				$this->createMock(ContainerInterface::class),
				$this->createMock(IAppManager::class),
			])
			->getMock();

		// route mocks
		$container = new DIContainer('app1');
		$indexRoute = $this->mockRoute($container, 'GET', $controllerName, 'index');
		$showRoute = $this->mockRoute($container, 'GET', $controllerName, 'show');
		$createRoute = $this->mockRoute($container, 'POST', $controllerName, 'create');
		$updateRoute = $this->mockRoute($container, 'PUT', $controllerName, 'update');
		$destroyRoute = $this->mockRoute($container, 'DELETE', $controllerName, 'destroy');

		$urlWithParam = $url . '/{' . $paramName . '}';

		// we expect create to be called five times:
		$router
			->expects($this->exactly(5))
			->method('create')
			->withConsecutive(
				[$this->equalTo('ocs.app1.' . $resourceName . '.index'), $this->equalTo($url)],
				[$this->equalTo('ocs.app1.' . $resourceName . '.show'), $this->equalTo($urlWithParam)],
				[$this->equalTo('ocs.app1.' . $resourceName . '.create'), $this->equalTo($url)],
				[$this->equalTo('ocs.app1.' . $resourceName . '.update'), $this->equalTo($urlWithParam)],
				[$this->equalTo('ocs.app1.' . $resourceName . '.destroy'), $this->equalTo($urlWithParam)],
			)->willReturnOnConsecutiveCalls(
				$indexRoute,
				$showRoute,
				$createRoute,
				$updateRoute,
				$destroyRoute,
			);

		// load route configuration
		$config = new RouteConfig($container, $router, $yaml);

		$config->register();
	}

	/**
	 * @param string $resourceName
	 * @param string $url
	 * @param string $controllerName
	 * @param string $paramName
	 */
	private function assertResource($yaml, $resourceName, $url, $controllerName, $paramName) {
		/** @var IRouter|MockObject $router */
		$router = $this->getMockBuilder(Router::class)
			->onlyMethods(['create'])
			->setConstructorArgs([
				$this->createMock(LoggerInterface::class),
				$this->createMock(IRequest::class),
				$this->createMock(IConfig::class),
				$this->createMock(IEventLogger::class),
				$this->createMock(ContainerInterface::class),
				$this->createMock(IAppManager::class),
			])
			->getMock();

		// route mocks
		$container = new DIContainer('app1');
		$indexRoute = $this->mockRoute($container, 'GET', $controllerName, 'index');
		$showRoute = $this->mockRoute($container, 'GET', $controllerName, 'show');
		$createRoute = $this->mockRoute($container, 'POST', $controllerName, 'create');
		$updateRoute = $this->mockRoute($container, 'PUT', $controllerName, 'update');
		$destroyRoute = $this->mockRoute($container, 'DELETE', $controllerName, 'destroy');

		$urlWithParam = $url . '/{' . $paramName . '}';

		// we expect create to be called five times:
		$router
			->expects($this->exactly(5))
			->method('create')
			->withConsecutive(
				[$this->equalTo('app1.' . $resourceName . '.index'), $this->equalTo($url)],
				[$this->equalTo('app1.' . $resourceName . '.show'), $this->equalTo($urlWithParam)],
				[$this->equalTo('app1.' . $resourceName . '.create'), $this->equalTo($url)],
				[$this->equalTo('app1.' . $resourceName . '.update'), $this->equalTo($urlWithParam)],
				[$this->equalTo('app1.' . $resourceName . '.destroy'), $this->equalTo($urlWithParam)],
			)->willReturnOnConsecutiveCalls(
				$indexRoute,
				$showRoute,
				$createRoute,
				$updateRoute,
				$destroyRoute,
			);

		// load route configuration
		$config = new RouteConfig($container, $router, $yaml);

		$config->register();
	}

	/**
	 * @param DIContainer $container
	 * @param string $verb
	 * @param string $controllerName
	 * @param string $actionName
	 * @param array $requirements
	 * @param array $defaults
	 * @return MockObject
	 */
	private function mockRoute(
		DIContainer $container,
		$verb,
		$controllerName,
		$actionName,
		array $requirements = [],
		array $defaults = [],
	) {
		$route = $this->getMockBuilder(Route::class)
			->onlyMethods(['method', 'requirements', 'defaults'])
			->disableOriginalConstructor()
			->getMock();
		$route
			->expects($this->once())
			->method('method')
			->with($this->equalTo($verb))
			->willReturn($route);

		if (count($requirements) > 0) {
			$route
				->expects($this->once())
				->method('requirements')
				->with($this->equalTo($requirements))
				->willReturn($route);
		}

		$route->expects($this->once())
			->method('defaults')
			->with($this->callback(function (array $def) use ($defaults, $controllerName, $actionName) {
				$defaults['caller'] = ['app1', $controllerName, $actionName];

				$this->assertEquals($defaults, $def);
				return true;
			}))
			->willReturn($route);

		return $route;
	}
}
