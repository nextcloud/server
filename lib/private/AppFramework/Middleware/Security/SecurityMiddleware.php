<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\AdminIpNotAllowedException;
use OC\AppFramework\Middleware\Security\Exceptions\AppNotEnabledException;
use OC\AppFramework\Middleware\Security\Exceptions\CrossSiteRequestForgeryException;
use OC\AppFramework\Middleware\Security\Exceptions\ExAppRequiredException;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\AppFramework\Middleware\Security\Exceptions\StrictCookieMissingException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Settings\AuthorizedGroupMapper;
use OC\User\Session;
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
use OCP\Util;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Used to do all the authentication and checking stuff for a controller method
 * It reads out the annotations of a controller method and checks which if
 * security things should be checked and also handles errors in case a security
 * check fails
 */
class SecurityMiddleware extends Middleware {
	private ?bool $isAdminUser = null;
	private ?bool $isSubAdmin = null;

	public function __construct(
		private IRequest $request,
		private ControllerMethodReflector $reflector,
		private INavigationManager $navigationManager,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
		private string $appName,
		private bool $isLoggedIn,
		private IGroupManager $groupManager,
		private ISubAdmin $subAdminManager,
		private IAppManager $appManager,
		private IL10N $l10n,
		private AuthorizedGroupMapper $groupAuthorizationMapper,
		private IUserSession $userSession,
		private IRemoteAddress $remoteAddress,
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
	 * This runs all the security checks before a method call. The
	 * security checks are determined by inspecting the controller method
	 * annotations
	 *
	 * @param Controller $controller the controller
	 * @param string $methodName the name of the method
	 * @throws SecurityException when a security check fails
	 *
	 * @suppress PhanUndeclaredClassConstant
	 */
	public function beforeController($controller, $methodName) {
		// this will set the current navigation entry of the app, use this only
		// for normal HTML requests and not for AJAX requests
		$this->navigationManager->setActiveEntry($this->appName);

		if (get_class($controller) === \OCA\Talk\Controller\PageController::class && $methodName === 'showCall') {
			$this->navigationManager->setActiveEntry('spreed');
		}

		$reflectionMethod = new ReflectionMethod($controller, $methodName);

		// security checks
		$isPublicPage = $this->hasAnnotationOrAttribute($reflectionMethod, 'PublicPage', PublicPage::class);

		if ($this->hasAnnotationOrAttribute($reflectionMethod, 'ExAppRequired', ExAppRequired::class)) {
			if (!$this->userSession instanceof Session || $this->userSession->getSession()->get('app_api') !== true) {
				throw new ExAppRequiredException();
			}
		} elseif (!$isPublicPage) {
			$authorized = false;
			if ($this->hasAnnotationOrAttribute($reflectionMethod, null, AppApiAdminAccessWithoutUser::class)) {
				// this attribute allows ExApp to access admin endpoints only if "userId" is "null"
				if ($this->userSession instanceof Session && $this->userSession->getSession()->get('app_api') === true && $this->userSession->getUser() === null) {
					$authorized = true;
				}
			}

			if (!$authorized && !$this->isLoggedIn) {
				throw new NotLoggedInException();
			}

			if (!$authorized && $this->hasAnnotationOrAttribute($reflectionMethod, 'AuthorizedAdminSetting', AuthorizedAdminSetting::class)) {
				$authorized = $this->isAdminUser();

				if (!$authorized && $this->hasAnnotationOrAttribute($reflectionMethod, 'SubAdminRequired', SubAdminRequired::class)) {
					$authorized = $this->isSubAdmin();
				}

				if (!$authorized) {
					$settingClasses = $this->getAuthorizedAdminSettingClasses($reflectionMethod);
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
					throw new AdminIpNotAllowedException($this->l10n->t('Your current IP address doesn’t allow you to perform admin actions'));
				}
			}
			if ($this->hasAnnotationOrAttribute($reflectionMethod, 'SubAdminRequired', SubAdminRequired::class)
				&& !$this->isSubAdmin()
				&& !$this->isAdminUser()
				&& !$authorized) {
				throw new NotAdminException($this->l10n->t('Logged in account must be an admin or sub admin'));
			}
			if (!$this->hasAnnotationOrAttribute($reflectionMethod, 'SubAdminRequired', SubAdminRequired::class)
				&& !$this->hasAnnotationOrAttribute($reflectionMethod, 'NoAdminRequired', NoAdminRequired::class)
				&& !$this->isAdminUser()
				&& !$authorized) {
				throw new NotAdminException($this->l10n->t('Logged in account must be an admin'));
			}
			if ($this->hasAnnotationOrAttribute($reflectionMethod, 'SubAdminRequired', SubAdminRequired::class)
				&& !$this->remoteAddress->allowsAdminActions()) {
				throw new AdminIpNotAllowedException($this->l10n->t('Your current IP address doesn’t allow you to perform admin actions'));
			}
			if (!$this->hasAnnotationOrAttribute($reflectionMethod, 'SubAdminRequired', SubAdminRequired::class)
				&& !$this->hasAnnotationOrAttribute($reflectionMethod, 'NoAdminRequired', NoAdminRequired::class)
				&& !$this->remoteAddress->allowsAdminActions()) {
				throw new AdminIpNotAllowedException($this->l10n->t('Your current IP address doesn’t allow you to perform admin actions'));
			}

		}

		// Check for strict cookie requirement
		if ($this->hasAnnotationOrAttribute($reflectionMethod, 'StrictCookieRequired', StrictCookiesRequired::class) ||
			!$this->hasAnnotationOrAttribute($reflectionMethod, 'NoCSRFRequired', NoCSRFRequired::class)) {
			if (!$this->request->passesStrictCookieCheck()) {
				throw new StrictCookieMissingException();
			}
		}
		// CSRF check - also registers the CSRF token since the session may be closed later
		Util::callRegister();
		if ($this->isInvalidCSRFRequired($reflectionMethod)) {
			/*
			 * Only allow the CSRF check to fail on OCS Requests. This kind of
			 * hacks around that we have no full token auth in place yet and we
			 * do want to offer CSRF checks for web requests.
			 *
			 * Additionally we allow Bearer authenticated requests to pass on OCS routes.
			 * This allows oauth apps (e.g. moodle) to use the OCS endpoints
			 */
			if (!$controller instanceof OCSController || !$this->isValidOCSRequest()) {
				throw new CrossSiteRequestForgeryException();
			}
		}

		/**
		 * Checks if app is enabled (also includes a check whether user is allowed to access the resource)
		 * The getAppPath() check is here since components such as settings also use the AppFramework and
		 * therefore won't pass this check.
		 * If page is public, app does not need to be enabled for current user/visitor
		 */
		try {
			$appPath = $this->appManager->getAppPath($this->appName);
		} catch (AppPathNotFoundException $e) {
			$appPath = false;
		}

		if ($appPath !== false && !$isPublicPage && !$this->appManager->isEnabledForUser($this->appName)) {
			throw new AppNotEnabledException();
		}
	}

	private function isInvalidCSRFRequired(ReflectionMethod $reflectionMethod): bool {
		if ($this->hasAnnotationOrAttribute($reflectionMethod, 'NoCSRFRequired', NoCSRFRequired::class)) {
			return false;
		}

		return !$this->request->passesCSRFCheck();
	}

	private function isValidOCSRequest(): bool {
		return $this->request->getHeader('OCS-APIREQUEST') === 'true'
			|| str_starts_with($this->request->getHeader('Authorization'), 'Bearer ');
	}

	/**
	 * @template T
	 *
	 * @param ReflectionMethod $reflectionMethod
	 * @param ?string $annotationName
	 * @param class-string<T> $attributeClass
	 * @return boolean
	 */
	protected function hasAnnotationOrAttribute(ReflectionMethod $reflectionMethod, ?string $annotationName, string $attributeClass): bool {
		if (!empty($reflectionMethod->getAttributes($attributeClass))) {
			return true;
		}

		if ($annotationName && $this->reflector->hasAnnotation($annotationName)) {
			$this->logger->debug($reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName() . ' uses the @' . $annotationName . ' annotation and should use the #[' . $attributeClass . '] attribute instead');
			return true;
		}

		return false;
	}

	/**
	 * @param ReflectionMethod $reflectionMethod
	 * @return string[]
	 */
	protected function getAuthorizedAdminSettingClasses(ReflectionMethod $reflectionMethod): array {
		$classes = [];
		if ($this->reflector->hasAnnotation('AuthorizedAdminSetting')) {
			$classes = explode(';', $this->reflector->getAnnotationParameter('AuthorizedAdminSetting', 'settings'));
		}

		$attributes = $reflectionMethod->getAttributes(AuthorizedAdminSetting::class);
		if (!empty($attributes)) {
			foreach ($attributes as $attribute) {
				/** @var AuthorizedAdminSetting $setting */
				$setting = $attribute->newInstance();
				$classes[] = $setting->getSettings();
			}
		}

		return $classes;
	}

	/**
	 * If an SecurityException is being caught, ajax requests return a JSON error
	 * response and non ajax requests redirect to the index
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param \Exception $exception the thrown exception
	 * @return Response a Response object or null in case that the exception could not be handled
	 * @throws \Exception the passed in exception if it can't handle it
	 */
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

			$this->logger->debug($exception->getMessage(), [
				'exception' => $exception,
			]);
			return $response;
		}

		throw $exception;
	}
}
