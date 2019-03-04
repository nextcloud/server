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
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OC\Template\IconsCacher;

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

	/** @var IconsCacher */
	protected $iconsCacher;

	/** @var \OC_Defaults */
	private $defaults;

	/** @var null|string */
	private $injectedVariables;

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
	 * @param \OC_Defaults $defaults
	 */
	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IUserManager $userManager,
								ILogger $logger,
								IURLGenerator $urlGenerator,
								ITimeFactory $timeFactory,
								IUserSession $userSession,
								IAppManager $appManager,
								IconsCacher $iconsCacher,
								\OC_Defaults $defaults) {
		parent::__construct($appName, $request);
		$this->appName      = $appName;
		$this->config       = $config;
		$this->userManager  = $userManager;
		$this->logger       = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->timeFactory  = $timeFactory;
		$this->userSession  = $userSession;
		$this->appManager   = $appManager;
		$this->iconsCacher  = $iconsCacher;
		$this->defaults     = $defaults;

		$this->serverRoot = \OC::$SERVERROOT;
		$this->appRoot    = $this->appManager->getAppPath($this->appName);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @NoSameSiteCookieRequired
	 *
	 * @return DataDisplayResponse
	 */
	public function getCss(): DataDisplayResponse {
		$css        = '';
		$imports    = '';
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
					$this->getInjectedVariables() .
					'@import "variables.scss";' .
					'@import "css-variables.scss";'
				);
			} catch (ParserException $e) {
				$this->logger->error($e->getMessage(), ['app' => 'core']);
			}
		}

		// We don't want to override vars with url since path is different
		$css = $this->filterOutRule('/--[a-z-:]+url\([^;]+\)/mi', $css);

		// Rebase all urls
		$appWebRoot = substr($this->appRoot, strlen($this->serverRoot) - strlen(\OC::$WEBROOT));
		$css        = $this->rebaseUrls($css, $appWebRoot . '/css');

		if (in_array('themedark', $userValues) && $this->iconsCacher->getCachedList() && $this->iconsCacher->getCachedList()->getSize() > 0) {
			$iconsCss = $this->invertSvgIconsColor($this->iconsCacher->getCachedList()->getContent());
			$css = $css . $iconsCss;
		}

		$response = new DataDisplayResponse($css, Http::STATUS_OK, ['Content-Type' => 'text/css']);

		// Set cache control
		$ttl = 31536000;
		$response->addHeader('Cache-Control', 'max-age=' . $ttl . ', immutable');
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT' . $ttl . 'S'));
		$response->addHeader('Expires', $expires->format(\DateTime::RFC1123));
		$response->addHeader('Pragma', 'cache');

		// store current cache hash
		$this->config->setUserValue($this->userSession->getUser()->getUID(), $this->appName, 'icons-css', md5($css));

		return $response;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoSameSiteCookieRequired
	 *
	 * @return DataDownloadResponse
	 */
	public function getJavascript(): DataDownloadResponse {
		$user = $this->userSession->getUser();

		if ($user === null) {
			$theme = false;
		} else {
			$theme = $this->config->getUserValue($user->getUID(), $this->appName, 'theme', false);
		}

		$responseJS = '(function() {
	OCA.Accessibility = {
		theme: ' . json_encode($theme) . ',
		
	};
})();';
		$response = new DataDownloadResponse($responseJS, 'javascript', 'text/javascript');
		$response->cacheFor(3600);
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
	 * Remove all matches from the $rule regex
	 *
	 * @param string $css string to parse
	 * @return string
	 */
	private function invertSvgIconsColor(string $css) {
		return str_replace(
			['color=000&', 'color=fff&', 'color=***&'],
			['color=***&', 'color=000&', 'color=fff&'],
			str_replace(
				['color=000000&', 'color=ffffff&', 'color=******&'],
				['color=******&', 'color=000000&', 'color=ffffff&'],
				$css
			)
		);
	}

	/**
	 * @return string SCSS code for variables from OC_Defaults
	 */
	private function getInjectedVariables(): string {
		if ($this->injectedVariables !== null) {
			return $this->injectedVariables;
		}
		$variables = '';
		foreach ($this->defaults->getScssVariables() as $key => $value) {
			$variables .= '$' . $key . ': ' . $value . ';';
		}

		// check for valid variables / otherwise fall back to defaults
		try {
			$scss = new Compiler();
			$scss->compile($variables);
			$this->injectedVariables = $variables;
		} catch (ParserException $e) {
			$this->logger->error($e, ['app' => 'core']);
		}
		return $variables;
	}
}
