<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\AppFramework\Routing;

use OC\AppFramework\Routing\RouteParser;
use Symfony\Component\Routing\Route as RoutingRoute;
use Symfony\Component\Routing\RouteCollection;

class RouteParserTest extends \Test\TestCase {

	protected RouteParser $parser;

	protected function setUp(): void {
		$this->parser = new RouteParser();
	}

	public function testParseRoutes(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET'],
			['name' => 'folders#create', 'url' => '/{folderId}/create', 'verb' => 'POST']
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'open', route: $collection->get('app1.folders.open'));
		$this->assertArrayHasKey('app1.folders.create', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/create', 'POST', 'FoldersController', 'create', route: $collection->get('app1.folders.create'));
	}

	public function testParseRoutesRootApps(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET'],
			['name' => 'folders#create', 'url' => '/{folderId}/create', 'verb' => 'POST']
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'core');
		$this->assertArrayHasKey('core.folders.open', $collection->all());
		$this->assertSimpleRoute('/{folderId}/open', 'GET', 'FoldersController', 'open', app: 'core', route: $collection->get('core.folders.open'));
		$this->assertArrayHasKey('core.folders.create', $collection->all());
		$this->assertSimpleRoute('/{folderId}/create', 'POST', 'FoldersController', 'create', app: 'core', route: $collection->get('core.folders.create'));
	}

	public function testParseRoutesWithResources(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET'],
		], 'resources' => [
			'names' => ['url' => '/names'],
			'folder_names' => ['url' => '/folder/names'],
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.folders.open', $collection->all());
		$this->assertSimpleResource('/apps/app1/folder/names', 'folder_names', 'FolderNamesController', 'app1', $collection);
		$this->assertSimpleResource('/apps/app1/names', 'names', 'NamesController', 'app1', $collection);
	}

	public function testParseRoutesWithPostfix(): void {
		$routes = ['routes' => [
			['name' => 'folders#update', 'url' => '/{folderId}/update', 'verb' => 'POST'],
			['name' => 'folders#update', 'url' => '/{folderId}/update', 'verb' => 'PUT', 'postfix' => '-edit']
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.folders.update', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/update', 'POST', 'FoldersController', 'update', route: $collection->get('app1.folders.update'));
		$this->assertArrayHasKey('app1.folders.update-edit', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/update', 'PUT', 'FoldersController', 'update', route: $collection->get('app1.folders.update-edit'));
	}

	public function testParseRoutesKebabCaseAction(): void {
		$routes = ['routes' => [
			['name' => 'folders#open_folder', 'url' => '/{folderId}/open', 'verb' => 'GET']
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.folders.open_folder', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'openFolder', route: $collection->get('app1.folders.open_folder'));
	}

	public function testParseRoutesKebabCaseController(): void {
		$routes = ['routes' => [
			['name' => 'my_folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET']
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.my_folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'MyFoldersController', 'open', route: $collection->get('app1.my_folders.open'));
	}

	public function testParseRoutesLowercaseVerb(): void {
		$routes = ['routes' => [
			['name' => 'folders#delete', 'url' => '/{folderId}/delete', 'verb' => 'delete']
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.folders.delete', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/delete', 'DELETE', 'FoldersController', 'delete', route: $collection->get('app1.folders.delete'));
	}

	public function testParseRoutesMissingVerb(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open']
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'open', route: $collection->get('app1.folders.open'));
	}

	public function testParseRoutesWithRequirements(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET', 'requirements' => ['folderId' => '\d+']]
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'open', requirements: ['folderId' => '\d+'], route: $collection->get('app1.folders.open'));
	}

	public function testParseRoutesWithDefaults(): void {
		$routes = ['routes' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET', 'defaults' => ['hello' => 'world']]
		]];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertArrayHasKey('app1.folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'open', defaults: ['hello' => 'world'], route: $collection->get('app1.folders.open'));
	}

	public function testParseRoutesInvalidName(): void {
		$routes = ['routes' => [
			['name' => 'folders', 'url' => '/{folderId}/open', 'verb' => 'GET']
		]];

		$this->expectException(\UnexpectedValueException::class);
		$this->parser->parseDefaultRoutes($routes, 'app1');
	}

	public function testParseRoutesInvalidName2(): void {
		$routes = ['routes' => [
			['name' => 'folders#open#action', 'url' => '/{folderId}/open', 'verb' => 'GET']
		]];

		$this->expectException(\UnexpectedValueException::class);
		$this->parser->parseDefaultRoutes($routes, 'app1');
	}

	public function testParseRoutesEmpty(): void {
		$routes = ['routes' => []];

		$collection = $this->parser->parseDefaultRoutes($routes, 'app1');
		$this->assertEquals(0, $collection->count());
	}

	// OCS routes

	public function testParseOcsRoutes(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET'],
			['name' => 'folders#create', 'url' => '/{folderId}/create', 'verb' => 'POST']
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'open', route: $collection->get('ocs.app1.folders.open'));
		$this->assertArrayHasKey('ocs.app1.folders.create', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/create', 'POST', 'FoldersController', 'create', route: $collection->get('ocs.app1.folders.create'));
	}

	public function testParseOcsRoutesRootApps(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET'],
			['name' => 'folders#create', 'url' => '/{folderId}/create', 'verb' => 'POST']
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'core');
		$this->assertArrayHasKey('ocs.core.folders.open', $collection->all());
		$this->assertSimpleRoute('/{folderId}/open', 'GET', 'FoldersController', 'open', app: 'core', route: $collection->get('ocs.core.folders.open'));
		$this->assertArrayHasKey('ocs.core.folders.create', $collection->all());
		$this->assertSimpleRoute('/{folderId}/create', 'POST', 'FoldersController', 'create', app: 'core', route: $collection->get('ocs.core.folders.create'));
	}

	public function testParseOcsRoutesWithPostfix(): void {
		$routes = ['ocs' => [
			['name' => 'folders#update', 'url' => '/{folderId}/update', 'verb' => 'POST'],
			['name' => 'folders#update', 'url' => '/{folderId}/update', 'verb' => 'PUT', 'postfix' => '-edit']
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.folders.update', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/update', 'POST', 'FoldersController', 'update', route: $collection->get('ocs.app1.folders.update'));
		$this->assertArrayHasKey('ocs.app1.folders.update-edit', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/update', 'PUT', 'FoldersController', 'update', route: $collection->get('ocs.app1.folders.update-edit'));
	}

	public function testParseOcsRoutesKebabCaseAction(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open_folder', 'url' => '/{folderId}/open', 'verb' => 'GET']
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.folders.open_folder', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'openFolder', route: $collection->get('ocs.app1.folders.open_folder'));
	}

	public function testParseOcsRoutesKebabCaseController(): void {
		$routes = ['ocs' => [
			['name' => 'my_folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET']
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.my_folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'MyFoldersController', 'open', route: $collection->get('ocs.app1.my_folders.open'));
	}

	public function testParseOcsRoutesLowercaseVerb(): void {
		$routes = ['ocs' => [
			['name' => 'folders#delete', 'url' => '/{folderId}/delete', 'verb' => 'delete']
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.folders.delete', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/delete', 'DELETE', 'FoldersController', 'delete', route: $collection->get('ocs.app1.folders.delete'));
	}

	public function testParseOcsRoutesMissingVerb(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open']
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'open', route: $collection->get('ocs.app1.folders.open'));
	}

	public function testParseOcsRoutesWithRequirements(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET', 'requirements' => ['folderId' => '\d+']]
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'open', requirements: ['folderId' => '\d+'], route: $collection->get('ocs.app1.folders.open'));
	}

	public function testParseOcsRoutesWithDefaults(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET', 'defaults' => ['hello' => 'world']]
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.folders.open', $collection->all());
		$this->assertSimpleRoute('/apps/app1/{folderId}/open', 'GET', 'FoldersController', 'open', defaults: ['hello' => 'world'], route: $collection->get('ocs.app1.folders.open'));
	}

	public function testParseOcsRoutesInvalidName(): void {
		$routes = ['ocs' => [
			['name' => 'folders', 'url' => '/{folderId}/open', 'verb' => 'GET']
		]];

		$this->expectException(\UnexpectedValueException::class);
		$this->parser->parseOCSRoutes($routes, 'app1');
	}

	public function testParseOcsRoutesEmpty(): void {
		$routes = ['ocs' => []];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertEquals(0, $collection->count());
	}

	public function testParseOcsRoutesWithResources(): void {
		$routes = ['ocs' => [
			['name' => 'folders#open', 'url' => '/{folderId}/open', 'verb' => 'GET'],
		], 'ocs-resources' => [
			'names' => ['url' => '/names', 'root' => '/core/something'],
			'folder_names' => ['url' => '/folder/names'],
		]];

		$collection = $this->parser->parseOCSRoutes($routes, 'app1');
		$this->assertArrayHasKey('ocs.app1.folders.open', $collection->all());
		$this->assertOcsResource('/apps/app1/folder/names', 'folder_names', 'FolderNamesController', 'app1', $collection);
		$this->assertOcsResource('/core/something/names', 'names', 'NamesController', 'app1', $collection);
	}

	protected function assertSimpleRoute(
		string $path,
		string $method,
		string $controller,
		string $action,
		string $app = 'app1',
		array $requirements = [],
		array $defaults = [],
		?RoutingRoute $route = null,
	): void {
		self::assertEquals($path, $route->getPath());
		self::assertEqualsCanonicalizing([$method], $route->getMethods());
		self::assertEqualsCanonicalizing($requirements, $route->getRequirements());
		self::assertEquals([...$defaults, 'action' => null, 'caller' => [$app, $controller, $action]], $route->getDefaults());
	}

	protected function assertSimpleResource(
		string $path,
		string $resourceName,
		string $controller,
		string $app,
		RouteCollection $collection,
	): void {
		self::assertArrayHasKey("$app.$resourceName.index", $collection->all());
		self::assertArrayHasKey("$app.$resourceName.show", $collection->all());
		self::assertArrayHasKey("$app.$resourceName.create", $collection->all());
		self::assertArrayHasKey("$app.$resourceName.update", $collection->all());
		self::assertArrayHasKey("$app.$resourceName.destroy", $collection->all());

		$this->assertSimpleRoute($path, 'GET', $controller, 'index', $app, route: $collection->get("$app.$resourceName.index"));
		$this->assertSimpleRoute($path, 'POST', $controller, 'create', $app, route: $collection->get("$app.$resourceName.create"));
		$this->assertSimpleRoute("$path/{id}", 'GET', $controller, 'show', $app, route: $collection->get("$app.$resourceName.show"));
		$this->assertSimpleRoute("$path/{id}", 'PUT', $controller, 'update', $app, route: $collection->get("$app.$resourceName.update"));
		$this->assertSimpleRoute("$path/{id}", 'DELETE', $controller, 'destroy', $app, route: $collection->get("$app.$resourceName.destroy"));
	}

	protected function assertOcsResource(
		string $path,
		string $resourceName,
		string $controller,
		string $app,
		RouteCollection $collection,
	): void {
		self::assertArrayHasKey("ocs.$app.$resourceName.index", $collection->all());
		self::assertArrayHasKey("ocs.$app.$resourceName.show", $collection->all());
		self::assertArrayHasKey("ocs.$app.$resourceName.create", $collection->all());
		self::assertArrayHasKey("ocs.$app.$resourceName.update", $collection->all());
		self::assertArrayHasKey("ocs.$app.$resourceName.destroy", $collection->all());

		$this->assertSimpleRoute($path, 'GET', $controller, 'index', $app, route: $collection->get("ocs.$app.$resourceName.index"));
		$this->assertSimpleRoute($path, 'POST', $controller, 'create', $app, route: $collection->get("ocs.$app.$resourceName.create"));
		$this->assertSimpleRoute("$path/{id}", 'GET', $controller, 'show', $app, route: $collection->get("ocs.$app.$resourceName.show"));
		$this->assertSimpleRoute("$path/{id}", 'PUT', $controller, 'update', $app, route: $collection->get("ocs.$app.$resourceName.update"));
		$this->assertSimpleRoute("$path/{id}", 'DELETE', $controller, 'destroy', $app, route: $collection->get("ocs.$app.$resourceName.destroy"));
	}
}
