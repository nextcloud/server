<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

class Autoloader {
	private $useGlobalClassPath = true;

	private $classPaths = array();

	/**
	 * Add a custom classpath to the autoloader
	 *
	 * @param string $class
	 * @param string $path
	 */
	public function registerClass($class, $path) {
		$this->classPaths[$class] = $path;
	}

	/**
	 * disable the usage of the global classpath \OC::$CLASSPATH
	 */
	public function disableGlobalClassPath() {
		$this->useGlobalClassPath = false;
	}

	/**
	 * enable the usage of the global classpath \OC::$CLASSPATH
	 */
	public function enableGlobalClassPath() {
		$this->useGlobalClassPath = true;
	}

	/**
	 * Load the specified class
	 *
	 * @param string $class
	 * @return bool
	 */
	public function load($class) {
		$class = trim($class, '\\');

		$paths = array();
		if (array_key_exists($class, $this->classPaths)) {
			$paths[] = $this->classPaths[$class];
		} else if ($this->useGlobalClassPath and array_key_exists($class, \OC::$CLASSPATH)) {
			$paths[] = \OC::$CLASSPATH[$class];
			/**
			 * @TODO: Remove this when necessary
			 * Remove "apps/" from inclusion path for smooth migration to mutli app dir
			 */
			if (strpos(\OC::$CLASSPATH[$class], 'apps/') === 0) {
				\OC_Log::write('core', 'include path for class "' . $class . '" starts with "apps/"', \OC_Log::DEBUG);
				$path = str_replace('apps/', '', \OC::$CLASSPATH[$class]);
			}
		} elseif (strpos($class, 'OC_') === 0) {
			$paths[] = strtolower(str_replace('_', '/', substr($class, 3)) . '.php');
		} elseif (strpos($class, 'OC\\') === 0) {
			$paths[] = strtolower(str_replace('\\', '/', substr($class, 3)) . '.php');
		} elseif (strpos($class, 'OCP\\') === 0) {
			$paths[] = 'public/' . strtolower(str_replace('\\', '/', substr($class, 3)) . '.php');
		} elseif (strpos($class, 'OCA\\') === 0) {
			foreach (\OC::$APPSROOTS as $appDir) {
				$paths[] = $appDir['path'] . '/' . strtolower(str_replace('\\', '/', substr($class, 4)) . '.php');
				// If not found in the root of the app directory, insert '/lib' after app id and try again.
				$paths[] = $appDir['path'] . '/lib/' . strtolower(str_replace('\\', '/', substr($class, 4)) . '.php');
			}
		} elseif (strpos($class, 'Sabre_') === 0) {
			$paths[] = str_replace('_', '/', $class) . '.php';
		} elseif (strpos($class, 'Symfony\\Component\\Routing\\') === 0) {
			$paths[] = 'symfony/routing/' . str_replace('\\', '/', $class) . '.php';
		} elseif (strpos($class, 'Sabre\\VObject') === 0) {
			$paths[] = str_replace('\\', '/', $class) . '.php';
		} elseif (strpos($class, 'Test_') === 0) {
			$paths[] = 'tests/lib/' . strtolower(str_replace('_', '/', substr($class, 5)) . '.php');
		} elseif (strpos($class, 'Test\\') === 0) {
			$paths[] = 'tests/lib/' . strtolower(str_replace('\\', '/', substr($class, 5)) . '.php');
		} else {
			return false;
		}

		foreach ($paths as $path) {
			if ($fullPath = stream_resolve_include_path($path)) {
				require_once $fullPath;
			}
		}
		return false;
	}
}
