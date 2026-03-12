<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Kernel;

use Error;
use Exception;
use OC\AppFramework\Http;
use OC\Core\Controller\SetupController;
use OC\ServiceUnavailableException;
use OC\SystemConfig;
use OC\User\DisabledUserException;
use OC\User\LoginException;
use OC_User;
use OC_Util;
use OCA\AppAPI\Service\AppAPIService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\ErrorTemplateResponse;
use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Diagnostics\IEventLogger;
use OCP\HintException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\Bruteforce\MaxDelayReached;
use OCP\Server;
use OCP\Template\ITemplateManager;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Throwable;
use function OCP\Log\logger;

class HttpKernel extends Kernel {
	public function handle(IRequest $request): Response {
		try {
			return $this->doHandle($request);
		} catch (ServiceUnavailableException $ex) {
			Server::get(LoggerInterface::class)->error($ex->getMessage(), [
				'app' => 'index',
				'exception' => $ex,
			]);

			//show the user a detailed error page
			Server::get(ITemplateManager::class)->printExceptionErrorPage($ex, 503);
		} catch (HintException $ex) {
			try {
				Server::get(ITemplateManager::class)->printErrorPage($ex->getMessage(), $ex->getHint(), 503);
			} catch (Exception $ex2) {
				try {
					Server::get(LoggerInterface::class)->error($ex->getMessage(), [
						'app' => 'index',
						'exception' => $ex,
					]);
					Server::get(LoggerInterface::class)->error($ex2->getMessage(), [
						'app' => 'index',
						'exception' => $ex2,
					]);
				} catch (Throwable $e) {
					// no way to log it properly - but to avoid a white page of death we try harder and ignore this one here
				}

				//show the user a detailed error page
				Server::get(ITemplateManager::class)->printExceptionErrorPage($ex, 500);
			}
		} catch (LoginException $ex) {
			$request = Server::get(IRequest::class);
			/**
			 * Routes with the @CORS annotation and other API endpoints should
			 * not return a webpage, so we only print the error page when html is accepted,
			 * otherwise we reply with a JSON array like the SecurityMiddleware would do.
			 */
			if (stripos($request->getHeader('Accept'), 'html') === false) {
				http_response_code(401);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(['message' => $ex->getMessage()]);
				exit();
			}
			Server::get(ITemplateManager::class)->printErrorPage($ex->getMessage(), $ex->getMessage(), 401);
		} catch (MaxDelayReached $ex) {
			$request = Server::get(IRequest::class);
			/**
			 * Routes with the @CORS annotation and other API endpoints should
			 * not return a webpage, so we only print the error page when html is accepted,
			 * otherwise we reply with a JSON array like the BruteForceMiddleware would do.
			 */
			if (stripos($request->getHeader('Accept'), 'html') === false) {
				http_response_code(429);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(['message' => $ex->getMessage()]);
				exit();
			}
			http_response_code(429);
			Server::get(ITemplateManager::class)->printGuestPage('core', '429');
		} catch (Exception $ex) {
			Server::get(LoggerInterface::class)->error($ex->getMessage(), [
				'app' => 'index',
				'exception' => $ex,
			]);

			//show the user a detailed error page
			Server::get(ITemplateManager::class)->printExceptionErrorPage($ex, 500);
		} catch (Error $ex) {
			try {
				Server::get(LoggerInterface::class)->error($ex->getMessage(), [
					'app' => 'index',
					'exception' => $ex,
				]);
			} catch (Error $e) {
				http_response_code(500);
				header('Content-Type: text/plain; charset=utf-8');
				print("Internal Server Error\n\n");
				print("The server encountered an internal error and was unable to complete your request.\n");
				print("Please contact the server administrator if this error reappears multiple times, please include the technical details below in your report.\n");
				print("More details can be found in the webserver log.\n");

				throw $ex;
			}
			Server::get(ITemplateManager::class)->printExceptionErrorPage($ex, 500);
		}
	}

	public function doHandle(IRequest $request): Response {
		$this->getContainer()->get(IEventLogger::class)
			->start('handle_request', 'Handle request');

		// Check if Nextcloud is installed or in maintenance (update) mode
		if (!$this->getSystemConfig()->getValue('installed', false)) {
			$this->getContainer()->get(ISession::class)->clear();
			$controller = $this->getContainer()->get(SetupController::class);
			return $controller->run($_POST);
		}

		$request->throwDecodingExceptionIfAny();
		$requestPath = $request->getRawPathInfo();
		if (substr($requestPath, -3) !== '.js') { // we need these files during the upgrade
			$this->checkMaintenanceMode($this->getSystemConfig());

			if (Util::needUpgrade()) {
				if (function_exists('opcache_reset')) {
					opcache_reset();
				}
				if (!((bool)$this->getSystemConfig()->getValue('maintenance', false))) {
					/** @var UpgradePageController $upgradePageController */
					$upgradePageController = $this->getContainer()->get(UpgradePageController::class);
					return $upgradePageController->printUpgradePage($this->getSystemConfig());
				}
			}
		}

		$appManager = $this->getContainer()->get(\OCP\App\IAppManager::class);

		// Always load authentication apps
		$appManager->loadApps(['authentication']);
		$appManager->loadApps(['extended_authentication']);

		// Load minimum set of apps
		if (!Util::needUpgrade() && !((bool)($this->getSystemConfig()->getValue('maintenance', false)))) {
			// For logged-in users: Load everything
			if ($this->getContainer()->get(IUserSession::class)->isLoggedIn()) {
				$appManager->loadApps();
			} else {
				// For guests: Load only filesystem and logging
				$appManager->loadApps(['filesystem', 'logging']);

				// Don't try to log in when a client is trying to get a OAuth token.
				// OAuth needs to support basic auth too, so the login is not valid
				// inside Nextcloud and the Login exception would ruin it.
				if ($request->getRawPathInfo() !== '/apps/oauth2/api/v1/token') {
					try {
						self::handleLogin($request);
					} catch (DisabledUserException $e) {
						// Disabled users would not be seen as logged in and
						// trying to log them in would fail, so the login
						// exception is ignored for the themed stylesheets and
						// images.
						if ($request->getRawPathInfo() !== '/apps/theming/theme/default.css'
							&& $request->getRawPathInfo() !== '/apps/theming/theme/light.css'
							&& $request->getRawPathInfo() !== '/apps/theming/theme/dark.css'
							&& $request->getRawPathInfo() !== '/apps/theming/theme/light-highcontrast.css'
							&& $request->getRawPathInfo() !== '/apps/theming/theme/dark-highcontrast.css'
							&& $request->getRawPathInfo() !== '/apps/theming/theme/opendyslexic.css'
							&& $request->getRawPathInfo() !== '/apps/theming/image/background'
							&& $request->getRawPathInfo() !== '/apps/theming/image/logo'
							&& $request->getRawPathInfo() !== '/apps/theming/image/logoheader'
							&& !str_starts_with($request->getRawPathInfo(), '/apps/theming/favicon')
							&& !str_starts_with($request->getRawPathInfo(), '/apps/theming/icon')) {
							throw $e;
						}
					}
				}
			}
		}

		try {
			if (!Util::needUpgrade()) {
				$appManager->loadApps(['filesystem', 'logging']);
				$appManager->loadApps();
			}
			return Server::get(\OC\Route\Router::class)->match($request);
		} catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
			//header('HTTP/1.0 404 Not Found');
		} catch (Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
			return new ErrorTemplateResponse('', '', status: 405);
		}

		// Handle WebDAV
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'PROPFIND') {
			// not allowed anymore to prevent people
			// mounting this root directly.
			// Users need to mount remote.php/webdav instead.
			return new ErrorTemplateResponse('', '', status: 405);
		}

		// Handle requests for JSON or XML
		$acceptHeader = $request->getHeader('Accept');
		if (in_array($acceptHeader, ['application/json', 'application/xml'], true)) {
			return new NotFoundResponse();
		}

		// Handle resources that can't be found
		// This prevents browsers from redirecting to the default page and then
		// attempting to parse HTML as CSS and similar.
		$destinationHeader = $request->getHeader('Sec-Fetch-Dest');
		if (in_array($destinationHeader, ['font', 'script', 'style'])) {
			return new NotFoundResponse();
		}

		// Redirect to the default app or login only as an entry point
		if ($requestPath === '') {
			// Someone is logged in
			$userSession = Server::get(IUserSession::class);
			if ($userSession->isLoggedIn()) {
				$response = new RedirectResponse(Server::get(IURLGenerator::class)->linkToDefaultPageUrl());
				$response->addHeader('X-User-Id', $userSession->getUser()?->getUID());
				return $response;
			} else {
				// Not handled and not logged in
				return new RedirectResponse(Server::get(IURLGenerator::class)->linkToRouteAbsolute('core.login.showLoginForm'));
			}
		}

		try {
			return Server::get(\OC\Route\Router::class)->match('/error/404');
		} catch (\Exception $e) {
			if (!$e instanceof MethodNotAllowedException) {
				logger('core')->emergency($e->getMessage(), ['exception' => $e]);
			}
			return new NotFoundResponse();
		}
	}

	public function checkMaintenanceMode(SystemConfig $systemConfig): ?TemplateResponse {
		// Allow ajax update script to execute without being stopped
		if (!((bool)$systemConfig->getValue('maintenance', false)) || $this->getSubUri() === '/core/ajax/update.php') {
			return null;
		}

		$response = new TemplateResponse('', 'update.user', [], TemplateResponse::RENDER_AS_GUEST, 503);
		$response->setHeaders('X-Nextcloud-Maintenance-Mode', '1');
		$response->setHeaders('Retry-After', '120');
		Util::addScript('core', 'maintenance');
		Util::addScript('core', 'common');
		Util::addStyle('core', 'guest');
		return $response;
	}

	public function boot(): self {
		$this->handleAuthHeaders();
		parent::boot();

		$this->eventLogger->start('check_server', 'Run a few configuration checks');
		$errors = OC_Util::checkServer($this->systemConfig);
		if (count($errors) > 0) {
			if (!$this->isCli()) {
				http_response_code(503);
				Util::addStyle('guest');
				try {
					$this->server->get(ITemplateManager::class)->printGuestPage('', 'error', ['errors' => $errors]);
					exit;
				} catch (\Exception $e) {
					// In case any error happens when showing the error page, we simply fall back to posting the text.
					// This might be the case when e.g. the data directory is broken and we can not load/write SCSS to/from it.
				}
			}

			// Convert l10n string into regular string for usage in database
			$staticErrors = [];
			foreach ($errors as $error) {
				echo $error['error'] . "\n";
				echo $error['hint'] . "\n\n";
				$staticErrors[] = [
					'error' => (string)$error['error'],
					'hint' => (string)$error['hint'],
				];
			}

			try {
				$this->server->get(IAppConfig::class)->setValueArray('core', 'cronErrors', $staticErrors);
			} catch (\Exception $e) {
				echo('Writing to database failed');
			}
			exit(1);
		} elseif ($this->isCli() && $this->systemConfig->getValue('installed', false)) {
			$this->server->get(IAppConfig::class)->deleteKey('core', 'cronErrors');
		}
		$this->eventLogger->end('check_server');
		return $this;
	}

	protected function setupSession(IRequest $request, IEventLogger $eventLogger): void {
		$eventLogger->start('init_session', 'Initialize session');

		$systemConfig = $this->server->get(SystemConfig::class);
		$appManager = $this->server->get(\OCP\App\IAppManager::class);
		if ($systemConfig->getValue('installed', false)) {
			$appManager->loadApps(['session']);
		}
		$this->initSession($request);
		$eventLogger->end('init_session');
		$this->checkInstalled($systemConfig);

		$this->addSecurityHeaders();
		$this->performSameSiteCookieProtection($request, $this->server->get(IConfig::class));
	}

	public function initSession(IRequest $request): void {
		// TODO: Temporary disabled again to solve issues with CalDAV/CardDAV clients like DAVx5 that use cookies
		// TODO: See https://github.com/nextcloud/server/issues/37277#issuecomment-1476366147 and the other comments
		// TODO: for further information.
		// $isDavRequest = strpos($request->getRequestUri(), '/remote.php/dav') === 0 || strpos($request->getRequestUri(), '/remote.php/webdav') === 0;
		// if ($request->getHeader('Authorization') !== '' && is_null($request->getCookie('cookie_test')) && $isDavRequest && !isset($_COOKIE['nc_session_id'])) {
		// setcookie('cookie_test', 'test', time() + 3600);
		// // Do not initialize the session if a request is authenticated directly
		// // unless there is a session cookie already sent along
		// return;
		// }

		if ($request->getServerProtocol() === 'https') {
			ini_set('session.cookie_secure', 'true');
		}

		// prevents javascript from accessing php session cookies
		ini_set('session.cookie_httponly', 'true');

		// set the cookie path to the Nextcloud directory
		$cookie_path = $this->webRoot ? : '/';
		ini_set('session.cookie_path', $cookie_path);

		// set the cookie domain to the Nextcloud domain
		$cookie_domain = $this->getSystemConfig()->getValue('cookie_domain', '');
		if ($cookie_domain) {
			ini_set('session.cookie_domain', $cookie_domain);
		}

		// Do not initialize sessions for 'status.php' requests
		// Monitoring endpoints can quickly flood session handlers
		// and 'status.php' doesn't require sessions anyway
		// We still need to run the ini_set above so that same-site cookies use the correct configuration.
		if (str_ends_with($request->getScriptName(), '/status.php')) {
			return;
		}

		// Let the session name be changed in the initSession Hook
		$sessionName = OC_Util::getInstanceId();

		try {
			$logger = null;
			if (Server::get(SystemConfig::class)->getValue('installed', false)) {
				$logger = logger('core');
			}

			// set the session name to the instance id - which is unique
			$session = new \OC\Session\Internal(
				$sessionName,
				$logger,
			);

			$cryptoWrapper = Server::get(\OC\Session\CryptoWrapper::class);
			$session = $cryptoWrapper->wrapSession($session);
			$this->server->setSession($session);

			// if session can't be started break with http 500 error
		} catch (Exception $e) {
			Server::get(LoggerInterface::class)->error($e->getMessage(), ['app' => 'base','exception' => $e]);
			//show the user a detailed error page
			Server::get(ITemplateManager::class)->printExceptionErrorPage($e, 500);
			die();
		}

		//try to set the session lifetime
		$sessionLifeTime = self::getSessionLifeTime();

		// session timeout
		if ($session->exists('LAST_ACTIVITY') && (time() - $session->get('LAST_ACTIVITY') > $sessionLifeTime)) {
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', -1, $this->webRoot ? : '/');
			}
			Server::get(IUserSession::class)->logout();
		}

		if (!self::hasSessionRelaxedExpiry()) {
			$session->set('LAST_ACTIVITY', time());
		}
		$session->close();
	}

	private static function getSessionLifeTime(): int {
		return Server::get(IConfig::class)->getSystemValueInt('session_lifetime', 60 * 60 * 24);
	}

	/**
	 * @return bool true if the session expiry should only be done by gc instead of an explicit timeout
	 */
	public static function hasSessionRelaxedExpiry(): bool {
		return Server::get(IConfig::class)->getSystemValueBool('session_relaxed_expiry', false);
	}

	private function handleAuthHeaders(): void {
		//copy http auth headers for apache+php-fcgid work around
		if (isset($_SERVER['HTTP_XAUTHORIZATION']) && !isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['HTTP_XAUTHORIZATION'];
		}

		// Extract PHP_AUTH_USER/PHP_AUTH_PW from other headers if necessary.
		$vars = [
			'HTTP_AUTHORIZATION', // apache+php-cgi work around
			'REDIRECT_HTTP_AUTHORIZATION', // apache+php-cgi alternative
		];
		foreach ($vars as $var) {
			if (isset($_SERVER[$var]) && is_string($_SERVER[$var]) && preg_match('/Basic\s+(.*)$/i', $_SERVER[$var], $matches)) {
				$credentials = explode(':', base64_decode($matches[1]), 2);
				if (count($credentials) === 2) {
					$_SERVER['PHP_AUTH_USER'] = $credentials[0];
					$_SERVER['PHP_AUTH_PW'] = $credentials[1];
					break;
				}
			}
		}
	}

	/**
	 * Craft the response output based on the given Response object.
	 */
	public function deliverResponse(IRequest $request, Response $response, IOutput $io): void {
		/** @var Http $protocol */
		$protocol = $this->getContainer()->get(Http::class);
		$protocol->getStatusHeader($response->getStatus());

		// Headers
		foreach ($response->getHeaders() as $name => $value) {
			$io->setHeader($name . ': ' . $value);
		}

		// Output

		// Cookies
		foreach ($response->getCookies() as $name => $value) {
			$expireDate = null;
			if ($value['expireDate'] instanceof \DateTime) {
				$expireDate = $value['expireDate']->getTimestamp();
			}
			$sameSite = $value['sameSite'] ?? 'Lax';

			$io->setCookie(
				$name,
				$value['value'],
				$expireDate,
				$this->getWebRoot(),
				null,
				$request->getServerProtocol() === 'https',
				true,
				$sameSite
			);
		}
	}

	/**
	 * Check login: apache auth, auth token, basic auth
	 */
	public function handleLogin(IRequest $request): bool {
		if ($request->getHeader('X-Nextcloud-Federation')) {
			return false;
		}
		$userSession = Server::get(\OC\User\Session::class);
		if (OC_User::handleApacheAuth()) {
			return true;
		}
		if ($this->tryAppAPILogin($request)) {
			return true;
		}
		if ($userSession->tryTokenLogin($request)) {
			return true;
		}
		if (isset($_COOKIE['nc_username'])
			&& isset($_COOKIE['nc_token'])
			&& isset($_COOKIE['nc_session_id'])
			&& $userSession->loginWithCookie($_COOKIE['nc_username'], $_COOKIE['nc_token'], $_COOKIE['nc_session_id'])) {
			return true;
		}
		if ($userSession->tryBasicAuthLogin($request, Server::get(IThrottler::class))) {
			return true;
		}
		return false;
	}

	protected function tryAppAPILogin(IRequest $request): bool {
		if (!$request->getHeader('AUTHORIZATION-APP-API')) {
			return false;
		}
		$appManager = Server::get(IAppManager::class);
		if (!$appManager->isEnabledForAnyone('app_api')) {
			return false;
		}
		try {
			$appAPIService = Server::get(AppAPIService::class);
			return $appAPIService->validateExAppRequestToNC($request);
		} catch (\Psr\Container\NotFoundExceptionInterface|\Psr\Container\ContainerExceptionInterface $e) {
			return false;
		}
	}

	public function checkInstalled(\OC\SystemConfig $systemConfig): void {
		// Redirect to installer if not installed
		if (!$systemConfig->getValue('installed', false) && $this->subUri !== '/index.php' && $this->subUri !== '/status.php') {
			if ($this->isCli()) {
				throw new Exception('Not installed');
			} else {
				$url = $this->webRoot . '/index.php';
				header('Location: ' . $url);
			}
			exit();
		}
	}

	/**
	 * Send the same site cookies
	 */
	private function sendSameSiteCookies(): void {
		$cookieParams = session_get_cookie_params();
		$secureCookie = ($cookieParams['secure'] === true) ? 'secure; ' : '';
		$policies = [
			'lax',
			'strict',
		];

		// Append __Host to the cookie if it meets the requirements
		$cookiePrefix = '';
		if ($cookieParams['secure'] === true && $cookieParams['path'] === '/') {
			$cookiePrefix = '__Host-';
		}

		foreach ($policies as $policy) {
			header(
				sprintf(
					'Set-Cookie: %snc_sameSiteCookie%s=true; path=%s; httponly;' . $secureCookie . 'expires=Fri, 31-Dec-2100 23:59:59 GMT; SameSite=%s',
					$cookiePrefix,
					$policy,
					$cookieParams['path'],
					$policy
				),
				false
			);
		}
	}

	/**
	 * Same Site cookie to further mitigate CSRF attacks. This cookie has to
	 * be set in every request if cookies are sent to add a second level of
	 * defense against CSRF.
	 *
	 * If the cookie is not sent this will set the cookie and reload the page.
	 * We use an additional cookie since we want to protect logout CSRF and
	 * also we can't directly interfere with PHP's session mechanism.
	 */
	private function performSameSiteCookieProtection(IRequest $request, IConfig $config): void {
		// Some user agents are notorious and don't really properly follow HTTP
		// specifications. For those, have an automated opt-out. Since the protection
		// for remote.php is applied in base.php as starting point we need to opt out
		// here.
		$incompatibleUserAgents = $config->getSystemValue('csrf.optout');

		// Fallback, if csrf.optout is unset
		if (!is_array($incompatibleUserAgents)) {
			$incompatibleUserAgents = [
				// OS X Finder
				'/^WebDAVFS/',
				// Windows webdav drive
				'/^Microsoft-WebDAV-MiniRedir/',
			];
		}

		if ($request->isUserAgent($incompatibleUserAgents)) {
			return;
		}

		if (count($_COOKIE) > 0) {
			$requestUri = $request->getScriptName();
			$processingScript = explode('/', $requestUri);
			$processingScript = $processingScript[count($processingScript) - 1];

			if ($processingScript === 'index.php' // index.php routes are handled in the middleware
				|| $processingScript === 'cron.php' // and cron.php does not need any authentication at all
				|| $processingScript === 'public.php' // For public.php, auth for password protected shares is done in the PublicAuth plugin
			) {
				return;
			}

			// All other endpoints require the lax and the strict cookie
			if (!$request->passesStrictCookieCheck()) {
				logger('core')->warning('Request does not pass strict cookie check');
				self::sendSameSiteCookies();
				// Debug mode gets access to the resources without strict cookie
				// due to the fact that the SabreDAV browser also lives there.
				if (!$config->getSystemValueBool('debug', false)) {
					http_response_code(\OCP\AppFramework\Http::STATUS_PRECONDITION_FAILED);
					header('Content-Type: application/json');
					echo json_encode(['error' => 'Strict Cookie has not been found in request']);
					exit();
				}
			}
		} elseif (!isset($_COOKIE['nc_sameSiteCookielax']) || !isset($_COOKIE['nc_sameSiteCookiestrict'])) {
			self::sendSameSiteCookies();
		}
	}

	/**
	 * This function adds some security related headers to all requests served via base.php
	 * The implementation of this function has to happen here to ensure that all third-party
	 * components (e.g. SabreDAV) also benefit from these headers.
	 */
	private static function addSecurityHeaders(): void {
		/**
		 * FIXME: Content Security Policy for legacy components. This
		 * can be removed once \OCP\AppFramework\Http\Response from the AppFramework
		 * is used everywhere.
		 * @see \OCP\AppFramework\Http\Response::getHeaders
		 */
		$policy = 'default-src \'self\'; '
			. 'script-src \'self\' \'nonce-' . \OC::$server->getContentSecurityPolicyNonceManager()->getNonce() . '\'; '
			. 'style-src \'self\' \'unsafe-inline\'; '
			. 'frame-src *; '
			. 'img-src * data: blob:; '
			. 'font-src \'self\' data:; '
			. 'media-src *; '
			. 'connect-src *; '
			. 'object-src \'none\'; '
			. 'base-uri \'self\'; ';
		header('Content-Security-Policy:' . $policy);

		// Send fallback headers for installations that don't have the possibility to send
		// custom headers on the webserver side
		if (getenv('modHeadersAvailable') !== 'true') {
			header('Referrer-Policy: no-referrer'); // https://www.w3.org/TR/referrer-policy/
			header('X-Content-Type-Options: nosniff'); // Disable sniffing the content type for IE
			header('X-Frame-Options: SAMEORIGIN'); // Disallow iFraming from other domains
			header('X-Permitted-Cross-Domain-Policies: none'); // https://www.adobe.com/devnet/adobe-media-server/articles/cross-domain-xml-for-streaming.html
			header('X-Robots-Tag: noindex, nofollow'); // https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
		}
	}
}
