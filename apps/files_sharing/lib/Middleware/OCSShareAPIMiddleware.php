<?php

namespace OCA\Files_Sharing\Middleware;

use OCA\Files_Sharing\Controller\ShareAPIController;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IL10N;
use OCP\Share\IManager;

class OCSShareAPIMiddleware extends Middleware {
	/** @var IManager */
	private $shareManager;
	/** @var IL10N */
	private $l;

	public function __construct(IManager $shareManager,
								IL10N $l) {
		$this->shareManager = $shareManager;
		$this->l = $l;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 *
	 * @throws OCSNotFoundException
	 */
	public function beforeController($controller, $methodName) {
		if ($controller instanceof ShareAPIController) {
			if (!$this->shareManager->shareApiEnabled()) {
				throw new OCSNotFoundException($this->l->t('Share API is disabled'));
			}
		}
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController($controller, $methodName, Response $response) {
		if ($controller instanceof ShareAPIController) {
			/** @var ShareAPIController $controller */
			$controller->cleanup();
		}

		return $response;
	}
}
