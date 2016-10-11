<?php

namespace OCA\Provisioning_API\Middleware;

use OCA\Provisioning_API\Middleware\Exceptions\NotSubAdminException;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\Utility\IControllerMethodReflector;

class ProvisioningApiMiddleware extends Middleware {

	/** @var IControllerMethodReflector */
	private $reflector;

	/** @var bool */
	private $isAdmin;

	/** @var bool */
	private $isSubAdmin;

	/**
	 * ProvisioningApiMiddleware constructor.
	 *
	 * @param IControllerMethodReflector $reflector
	 * @param bool $isAdmin
	 * @param bool $isSubAdmin
	 */
	public function __construct(
		IControllerMethodReflector $reflector,
		$isAdmin,
		$isSubAdmin) {
		$this->reflector = $reflector;
		$this->isAdmin = $isAdmin;
		$this->isSubAdmin = $isSubAdmin;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 *
	 * @throws NotSubAdminException
	 */
	public function beforeController($controller, $methodName) {
		if (!$this->isAdmin && !$this->reflector->hasAnnotation('NoSubAdminRequired') && !$this->isSubAdmin) {
			throw new NotSubAdminException();
		}
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 * @return Response
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof NotSubAdminException) {
			throw new OCSException($exception->getMessage(), \OCP\API::RESPOND_UNAUTHORISED);
		}

		throw $exception;
	}
}