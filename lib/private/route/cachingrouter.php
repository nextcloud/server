<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Route;

class CachingRouter extends Router {
	/**
	 * @var \OCP\ICache
	 */
	protected $cache;

	/**
	 * @param \OCP\ICache $cache
	 */
	public function __construct($cache) {
		$this->cache = $cache;
		parent::__construct();
	}

	/**
	 * Generate url based on $name and $parameters
	 *
	 * @param string $name Name of the route to use.
	 * @param array $parameters Parameters for the route
	 * @param bool $absolute
	 * @return string
	 */
	public function generate($name, $parameters = array(), $absolute = false) {
		asort($parameters);
		$key = $this->context->getHost() . '#' . $this->context->getBaseUrl() . $name . json_encode($parameters) . intval($absolute);
		if ($this->cache->hasKey($key)) {
			return $this->cache->get($key);
		} else {
			$url = parent::generate($name, $parameters, $absolute);
			$this->cache->set($key, $url, 3600);
			return $url;
		}
	}
}
