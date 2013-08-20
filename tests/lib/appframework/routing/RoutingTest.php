<?php

namespace OC\AppFramework\Routing;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\routing\RouteConfig;


class RouteConfigTest extends \PHPUnit_Framework_TestCase
{

	public function testSimpleRoute()
	{
		$routes = array('routes' => array(
			array('name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'GET')
		));

		$this->assertSimpleRoute($routes, 'folders.open', 'GET', '/folders/{folderId}/open', 'FoldersController', 'open');
	}

	public function testSimpleRouteWithMissingVerb()
	{
		$routes = array('routes' => array(
			array('name' => 'folders#open', 'url' => '/folders/{folderId}/open')
		));

		$this->assertSimpleRoute($routes, 'folders.open', 'GET', '/folders/{folderId}/open', 'FoldersController', 'open');
	}

	public function testSimpleRouteWithLowercaseVerb()
	{
		$routes = array('routes' => array(
			array('name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete')
		));

		$this->assertSimpleRoute($routes, 'folders.open', 'DELETE', '/folders/{folderId}/open', 'FoldersController', 'open');
	}

	/**
	 * @expectedException \UnexpectedValueException
	 */
	public function testSimpleRouteWithBrokenName()
	{
		$routes = array('routes' => array(
			array('name' => 'folders_open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete')
		));

		// router mock
		$router = $this->getMock("\OC_Router", array('create'));

		// load route configuration
		$container = new DIContainer('app1');
		$config = new RouteConfig($container, $router, $routes);

		$config->register();
	}

	public function testSimpleRouteWithUnderScoreNames()
	{
		$routes = array('routes' => array(
			array('name' => 'admin_folders#open_current', 'url' => '/folders/{folderId}/open', 'verb' => 'delete')
		));

		$this->assertSimpleRoute($routes, 'admin_folders.open_current', 'DELETE', '/folders/{folderId}/open', 'AdminFoldersController', 'openCurrent');
	}

	public function testResource()
	{
		$routes = array('resources' => array('accounts' => array('url' => '/accounts')));

		$this->assertResource($routes, 'accounts', '/accounts', 'AccountsController', 'accountId');
	}

	public function testResourceWithUnderScoreName()
	{
		$routes = array('resources' => array('admin_accounts' => array('url' => '/admin/accounts')));

		$this->assertResource($routes, 'admin_accounts', '/admin/accounts', 'AdminAccountsController', 'adminAccountId');
	}

	private function assertSimpleRoute($routes, $name, $verb, $url, $controllerName, $actionName)
	{
		// route mocks
		$route = $this->mockRoute($verb, $controllerName, $actionName);

		// router mock
		$router = $this->getMock("\OC_Router", array('create'));

		// we expect create to be called once:
		$router
			->expects($this->once())
			->method('create')
			->with($this->equalTo('app1.' . $name), $this->equalTo($url))
			->will($this->returnValue($route));

		// load route configuration
		$container = new DIContainer('app1');
		$config = new RouteConfig($container, $router, $routes);

		$config->register();
	}

	private function assertResource($yaml, $resourceName, $url, $controllerName, $paramName)
	{
		// router mock
		$router = $this->getMock("\OC_Router", array('create'));

		// route mocks
		$indexRoute = $this->mockRoute('GET', $controllerName, 'index');
		$showRoute = $this->mockRoute('GET', $controllerName, 'show');
		$createRoute = $this->mockRoute('POST', $controllerName, 'create');
		$updateRoute = $this->mockRoute('PUT', $controllerName, 'update');
		$destroyRoute = $this->mockRoute('DELETE', $controllerName, 'destroy');

		$urlWithParam = $url . '/{' . $paramName . '}';

		// we expect create to be called once:
		$router
			->expects($this->at(0))
			->method('create')
			->with($this->equalTo('app1.' . $resourceName . '.index'), $this->equalTo($url))
			->will($this->returnValue($indexRoute));

		$router
			->expects($this->at(1))
			->method('create')
			->with($this->equalTo('app1.' . $resourceName . '.show'), $this->equalTo($urlWithParam))
			->will($this->returnValue($showRoute));

		$router
			->expects($this->at(2))
			->method('create')
			->with($this->equalTo('app1.' . $resourceName . '.create'), $this->equalTo($url))
			->will($this->returnValue($createRoute));

		$router
			->expects($this->at(3))
			->method('create')
			->with($this->equalTo('app1.' . $resourceName . '.update'), $this->equalTo($urlWithParam))
			->will($this->returnValue($updateRoute));

		$router
			->expects($this->at(4))
			->method('create')
			->with($this->equalTo('app1.' . $resourceName . '.destroy'), $this->equalTo($urlWithParam))
			->will($this->returnValue($destroyRoute));

		// load route configuration
		$container = new DIContainer('app1');
		$config = new RouteConfig($container, $router, $yaml);

		$config->register();
	}

	/**
	 * @param $verb
	 * @param $controllerName
	 * @param $actionName
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function mockRoute($verb, $controllerName, $actionName)
	{
		$container = new DIContainer('app1');
		$route = $this->getMock("\OC_Route", array('method', 'action'), array(), '', false);
		$route
			->expects($this->exactly(1))
			->method('method')
			->with($this->equalTo($verb))
			->will($this->returnValue($route));

		$route
			->expects($this->exactly(1))
			->method('action')
			->with($this->equalTo(new RouteActionHandler($container, $controllerName, $actionName)))
			->will($this->returnValue($route));
		return $route;
	}

}

/*
#
# sample routes.yaml for ownCloud
#
# the section simple describes one route

routes:
        - name: folders#open
          url: /folders/{folderId}/open
          verb: GET
          # controller: name.split()[0]
          # action: name.split()[1]

# for a resource following actions will be generated:
# - index
# - create
# - show
# - update
# - destroy
# - new
resources:
    accounts:
        url: /accounts

    folders:
        url: /accounts/{accountId}/folders
        # actions can be used to define additional actions on the resource
        actions:
            - name: validate
              verb: GET
              on-collection: false

 * */
