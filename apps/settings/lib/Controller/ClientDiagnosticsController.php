<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Settings\Controller;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OCA\Settings\Db\ClientDiagnostics;
use OCA\Settings\Db\ClientDiagnosticsMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Session\Exceptions\SessionNotAvailableException;

class ClientDiagnosticsController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private ISession $session,
		private IUserSession $userSession,
		private ClientDiagnosticsMapper $mapper,
		private IProvider $tokenProvider,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 */
	public function update(string $data): DataResponse {
		try {
			$sessionId = $this->session->getId();
		} catch (SessionNotAvailableException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_SERVICE_UNAVAILABLE);
		}
		if ($this->userSession->getImpersonatingUserID() !== null) {
			return new DataResponse([], Http::STATUS_METHOD_NOT_ALLOWED);
		}

		$appPassword = $this->session->get('app_password');

		try {
			$token = $this->tokenProvider->getToken($appPassword);
		} catch (InvalidTokenException $e) {
			return new DataResponse([], Http::STATUS_METHOD_NOT_ALLOWED);
		}

		$entity = $this->mapper->insertOrUpdate(new ClientDiagnostics($token->getId(), $data));

		return new DataResponse([]);
	}
}
