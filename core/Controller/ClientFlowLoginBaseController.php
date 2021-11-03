<?php
/*
 * @copyright Copyright (c) 2021 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

declare(strict_types=1);

namespace OC\Core\Controller;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\IRequest;

abstract class ClientFlowLoginBaseController extends Controller {

	/** @var IProvider */
	private $tokenProvider;

	public function __construct($appName, IRequest $request, IProvider $tokenProvider) {
		parent::__construct($appName, $request);

		$this->tokenProvider = $tokenProvider;
	}

	abstract protected function isValidStateToken(string $stateToken): bool;

	/**
	 * @PublicPage
	 */
	public function apptokenRedirect(string $stateToken, string $user, string $password) {
		if (!$this->isValidStateToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		try {
			$token = $this->tokenProvider->getToken($password);
			if ($token->getLoginName() !== $user) {
				throw new InvalidTokenException('login name does not match');
			}
		} catch (InvalidTokenException $e) {
			$response = new StandaloneTemplateResponse(
				$this->appName,
				'403',
				[
					'message' => $this->l10n->t('Invalid app password'),
				],
				'guest'
			);
			$response->setStatus(Http::STATUS_FORBIDDEN);
			return $response;
		}

		$redirectUri = 'nc://login/server:' . $this->getServerPath() . '&user:' . urlencode($user) . '&password:' . urlencode($password);
		return new Http\RedirectResponse($redirectUri);
	}


	protected function stateTokenForbiddenResponse(): StandaloneTemplateResponse {
		$response = new StandaloneTemplateResponse(
			$this->appName,
			'403',
			[
				'message' => $this->l10n->t('State token does not match'),
			],
			'guest'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);
		return $response;
	}

}
