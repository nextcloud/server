<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\MiddlewareUtils;
use OC\AppFramework\Middleware\Security\Exceptions\AdminIpNotAllowedException;
use OC\AppFramework\Middleware\Security\Exceptions\AppNotEnabledException;
use OC\AppFramework\Middleware\Security\Exceptions\CrossSiteRequestForgeryException;
use OC\AppFramework\Middleware\Security\Exceptions\ExAppRequiredException;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Middleware\Security\Exceptions\NotConfirmedException;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\AppFramework\Middleware\Security\Exceptions\StrictCookieMissingException;
use OC\Security\CSRF\CsrfTokenManager;
use OC\Settings\AuthorizedGroupMapper;
use OC\User\Session;
use OCA\Talk\Controller\PageController as TalkPageController;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AppApiAdminAccessWithoutUser;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\ExAppRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\StrictCookiesRequired;
use OCP\AppFramework\Http\Attribute\SubAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Ip\IRemoteAddress;
use OCP\Server;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Enforces baseline access-control and request-security requirements for controller methods.
 *
 * Inspects controller-method annotations and attributes to apply authentication,
 * authorization, admin IP, strict-cookie, CSRF, and app-availability checks.
 * Handles security-check failures by returning an appropriate response.
 */
class SecurityMiddleware extends Middleware {
	private ?bool $isAdminUser = null;
	private ?bool $isSubAdmin = null;

	public function __construct(
		private readonly IRequest $request,
		private readonly MiddlewareUtils $middlewareUtils,
		private readonly INavigationManager $navigationManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly LoggerInterface $logger,
		private readonly string $appName,
		private readonly bool $isLoggedIn,
		private readonly IGroupManager $groupManager,
		private readonly ISubAdmin $subAdminManager,
		private readonly IAppManager $appManager,
		private readonly IL10N $l10n,
		private readonly AuthorizedGroupMapper $groupAuthorizationMapper,
		private readonly IUserSession $userSession,
		private readonly IRemoteAddress $remoteAddress,
	) {
	}

	private function isAdminUser(): bool {
		if ($this->isAdminUser === null) {
			$user = $this->userSession->getUser();
			$this->isAdminUser = $user && $this->groupManager->isAdmin($user->getUID());
		}
		return $this->isAdminUser;
	}

	private function isSubAdmin(): bool {
		if ($this->isSubAdmin === null) {
			$user = $this->userSession->getUser();
			$this->isSubAdmin = $user && $this->subAdminManager->isSubAdmin($user);
		}
		return $this->isSubAdmin;
	}

	/**
	 * Applies access-control and request-security requirements before a controller method runs.
	 *
	 * Inspects controller-method annotations and attributes to enforce authentication,
	 * authorization, admin IP restrictions, strict-cookie requirements, CSRF protection,
	 * and app availability.
	 *
	 * @param Controller $controller The controller instance.
	 * @param string $methodName The controller method name.
	 * @throws SecurityException When a security requirement is not met.
	 *
	 * @suppress PhanUndeclaredClassConstant
	 */
	#[\Override]
	public function beforeController($controller, $methodName) {
		// Mark the appropriate navigation entry as active for responses that render navigation.
		$navEntry = $this->appName;
		/** @psalm-suppress UndefinedClass */
		// Talk call pages belong to Talk's navigation entry rather than the current app.
		if (get_class($controller) === TalkPageController::class && $methodName === 'showCall') {
			$navEntry = 'spreed';
		}
		$this->navigationManager->setActiveEntry($navEntry);

		$reflectionMethod = new ReflectionMethod($controller, $methodName);

		$isPublicPage = $this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'PublicPage', PublicPage::class);
		$isExAppRequired = $this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'ExAppRequired', ExAppRequired::class);
		$requiresSubAdmin = $this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'SubAdminRequired', SubAdminRequired::class);
		$requiresAuthorizedAdminSetting = $this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'AuthorizedAdminSetting', AuthorizedAdminSetting::class);
		$doesNotRequireAdmin = $this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'NoAdminRequired', NoAdminRequired::class);
		$allowsAppApiAdminAccessWithoutUser = $this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, null, AppApiAdminAccessWithoutUser::class);
		// Keep support for the legacy @StrictCookieRequired annotation while accepting the StrictCookiesRequired attribute.
		$requiresStrictCookies = $this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'StrictCookieRequired', StrictCookiesRequired::class);
		$csrfProtectionDisabled = $this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'NoCSRFRequired', NoCSRFRequired::class);

		$requiresAdmin = !$requiresSubAdmin && !$doesNotRequireAdmin;
		$requiresPrivilegedAccess = $requiresSubAdmin || $requiresAdmin;

		if ($isExAppRequired) {
			if (
				!$this->userSession instanceof Session
				|| $this->userSession->getSession()->get('app_api') !== true
			) {
				throw new ExAppRequiredException();
			}
		} elseif (!$isPublicPage) {
			$authorized = false;
			// Allow an AppAPI request without an associated user to satisfy the route's
			// authorization requirement when it explicitly opts in. Admin IP restrictions
			// still apply to routes that require privileged access.
			$isAppApiRequestWithoutUser = $this->userSession instanceof Session
				&& $this->userSession->getSession()->get('app_api') === true
				&& $this->userSession->getUser() === null;

			if ($allowsAppApiAdminAccessWithoutUser && $isAppApiRequestWithoutUser) {
				$authorized = true;
			}

			if (!$authorized && !$this->isLoggedIn) {
				throw new NotLoggedInException();
			}

			if (!$authorized && $requiresAuthorizedAdminSetting) {
				$authorized = $this->isAdminUser() || ($requiresSubAdmin && $this->isSubAdmin());

				// Allow delegated administrators access to settings authorized for their groups.
				if (!$authorized) {
					$settingClasses = $this->middlewareUtils->getAuthorizedAdminSettingClasses($reflectionMethod);
					$authorizedClasses = $this->groupAuthorizationMapper->findAllClassesForUser($this->userSession->getUser());
					foreach ($settingClasses as $settingClass) {
						$authorized = in_array($settingClass, $authorizedClasses, true);

						if ($authorized) {
							break;
						}
					}
				}

				if (!$authorized) {
					throw new NotAdminException($this->l10n->t('Logged in account must be an admin, a sub admin or gotten special right to access this setting'));
				}

				if (!$this->remoteAddress->allowsAdminActions()) {
					throw new AdminIpNotAllowedException($this->l10n->t('Your current IP address doesn\'t allow you to perform admin actions'));
				}
			}

			if ($requiresSubAdmin && !$this->isSubAdmin() && !$this->isAdminUser() && !$authorized) {
				throw new NotAdminException($this->l10n->t('Logged in account must be an admin or sub admin'));
			}

			if ($requiresAdmin && !$this->isAdminUser() && !$authorized) {
				throw new NotAdminException($this->l10n->t('Logged in account must be an admin'));
			}

			if ($requiresPrivilegedAccess && !$this->remoteAddress->allowsAdminActions()) {
				throw new AdminIpNotAllowedException($this->l10n->t('Your current IP address doesn\'t allow you to perform admin actions'));
			}
		}

		if ($requiresStrictCookies || !$csrfProtectionDisabled) {
			if (!$this->request->passesStrictCookieCheck()) {
				throw new StrictCookieMissingException();
			}
		}

		// Generate the session token before controller execution because the session may
		// be closed before the response is rendered.
		Server::get(CsrfTokenManager::class)->generateSessionToken();

		// Restrict the OCS compatibility bypass to OCS controllers so regular web
		// routes cannot bypass CSRF validation.
		if (
			!$csrfProtectionDisabled
			&& !$this->request->passesCSRFCheck()
			&& !$this->canBypassCsrfForOcsRequest($controller)
		) {
			throw new CrossSiteRequestForgeryException();
		}

		// Per-user app enablement does not apply to public routes.
		if ($isPublicPage) {
			return;
		}
		// Only enforce per-user enablement for real apps.
		try {
			$this->appManager->getAppPath($this->appName);

			if (!$this->appManager->isEnabledForUser($this->appName)) {
				throw new AppNotEnabledException();
			}
		} catch (AppPathNotFoundException $e) {
			// AppFramework consumers that aren't apps do not have an app path.
		}
	}

	/**
	 * Determines whether a failed CSRF check may be bypassed for an OCS request.
	 *
	 * The bypass is restricted to OCS controllers and requests that either identify
	 * themselves as OCS requests or contain a Bearer authorization header.
	 */
	private function canBypassCsrfForOcsRequest(Controller $controller): bool {
		return $controller instanceof OCSController
			&& (
				$this->request->getHeader('OCS-APIREQUEST') === 'true'
				|| stripos($this->request->getHeader('Authorization'), 'Bearer ') === 0
			);
	}

	/**
	 * Converts security exceptions into responses appropriate for the requested format.
	 *
	 * Returns a JSON error response for requests that do not accept HTML. For HTML
	 * requests, redirects unauthenticated users to the login form and renders a
	 * forbidden response for other security failures.
	 *
	 * @param Controller $controller The controller being called.
	 * @param string $methodName The controller method being called.
	 * @param \Exception $exception The exception thrown during request handling.
	 * @return Response The response for a handled security exception.
	 * @throws \Exception When the exception is not a SecurityException.
	 */
	#[\Override]
	public function afterException($controller, $methodName, \Exception $exception): Response {
		if ($exception instanceof SecurityException) {
			if ($exception instanceof StrictCookieMissingException) {
				return new RedirectResponse(\OC::$WEBROOT . '/');
			}
			if (stripos($this->request->getHeader('Accept'), 'html') === false) {
				$response = new JSONResponse(
					['message' => $exception->getMessage()],
					$exception->getCode()
				);
			} else {
				if ($exception instanceof NotLoggedInException) {
					$params = [];
					if (isset($this->request->server['REQUEST_URI'])) {
						$params['redirect_url'] = $this->request->server['REQUEST_URI'];
					}
					$usernamePrefill = $this->request->getParam('user', '');
					if ($usernamePrefill !== '') {
						$params['user'] = $usernamePrefill;
					}
					if ($this->request->getParam('direct')) {
						$params['direct'] = 1;
					}
					$url = $this->urlGenerator->linkToRoute('core.login.showLoginForm', $params);
					$response = new RedirectResponse($url);
				} else {
					$response = new TemplateResponse('core', '403', ['message' => $exception->getMessage()], 'guest');
					$response->setStatus($exception->getCode());
				}
			}

			if ($exception instanceof NotConfirmedException) {
				$response->addHeader('X-NC-Auth-NotConfirmed', 'true');
			}
			$this->logger->debug($exception->getMessage(), [
				'exception' => $exception,
			]);
			return $response;
		}

		throw $exception;
	}
}
