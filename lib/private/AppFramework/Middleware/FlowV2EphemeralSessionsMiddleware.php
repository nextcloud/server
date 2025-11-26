<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Core\Controller\ClientFlowLoginV2Controller;
use OC\Core\Controller\TwoFactorChallengeController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\TwoFactorAuth\ALoginSetupController;
use OCP\ISession;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

// Will close the session if the user session is ephemeral.
// Happens when the user logs in via the login flow v2.
class FlowV2EphemeralSessionsMiddleware extends Middleware {

	private const EPHEMERAL_SESSION_TTL = 5 * 60; // 5 minutes

	public function __construct(
		private ISession $session,
		private IUserSession $userSession,
		private ControllerMethodReflector $reflector,
		private LoggerInterface $logger,
		private ITimeFactory $timeFactory,
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		$sessionCreationTime = $this->session->get(ClientFlowLoginV2Controller::EPHEMERAL_NAME);

		// Not an ephemeral session.
		if ($sessionCreationTime === null) {
			return;
		}

		// Lax enforcement until TTL is reached.
		if ($this->timeFactory->getTime() < $sessionCreationTime + self::EPHEMERAL_SESSION_TTL) {
			return;
		}

		// Allow certain controllers/methods to proceed without logging out.
		if (
			$controller instanceof ClientFlowLoginV2Controller
			&& ($methodName === 'grantPage' || $methodName === 'generateAppPassword')
		) {
			return;
		}

		if ($controller instanceof TwoFactorChallengeController
			|| $controller instanceof ALoginSetupController) {
			return;
		}

		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		if (!empty($reflectionMethod->getAttributes(PublicPage::class))) {
			return;
		}

		if ($this->reflector->hasAnnotation('PublicPage')) {
			return;
		}

		$this->logger->info('Closing user and PHP session for ephemeral session', [
			'controller' => $controller::class,
			'method' => $methodName,
		]);
		$this->userSession->logout();
		$this->session->close();
	}
}
