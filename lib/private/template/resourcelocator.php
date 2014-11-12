<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Template;

abstract class ResourceLocator {
	protected $theme;

	protected $mapping;
	protected $serverroot;
	protected $thirdpartyroot;
	protected $webroot;

	protected $resources = array();

	/**
	 * @param string $theme
	 */
	public function __construct( $theme, $core_map, $party_map ) {
		$this->theme = $theme;
		$this->mapping = $core_map + $party_map;
		$this->serverroot = key($core_map);
		$this->thirdpartyroot = key($party_map);
		$this->webroot = $this->mapping[$this->serverroot];
	}

	abstract public function doFind( $resource );
	abstract public function doFindTheme( $resource );

	public function find( $resources ) {
		try {
			foreach($resources as $resource) {
				$this->doFind($resource);
			}
			if (!empty($this->theme)) {
				foreach($resources as $resource) {
					$this->doFindTheme($resource);
				}
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage().' serverroot:'.$this->serverroot);
		}
	}

	/*
	 * append the $file resource if exist at $root
	 * @param string $root path to check
	 * @param string $file the filename
	 * @param string|null $webroot base for path, default map $root to $webroot
	 */
	protected function appendIfExist($root, $file, $webroot = null) {
		if (is_file($root.'/'.$file)) {
			if (!$webroot) {
				$webroot = $this->mapping[$root];
			}
			$this->resources[] = array($root, $webroot, $file);
			return true;
		}
		return false;
	}

	public function getResources() {
		return $this->resources;
	}
}
