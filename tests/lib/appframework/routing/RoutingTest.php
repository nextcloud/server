<?php

namespace OC\AppFramework\Routing;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\routing\RouteConfig;


class RoutingTest extends \Test\TestCase
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

	public function testSimpleRouteWithRequirements()
	{
		$routes = array('routes' => array(
			array('name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', 'requirements' => array('something'))
		));

		$this->assertSimpleRoute($routes, 'folders.open', 'DELETE', '/folders/{folderId}/open', 'FoldersController', 'open', array('something'));
	}

	public function testSimpleRouteWithDefaults()
	{
		$routes = array('routes' => array(
			array('name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', array(), 'defaults' => array('param' => 'foobar'))
		));

		$this->assertSimpleRoute($routes, 'folders.open', 'DELETE', '/folders/{folderId}/open', 'FoldersController', 'open', array(), array('param' => 'foobar'));
	}

	public function testSimpleRouteWithPostfix()
	{
		$routes = array('routes' => array(
			array('name' => 'folders#open', 'url' => '/folders/{folderId}/open', 'verb' => 'delete', 'postfix' => '_something')
		));

		$this->assertSimpleRoute($routes, 'folders.open', 'DELETE', '/folders/{folderId}/open', 'FoldersController', 'open', array(), array(), '_something');
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
		$router = $this->getMock("\OC\Route\Router", array('create'), [\OC::$server->getLogger()]);

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
		$routes = array('resources' => array('account' => array('url' => '/accounts')));

		$this->assertResource($routes, 'account', '/accounts', 'AccountController', 'id');
	}

	public function testResourceWithUnderScoreName()
	{
		$routes = array('resources' => array('admin_accounts' => array('url' => '/admin/accounts')));

		$this->assertResource($routes, 'admin_accounts', '/admin/accounts', 'AdminAccountsController', 'id');
	}

	/**
	 * @param string $name
	 * @param string $verb
	 * @param string $url
	 * @param string $controllerName
	 * @param string $actionName
	 */
	private function assertSimpleRoute($routes, $name, $verb, $url, $controllerName, $actionName, array $requirements=array(), array $defaults=array(), $postfix='')
	{
		if ($postfix) {
			$name .= $postfix;
		}

		// route mocks
		$container = new DIContainer('app1');
		$route = $this->mockRoute($container, $verb, $controllerName, $actionName, $requirements, $defaults);

		// router mock
		$router = $this->getMock("\OC\Route\Router", array('create'), [\OC::$server->getLogger()]);

		// we expect create to be called once:
		$router
			->expects($this->once())
			->method('create')
			->with($this->equalTo('app1.' . $name), $this->equalTo($url))
			->will($this->returnValue($route));

		// load route configuration
		$config = new RouteConfig($container, $router, $routes);

		$config->register();
	}

	/**
	 * @param string $resourceName
	 * @param string $url
	 * @param string $controllerName
	 * @param string $paramName
	 */
	private function assertResource($yaml, $resourceName, $url, $controllerName, $paramName)
	{
		// router mock
		$router = $this->getMock("\OC\Route\Router", array('create'), [\OC::$server->getLogger()]);

		// route mocks
		$container = new DIContainer('app1');
		$indexRoute = $this->mockRoute($container, 'GET', $controllerName, 'index');
		$showRoute = $this->mockRoute($container, 'GET', $controllerName, 'show');
		$createRoute = $this->mockRoute($container, 'POST', $controllerName, 'create');
		$updateRoute = $this->mockRoute($container, 'PUT', $controllerName, 'update');
		$destroyRoute = $this->mockRoute($container, 'DELETE', $controllerName, 'destroy');

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
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function mockRoute(
		DIContainer $container,
		$verb,
		$controllerName,
		$actionName,
		array $requirements=array(),
		array $defaults=array()
	) {
		$route = $this->getMock("\OC\Route\Route", array('method', 'action', 'requirements', 'defaults'), array(), '', false);
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

		if(count($requirements) > 0) {
			$route
				->expects($this->exactly(1))
				->method('requirements')
				->with($this->equalTo($requirements))
				->will($this->returnValue($route));
		}

		if (count($defaults) > 0) {
			$route
				->expects($this->exactly(1))
				->method('defaults')
				->with($this->equalTo($defaults))
				->will($this->returnValue($route));
		}

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
