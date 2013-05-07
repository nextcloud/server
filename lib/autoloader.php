<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

class Autoloader {
	public function load($class) {
		$class = trim($class, '\\');

		if (array_key_exists($class, \OC::$CLASSPATH)) {
			$path = \OC::$CLASSPATH[$class];
			/** @TODO: Remove this when necessary
			Remove "apps/" from inclusion path for smooth migration to mutli app dir
			 */
			if (strpos($path, 'apps/') === 0) {
				\OC_Log::write('core', 'include path for class "' . $class . '" starts with "apps/"', \OC_Log::DEBUG);
				$path = str_replace('apps/', '', $path);
			}
		} elseif (strpos($class, 'OC_') === 0) {
			$path = strtolower(str_replace('_', '/', substr($class, 3)) . '.php');
		} elseif (strpos($class, 'OC\\') === 0) {
			$path = strtolower(str_replace('\\', '/', substr($class, 3)) . '.php');
		} elseif (strpos($class, 'OCP\\') === 0) {
			$path = 'public/' . strtolower(str_replace('\\', '/', substr($class, 3)) . '.php');
		} elseif (strpos($class, 'OCA\\') === 0) {
			foreach (\OC::$APPSROOTS as $appDir) {
				$path = strtolower(str_replace('\\', '/', substr($class, 4)) . '.php');
				$fullPath = stream_resolve_include_path($appDir['path'] . '/' . $path);
				if (file_exists($fullPath)) {
					require_once $fullPath;
					return false;
				}
				// If not found in the root of the app directory, insert '/lib' after app id and try again.
				$libpath = substr($path, 0, strpos($path, '/')) . '/lib' . substr($path, strpos($path, '/'));
				$fullPath = stream_resolve_include_path($appDir['path'] . '/' . $libpath);
				if (file_exists($fullPath)) {
					require_once $fullPath;
					return false;
				}
			}
		} elseif (strpos($class, 'Sabre_') === 0) {
			$path = str_replace('_', '/', $class) . '.php';
		} elseif (strpos($class, 'Symfony\\Component\\Routing\\') === 0) {
			$path = 'symfony/routing/' . str_replace('\\', '/', $class) . '.php';
		} elseif (strpos($class, 'Sabre\\VObject') === 0) {
			$path = str_replace('\\', '/', $class) . '.php';
		} elseif (strpos($class, 'Test_') === 0) {
			$path = 'tests/lib/' . strtolower(str_replace('_', '/', substr($class, 5)) . '.php');
		} elseif (strpos($class, 'Test\\') === 0) {
			$path = 'tests/lib/' . strtolower(str_replace('\\', '/', substr($class, 5)) . '.php');
		} else {
			return false;
		}

		if ($fullPath = stream_resolve_include_path($path)) {
			require_once $fullPath;
		}
		return false;
	}
}
