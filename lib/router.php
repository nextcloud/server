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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class OC_Router {
	protected $collections = array();
	protected $collection = null;

	/**
	 * loads the api routes
	 */
	public function loadRoutes(){
		// TODO cache
		foreach(OC_APP::getEnabledApps() as $app){
			$file = OC_App::getAppPath($app).'/appinfo/routes.php';
			if(file_exists($file)){
				require_once($file);
			}
		}
	}

	public function useCollection($name) {
		if (!isset($this->collections[$name])) {
			$this->collections[$name] = new RouteCollection();
		}
		$this->collection = $this->collections[$name];
	}

	public function create($name, $pattern, array $defaults = array(), array $requirements = array()) {
		$route = new OC_Route($pattern, $defaults, $requirements);
		$this->collection->add($name, $route);
		return $route;
	}

    	public function match($url) {
		$context = new RequestContext($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
		$matcher = new UrlMatcher($this->collection, $context);
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
