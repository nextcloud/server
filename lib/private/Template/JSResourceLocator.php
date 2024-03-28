<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Kyle Fazzari <kyrofa@ubuntu.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Template;

use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;

class JSResourceLocator extends ResourceLocator {
	protected JSCombiner $jsCombiner;
	protected IAppManager $appManager;

	public function __construct(LoggerInterface $logger, JSCombiner $JSCombiner, IAppManager $appManager) {
		parent::__construct($logger);

		$this->jsCombiner = $JSCombiner;
		$this->appManager = $appManager;
	}

	/**
	 * @param string $script
	 */
	public function doFind($script) {
		$theme_dir = 'themes/'.$this->theme.'/';

		// Extracting the appId and the script file name
		$app = substr($script, 0, strpos($script, '/'));
		$scriptName = basename($script);
		// Get the app root path
		$appRoot = $this->serverroot . '/apps/';
		$appWebRoot = null;
		try {
			// We need the dir name as getAppPath appends the appid
			$appRoot = dirname($this->appManager->getAppPath($app));
			// Only do this if $app_path is set, because an empty argument to realpath gets turned into cwd.
			if ($appRoot) {
				// Handle symlinks
				$appRoot = realpath($appRoot);
			}
			// Get the app webroot
			$appWebRoot = dirname($this->appManager->getAppWebPath($app));
		} catch (AppPathNotFoundException $e) {
			// ignore
		}

		if (str_contains($script, '/l10n/')) {
			// For language files we try to load them all, so themes can overwrite
			// single l10n strings without having to translate all of them.
			$found = 0;
			$found += $this->appendScriptIfExist($this->serverroot, 'core/'.$script);
			$found += $this->appendScriptIfExist($this->serverroot, $theme_dir.'core/'.$script);
			$found += $this->appendScriptIfExist($this->serverroot, $script);
			$found += $this->appendScriptIfExist($this->serverroot, $theme_dir.$script);
			$found += $this->appendScriptIfExist($appRoot, $script, $appWebRoot);
			$found += $this->appendScriptIfExist($this->serverroot, $theme_dir.'apps/'.$script);

			if ($found) {
				return;
			}
		} elseif ($this->appendScriptIfExist($this->serverroot, $theme_dir.'apps/'.$script)
			|| $this->appendScriptIfExist($this->serverroot, $theme_dir.$script)
			|| $this->appendScriptIfExist($this->serverroot, $script)
			|| $this->appendScriptIfExist($this->serverroot, $theme_dir."dist/$app-$scriptName")
			|| $this->appendScriptIfExist($this->serverroot, "dist/$app-$scriptName")
			|| $this->appendScriptIfExist($appRoot, $script, $appWebRoot)
			|| $this->cacheAndAppendCombineJsonIfExist($this->serverroot, $script.'.json')
			|| $this->cacheAndAppendCombineJsonIfExist($appRoot, $script.'.json', $appWebRoot)
			|| $this->appendScriptIfExist($this->serverroot, $theme_dir.'core/'.$script)
			|| $this->appendScriptIfExist($this->serverroot, 'core/'.$script)
			|| (strpos($scriptName, '/') === -1 && ($this->appendScriptIfExist($this->serverroot, $theme_dir."dist/core-$scriptName")
				|| $this->appendScriptIfExist($this->serverroot, "dist/core-$scriptName")))
			|| $this->cacheAndAppendCombineJsonIfExist($this->serverroot, 'core/'.$script.'.json')
		) {
			return;
		}

		// missing translations files will be ignored
		if (str_contains($script, '/l10n/')) {
			return;
		}

		$this->logger->error('Could not find resource {resource} to load', [
			'resource' => $script . '.js',
			'app' => 'jsresourceloader',
		]);
	}

	/**
	 * @param string $script
	 */
	public function doFindTheme($script) {
	}

	/**
	 * Try to find ES6 script file (`.mjs`) with fallback to plain javascript (`.js`)
	 * @see appendIfExist()
	 */
	protected function appendScriptIfExist(string $root, string $file, string $webRoot = null) {
		if (!$this->appendIfExist($root, $file . '.mjs', $webRoot)) {
			return $this->appendIfExist($root, $file . '.js', $webRoot);
		}
		return true;
	}

	protected function cacheAndAppendCombineJsonIfExist($root, $file, $app = 'core') {
		if (is_file($root.'/'.$file)) {
			if ($this->jsCombiner->process($root, $file, $app)) {
				$this->append($this->serverroot, $this->jsCombiner->getCachedJS($app, $file), false, false);
			} else {
				// Add all the files from the json
				$files = $this->jsCombiner->getContent($root, $file);
				$app_url = null;
				try {
					$app_url = $this->appManager->getAppWebPath($app);
				} catch (AppPathNotFoundException) {
					// pass
				}

				foreach ($files as $jsFile) {
					$this->append($root, $jsFile, $app_url);
				}
			}
			return true;
		}

		return false;
	}
}
