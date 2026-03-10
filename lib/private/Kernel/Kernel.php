<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Kernel;

use Override;

class Kernel implements IKernel {
	private \Composer\Autoload\ClassLoader $composerAutoloader;
	private \OC\Config $systemConfig;
	private string $serverRoot;
	private string $webRoot;
	private string $configDir;
	private string $subUri;
	private array $appsRoots = [];

	public function __construct() {
		$this->serverRoot = str_replace('\\', '/', substr(__DIR__, 0, -4));
	}

	public function boot(): void {
		$this->setRequiredIniValues();

		// prevent any XML processing from loading external entities
		libxml_set_external_entity_loader(static function () {
			return null;
		});

		// Set default timezone before the Server object is booted
		if (!date_default_timezone_set('UTC')) {
			throw new \RuntimeException('Could not set timezone to UTC');
		}

		// register autoloader
		$loaderStart = microtime(true);

		// Add default composer PSR-4 autoloader, ensure apcu to be disabled
		$this->composerAutoloader = require_once $this->getServerRoot() . '/lib/composer/autoload.php';
		$this->composerAutoloader->setApcuPrefix(null);


		try {
			$this->initPaths();
			// setup 3rdparty autoloader
			$vendorAutoLoad = $this->getServerRoot() . '/3rdparty/autoload.php';
			if (!file_exists($vendorAutoLoad)) {
				throw new \RuntimeException('Composer autoloader not found, unable to continue. Check the folder "3rdparty". Running "git submodule update --init" will initialize the git submodule that handles the subfolder "3rdparty".');
			}
			require_once $vendorAutoLoad;
		} catch (\RuntimeException $e) {
			if (!$this->isCli()) {
				http_response_code(503);
			}
			// we can't use the template error page here, because this needs the
			// DI container which isn't available yet
			print($e->getMessage());
			exit();
		}
		$loaderEnd = microtime(true);
	}

	#[Override]
	public function isCli(): bool {
		return php_sapi_name() == 'cli';
	}

	#[Override]
	public function getServerRoot(): string {
		return str_replace('\\', '/', substr(__DIR__, 0, -4));
	}

	/**
	 * Try to set some values to the required Nextcloud default
	 */
	private function setRequiredIniValues(): void {
		// Don't display errors and log them
		@ini_set('display_errors', '0');
		@ini_set('log_errors', '1');

		// Try to configure php to enable big file uploads.
		// This doesn't work always depending on the webserver and php configuration.
		// Let's try to overwrite some defaults if they are smaller than 1 hour

		if (intval(@ini_get('max_execution_time') ?: 0) < 3600) {
			@ini_set('max_execution_time', strval(3600));
		}

		if (intval(@ini_get('max_input_time') ?: 0) < 3600) {
			@ini_set('max_input_time', strval(3600));
		}

		// Try to set the maximum execution time to the largest time limit we have
		if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
			@set_time_limit(max(intval(@ini_get('max_execution_time')), intval(@ini_get('max_input_time'))));
		}

		@ini_set('default_charset', 'UTF-8');
		@ini_set('gd.jpeg_ignore_warning', '1');
	}

	/**
	 * @throws \RuntimeException when the 3rdparty directory is missing or
	 *                           the app path list is empty or contains an invalid path
	 */
	public function initPaths(): void {
		if (defined('PHPUNIT_CONFIG_DIR')) {
			$this->configDir = $this->getServerRoot() . '/' . PHPUNIT_CONFIG_DIR . '/';
		} elseif (defined('PHPUNIT_RUN') && PHPUNIT_RUN && is_dir($this->getServerRoot() . '/tests/config/')) {
			$this->configDir = $this->getServerRoot() . '/tests/config/';
		} elseif ($dir = getenv('NEXTCLOUD_CONFIG_DIR')) {
			$this->configDir = rtrim($dir, '/') . '/';
		} else {
			$this->configDir = $this->getServerRoot() . '/config/';
		}
		$this->systemConfig = new \OC\Config($this->configDir);

		$this->subUri = str_replace('\\', '/', substr(realpath($_SERVER['SCRIPT_FILENAME'] ?? ''), strlen($this->getServerRoot())));
		/**
		 * FIXME: The following lines are required because we can't yet instantiate
		 *        Server::get(\OCP\IRequest::class) since \OC::$server does not yet exist.
		 */
		$params = [
			'server' => [
				'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
				'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? null,
			],
		];
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$params['server']['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
		}
		$fakeRequest = new \OC\AppFramework\Http\Request(
			$params,
			new \OC\AppFramework\Http\RequestId($_SERVER['UNIQUE_ID'] ?? '', new \OC\Security\SecureRandom()),
			new \OC\AllConfig(new \OC\SystemConfig($this->systemConfig))
		);
		$scriptName = $fakeRequest->getScriptName();
		if (substr($scriptName, -1) == '/') {
			$scriptName .= 'index.php';
			//make sure suburi follows the same rules as scriptName
			if (substr($this->subUri, -9) != 'index.php') {
				if (substr($this->subUri, -1) != '/') {
					$this->subUri = $this->subUri . '/';
				}
				$this->subUri = $this->subUri . 'index.php';
			}
		}

		if ($this->isCli()) {
			$this->webRoot = $this->systemConfig->getValue('overwritewebroot', '');
		} else {
			if (substr($scriptName, 0 - strlen($this->subUri)) === $this->subUri) {
				$this->webRoot = substr($scriptName, 0, 0 - strlen($this->subUri));

				if ($this->webRoot != '' && $this->webRoot[0] !== '/') {
					$this->webRoot = '/' . $this->webRoot;
				}
			} else {
				// The scriptName is not ending with Kernel::subUri
				// This most likely means that we are calling from CLI.
				// However, some cron jobs still need to generate
				// a web URL, so we use overwritewebroot as a fallback.
				$this->webRoot = $this->systemConfig->getValue('overwritewebroot', '');
			}

			// Resolve /nextcloud to /nextcloud/ to ensure to always have a trailing
			// slash which is required by URL generation.
			if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === $this->webRoot
				&& substr($_SERVER['REQUEST_URI'], -1) !== '/') {
				header('Location: ' . $this->webRoot . '/');
				exit();
			}
		}

		// search the apps folder
		$config_paths = $this->systemConfig->getValue('apps_paths', []);
		if (!empty($config_paths)) {
			foreach ($config_paths as $paths) {
				if (isset($paths['url']) && isset($paths['path'])) {
					$paths['url'] = rtrim($paths['url'], '/');
					$paths['path'] = rtrim($paths['path'], '/');
					$this->appsRoots[] = $paths;
				}
			}
		} elseif (file_exists($this->serverRoot . '/apps')) {
			$this->appsRoots[] = ['path' => $this->serverRoot . '/apps', 'url' => '/apps', 'writable' => true];
		}

		if ($this->appsRoots === []) {
			throw new \RuntimeException('apps directory not found! Please put the Nextcloud apps folder in the Nextcloud folder'
				. '. You can also configure the location in the config.php file.');
		}
		$paths = [];
		foreach ($this->appsRoots as $path) {
			$paths[] = $path['path'];
			if (!is_dir($path['path'])) {
				throw new \RuntimeException(sprintf('App directory "%s" not found! Please put the Nextcloud apps folder in the'
					. ' Nextcloud folder. You can also configure the location in the config.php file.', $path['path']));
			}
		}

		// set the right include path
		set_include_path(
			implode(PATH_SEPARATOR, $paths)
		);
	}

	protected function getWebRoot(): string {

	}
}
