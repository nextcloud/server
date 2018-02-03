<?php
/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\AppFramework\Middleware\Share;

use OC\AppFramework\Middleware\Share\Exceptions\AuthenticationRequiredException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\PublicShareController;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\Share\Exceptions\ShareNotFound;

class PublicShareMiddleware extends Middleware {

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @throws NotFoundException
	 */
	public function beforeController($controller, $methodName) {
		if (!($controller instanceof PublicShareController)) {
			return;
		}

		if (!$this->isLinkSharingEnabled()) {
			throw new NotFoundException('Link sharing is disabled');
		}
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 * @return Response
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if (!($controller instanceof PublicShareController)) {
			throw $exception;
		}

		if ($exception instanceof ShareNotFound || $exception instanceof NotFoundException) {
			return new NotFoundResponse();
		}

		if ($exception instanceof AuthenticationRequiredException) {
			return new RedirectResponse($exception->getRoute());
		}
	}

	/**
	 * Check if link sharing is allowed
	 * @return bool
	 */
	private function isLinkSharingEnabled() {
		// Check if the shareAPI is enabled
		if ($this->config->getAppValue('core', 'shareapi_enabled', 'yes') !== 'yes') {
			return false;
		}

		// Check whether public sharing is enabled
		if($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') !== 'yes') {
			return false;
		}

		return true;
	}
}
