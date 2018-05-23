<?php

namespace OC\AppFramework\Middleware\PublicShare;

use OC\AppFramework\Middleware\PublicShare\Exceptions\NeedAuthenticationException;
use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\PublicShareController;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\ISession;

class PublicShareMiddleware extends Middleware {
	/** @var IRequest */
	private $request;

	/** @var ISession */
	private $session;

	public function __construct(IRequest $request, ISession $session) {
		$this->request = $request;
		$this->session = $session;
	}

	public function beforeController($controller, $methodName) {
		if (!($controller instanceof PublicShareController)) {
			return;
		}

		// We require the token parameter to be set
		$token = $this->request->getParam('token');
		if ($token === null) {
			throw new NotFoundException();
		}

		// Set the token
		$controller->setToken($token);

		if (!$controller->isValidToken()) {
			$controller->shareNotFound();
			throw new NotFoundException();
		}

		// No need to check for authentication when we try to authenticate
		if ($methodName === 'authenticate' || $methodName === 'showAuthenticate') {
			return;
		}

		// If authentication succeeds just continue
		if ($controller->isAuthenticated($token)) {
			return;
		}

		// If we can authenticate to this controller do it else we throw a 404 to not leak any info
		if ($controller instanceof AuthPublicShareController) {
			$this->session->set('public_link_authenticate_redirect', json_encode($this->request->getParams()));
			throw new NeedAuthenticationException();
		}

		throw new NotFoundException();

	}

	public function afterException($controller, $methodName, \Exception $exception) {
		if (!($controller instanceof PublicShareController)) {
			throw $exception;
		}

		if ($exception instanceof NotFoundException) {
			return new NotFoundResponse();
		}

		if ($controller instanceof AuthPublicShareController && $exception instanceof NeedAuthenticationException) {
			return $controller->getAuthenticationRedirect($this->getFunctionForRoute($this->request->getParam('_route')));
		}

		throw $exception;
	}

	private function getFunctionForRoute(string $route): string {
		$tmp = explode('.', $route);
		return array_pop($tmp);
	}
}
