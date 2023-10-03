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

use OCA\Settings\Db\ClientDiagnostic;
use OCA\Settings\Db\ClientDiagnosticMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use Psr\Clock\ClockInterface;

class ClientDiagnosticsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ISession $session,
		private IUserSession $userSession,
		private ClientDiagnosticMapper $mapper,
		private IProvider $tokenProvider,
		private ClockInterface $clock,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @NoCSRFRequired
	 */
	public function update(array $problems): DataResponse {
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

		/* TODO: validate problems structure */

		try {
			$entity = $this->mapper->findByAuthtokenid($token->getId());
			$entity->setDiagnostic(json_encode(['problems' => $problems], JSON_THROW_ON_ERROR));
			$entity->setTimestamp(\DateTime::createFromImmutable($this->clock->now()));
			$this->mapper->update($entity);
		} catch (DoesNotExistException $e) {
			$entity = $this->mapper->insert(
				ClientDiagnostic::fromParams([
					'authtokenid' => $token->getId(),
					'diagnostic' => json_encode(['problems' => $problems], JSON_THROW_ON_ERROR),
					'timestamp' => \DateTime::createFromImmutable($this->clock->now()),
				]));
		}

		return new DataResponse([]);
	}
}
