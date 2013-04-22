<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
//use Symfony\Component\Routing\Route;

class OC_Router {
	protected $collections = array();
	protected $collection = null;
	protected $root = null;

	protected $generator = null;
	protected $routing_files;
	protected $cache_key;

	public function __construct() {
		$baseUrl = OC_Helper::linkTo('', 'index.php');
		if ( !OC::$CLI) {
			$method = $_SERVER['REQUEST_METHOD'];
		}else{
			$method = 'GET';
		}
		$host = OC_Request::serverHost();
		$schema = OC_Request::serverProtocol();
		$this->context = new RequestContext($baseUrl, $method, $host, $schema);
		// TODO cache
		$this->root = $this->getCollection('root');
	}

	public function getRoutingFiles() {
		if (!isset($this->routing_files)) {
			$this->routing_files = array();
			foreach(OC_APP::getEnabledApps() as $app) {
				$file = OC_App::getAppPath($app).'/appinfo/routes.php';
				if(file_exists($file)) {
					$this->routing_files[$app] = $file;
				}
			}
		}
		return $this->routing_files;
	}

	public function getCacheKey() {
		if (!isset($this->cache_key)) {
			$files = $this->getRoutingFiles();
			$files[] = 'settings/routes.php';
			$files[] = 'core/routes.php';
			$files[] = 'ocs/routes.php';
			$this->cache_key = OC_Cache::generateCacheKeyFromFiles($files);
		}
		return $this->cache_key;
	}

	/**
	 * loads the api routes
	 */
	public function loadRoutes() {
		foreach($this->getRoutingFiles() as $app => $file) {
			$this->useCollection($app);
			require_once $file;
			$collection = $this->getCollection($app);
			$this->root->addCollection($collection, '/apps/'.$app);
		}
		$this->useCollection('root');
		require_once 'settings/routes.php';
		require_once 'core/routes.php';

		// include ocs routes
		require_once 'ocs/routes.php';
		$collection = $this->getCollection('ocs');
		$this->root->addCollection($collection, '/ocs');
	}

	protected function getCollection($name) {
		if (!isset($this->collections[$name])) {
			$this->collections[$name] = new RouteCollection();
		}
		return $this->collections[$name];
	}

	/**
	 * Sets the collection to use for adding routes
	 *
	 * @param string $name Name of the colletion to use.
	 */
	public function useCollection($name) {
		$this->collection = $this->getCollection($name);
	}

	/**
	 * Create a OC_Route.
	 *
	 * @param string $name Name of the route to create.
	 * @param string $pattern The pattern to match
	 * @param array  $defaults     An array of default parameter values
	 * @param array  $requirements An array of requirements for parameters (regexes)
	 */
	public function create($name, $pattern, array $defaults = array(), array $requirements = array()) {
		$route = new OC_Route($pattern, $defaults, $requirements);
		$this->collection->add($name, $route);
		return $route;
	}

	/**
	 * Find the route matching $url.
	 *
	 * @param string $url The url to find
	 */
	public function match($url) {
		$matcher = new UrlMatcher($this->root, $this->context);
		$parameters = $matcher->match($url);
		if (isset($parameters['action'])) {
			$action = $parameters['action'];
			if (!is_callable($action)) {
				var_dump($action);
				throw new Exception('not a callable action');
			}
			unset($parameters['action']);
			call_user_func($action, $parameters);
		} elseif (isset($parameters['file'])) {
			include $parameters['file'];
		} else {
			throw new Exception('no action available');
		}
	}

	/**
	 * Get the url generator
	 *
	 */
	public function getGenerator()
	{
		if (null !== $this->generator) {
			return $this->generator;
		}

		return $this->generator = new UrlGenerator($this->root, $this->context);
	}

	/**
	 * Generate url based on $name and $parameters
	 *
	 * @param string $name Name of the route to use.
	 * @param array $parameters Parameters for the route
	 */
	public function generate($name, $parameters = array(), $absolute = false)
	{
		return $this->getGenerator()->generate($name, $parameters, $absolute);
	}

	/**
	 * Generate JSON response for routing in javascript
	 */
	public static function JSRoutes()
	{
		$router = OC::getRouter();

		$etag = $router->getCacheKey();
		OC_Response::enableCaching();
		OC_Response::setETagHeader($etag);

		$root = $router->getCollection('root');
		$routes = array();
		foreach($root->all() as $name => $route) {
			$compiled_route = $route->compile();
			$defaults = $route->getDefaults();
			unset($defaults['action']);
			$routes[$name] = array(
				'tokens' => $compiled_route->getTokens(),
				'defaults' => $defaults,
			);
		}
		OCP\JSON::success ( array( 'data' => $routes ) );
	}
}
