<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\AppFramework\Middleware;

use OC\Core\Controller\LoginController;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserSession;

class AdditionalScriptsMiddleware extends Middleware {
	public function __construct(
		private IUserSession $userSession,
		private IEventDispatcher $dispatcher,
	) {
	}

	public function afterController($controller, $methodName, Response $response): Response {
		if ($response instanceof TemplateResponse) {
			if ($controller instanceof LoginController) {
				$this->dispatcher->dispatchTyped(new BeforeLoginTemplateRenderedEvent($response));
			} else {
				$isLoggedIn = !($response instanceof StandaloneTemplateResponse) && $this->userSession->isLoggedIn();
				$this->dispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($isLoggedIn, $response));
			}
		}

		return $response;
	}
}
