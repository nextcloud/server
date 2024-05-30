<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\NotConfirmedException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ISession;
use OCP\IUserSession;
use OCP\User\Backend\IPasswordConfirmationBackend;
use ReflectionMethod;

class PasswordConfirmationMiddleware extends Middleware {
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var ISession */
	private $session;
	/** @var IUserSession */
	private $userSession;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var array */
	private $excludedUserBackEnds = ['user_saml' => true, 'user_globalsiteselector' => true];

	/**
	 * PasswordConfirmationMiddleware constructor.
	 *
	 * @param ControllerMethodReflector $reflector
	 * @param ISession $session
	 * @param IUserSession $userSession
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(ControllerMethodReflector $reflector,
		ISession $session,
		IUserSession $userSession,
		ITimeFactory $timeFactory) {
		$this->reflector = $reflector;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @throws NotConfirmedException
	 */
	public function beforeController($controller, $methodName) {
		$reflectionMethod = new ReflectionMethod($controller, $methodName);

		if ($this->hasAnnotationOrAttribute($reflectionMethod, 'PasswordConfirmationRequired', PasswordConfirmationRequired::class)) {
			$user = $this->userSession->getUser();
			$backendClassName = '';
			if ($user !== null) {
				$backend = $user->getBackend();
				if ($backend instanceof IPasswordConfirmationBackend) {
					if (!$backend->canConfirmPassword($user->getUID())) {
						return;
					}
				}

				$backendClassName = $user->getBackendClassName();
			}

			$lastConfirm = (int) $this->session->get('last-password-confirm');
			// we can't check the password against a SAML backend, so skip password confirmation in this case
			if (!isset($this->excludedUserBackEnds[$backendClassName]) && $lastConfirm < ($this->timeFactory->getTime() - (30 * 60 + 15))) { // allow 15 seconds delay
				throw new NotConfirmedException();
			}
		}
	}

	/**
	 * @template T
	 *
	 * @param ReflectionMethod $reflectionMethod
	 * @param string $annotationName
	 * @param class-string<T> $attributeClass
	 * @return boolean
	 */
	protected function hasAnnotationOrAttribute(ReflectionMethod $reflectionMethod, string $annotationName, string $attributeClass): bool {
		if (!empty($reflectionMethod->getAttributes($attributeClass))) {
			return true;
		}

		if ($this->reflector->hasAnnotation($annotationName)) {
			return true;
		}

		return false;
	}
}
