<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
//use Symfony\Component\Routing\Route;

class OC_Router {
	protected $collections = array();
	protected $collection = null;
	protected $root = null;

	public function __construct() {
		// TODO cache
		$this->root = $this->getCollection('root');
	}

	/**
	 * loads the api routes
	 */
	public function loadRoutes() {
		foreach(OC_APP::getEnabledApps() as $app){
			$file = OC_App::getAppPath($app).'/appinfo/routes.php';
			if(file_exists($file)){
				$this->useCollection($app);
				require_once($file);
				$collection = $this->getCollection($app);
				$this->root->addCollection($collection, '/apps/'.$app);
			}
		}
	}

	protected function getCollection($name) {
		if (!isset($this->collections[$name])) {
			$this->collections[$name] = new RouteCollection();
		}
		return $this->collections[$name];
	}

	public function useCollection($name) {
		$this->collection = $this->getCollection($name);
	}

	public function create($name, $pattern, array $defaults = array(), array $requirements = array()) {
		$route = new OC_Route($pattern, $defaults, $requirements);
		$this->collection->add($name, $route);
		return $route;
	}

    	public function match($url) {
		$context = new RequestContext($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
		$matcher = new UrlMatcher($this->root, $context);
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
			include ($parameters['file']);
		} else {
			throw new Exception('no action available');
		}
	}
}
