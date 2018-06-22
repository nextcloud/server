<?php
declare (strict_types = 1);
/**
 * @copyright Copyright (c) 2018 John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Accessibility\Controller;

use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Exception\ParserException;
use Leafo\ScssPhp\Formatter\Crunched;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;

class AccessibilityController extends Controller {

	/** @var string */
	protected $appName;

	/** @var string */
	protected $serverRoot;

	/** @var IConfig */
	private $config;

	/** @var IUserManager */
	private $userManager;

	/** @var ILogger */
	private $logger;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ITimeFactory */
	protected $timeFactory;

	/** @var IUserSession */
	private $userSession;

	/** @var IAppManager */
	private $appManager;

	/**
	 * Account constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IUserManager $userManager
	 * @param ILogger $logger
	 * @param IURLGenerator $urlGenerator
	 * @param ITimeFactory $timeFactory
	 * @param IUserSession $userSession
	 * @param IAppManager $appManager
	 */
	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IUserManager $userManager,
								ILogger $logger,
								IURLGenerator $urlGenerator,
								ITimeFactory $timeFactory,
								IUserSession $userSession,
								IAppManager $appManager) {
		parent::__construct($appName, $request);
		$this->appName      = $appName;
		$this->config       = $config;
		$this->userManager  = $userManager;
		$this->logger       = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->timeFactory  = $timeFactory;
		$this->userSession  = $userSession;
		$this->appManager   = $appManager;

		$this->serverRoot = \OC::$SERVERROOT;
		$this->appRoot    = $this->appManager->getAppPath($this->appName);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataDisplayResponse
	 */
	public function getCss(): DataDisplayResponse {
		$css     = '';
		$imports = '';
		$userValues = $this->getUserValues();

		foreach ($userValues as $key => $scssFile) {
			if ($scssFile !== false) {
				$imports .= '@import "' . $scssFile . '";';
			}
		}

		if ($imports !== '') {
			$scss = new Compiler();
			$scss->setImportPaths([
				$this->appRoot . '/css/',
				$this->serverRoot . '/core/css/'
			]);

			// Continue after throw
			$scss->setIgnoreErrors(true);
			$scss->setFormatter(Crunched::class);

			// Import theme, variables and compile css4 variables
			try {
				$css .= $scss->compile(
					$imports .
					'@import "variables.scss";' .
					'@import "css-variables.scss";'
				);
			} catch (ParserException $e) {
				$this->logger->error($e->getMessage(), ['app' => 'core']);
			}
		}

		// We don't want to override vars with url since path is different
		$css = $this->filterOutRule('/--[a-z-:]+url\([^;]+\)/mi', $css);

		// Calculate exact absolute path to file
		$path = $this->urlGenerator->linkToRoute($this->appName . '.accessibility.getCss', ['md5' => md5(implode('-', $userValues))]);
		$path = explode('/', $this->serverRoot . $path);
		array_pop($path);
		$path = implode('/', $path);
		$webDir = $this->getWebDir($path, $this->appName, $this->serverRoot, \OC::$WEBROOT);

		// Rebase all urls
		$css = $this->rebaseUrls($css, $webDir);

		$response = new DataDisplayResponse($css, Http::STATUS_OK, ['Content-Type' => 'text/css']);

		// Set cache control
		$ttl = 31536000;
		$response->addHeader('Cache-Control', 'max-age=' . $ttl . ', immutable');
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT' . $ttl . 'S'));
		$response->addHeader('Expires', $expires->format(\DateTime::RFC1123));
		$response->addHeader('Pragma', 'cache');

		return $response;
	}

	/**
	 * Return an array with the user theme & font settings
	 *
	 * @return array
	 */
	private function getUserValues(): array{
		$userTheme = $this->config->getUserValue($this->userSession->getUser()->getUID(), $this->appName, 'theme', false);
		$userFont  = $this->config->getUserValue($this->userSession->getUser()->getUID(), $this->appName, 'font', false);

		return [$userTheme, $userFont];
	}

	/**
	 * Remove all matches from the $rule regex
	 *
	 * @param string $rule regex to match
	 * @param string $css string to parse
	 * @return string
	 */
	private function filterOutRule(string $rule, string $css): string {
		return preg_replace($rule, '', $css);
	}

	/**
	 * Add the correct uri prefix to make uri valid again
	 *
	 * @param string $css
	 * @param string $webDir
	 * @return string
	 */
	private function rebaseUrls(string $css, string $webDir): string {
		$re    = '/url\([\'"]([^\/][\.\w?=\/-]*)[\'"]\)/x';
		$subst = 'url(\'' . $webDir . '/$1\')';

		return preg_replace($re, $subst, $css);
	}

	/**
	 * Get WebDir root
	 * @param string $path the css file path
	 * @param string $appName the app name
	 * @param string $serverRoot the server root path
	 * @param string $webRoot the nextcloud installation root path
	 * @return string the webDir
	 */
	private function getWebDir(string $path, string $appName, string $serverRoot, string $webRoot): string {
		// Detect if path is within server root AND if path is within an app path
		if ( strpos($path, $serverRoot) === false && $appWebPath = \OC_App::getAppWebPath($appName)) {
			// Get the file path within the app directory
			$appDirectoryPath = explode($appName, $path)[1];
			// Remove the webroot
			return str_replace($webRoot, '', $appWebPath.$appDirectoryPath);
		}
		return $webRoot.substr($path, strlen($serverRoot));
	}

}
