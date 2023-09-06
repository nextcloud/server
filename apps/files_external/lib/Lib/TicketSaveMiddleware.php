<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
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

namespace OCA\Files_External\Lib;

use Icewind\SMB\KerberosTicket;
use OCA\Files_External\Controller\UserGlobalStoragesController;
use OCA\Files_External\Lib\Auth\SMB\KerberosSsoDatabase;
use OCA\Files_External\Lib\Auth\SMB\KerberosSsoSession;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ICredentialsManager;

class TicketSaveMiddleware extends Middleware {
	const SAVE_SESSION = 1;
	const SAVE_DB = 2;

	private ISession $session;
	private IUserSession $userSession;
	private UserGlobalStoragesService $storagesService;
	private ICredentialsManager $credentialsManager;

	public function __construct(
		ISession $session,
		ICredentialsManager $credentialsManager,
		IUserSession $userSession,
		UserGlobalStoragesService $storagesService
	) {
		$this->session = $session;
		$this->credentialsManager = $credentialsManager;
		$this->userSession = $userSession;
		$this->storagesService = $storagesService;
	}

	public function afterController($controller, $methodName, Response $response) {
		$user = $this->userSession->getUser();
		if (!$user) {
			return $response;
		}
		$ticket = KerberosTicket::fromEnv();
		if ($ticket && $ticket->isValid()) {
			$save = $this->needToSaveTicket($user);
			if ($save & self::SAVE_SESSION) {
				$this->session->set('kerberos_ticket', base64_encode($ticket->save()));
			}
			if ($save & self::SAVE_DB) {
				$this->credentialsManager->store($user->getUID(), 'kerberos_ticket', base64_encode($ticket->save()));
			}
		}
		return $response;
	}

	private function needToSaveTicket(IUser $user): int {
		$save = 0;
		$storages = $this->storagesService->getAllStoragesForUser($user);
		foreach ($storages as $storage) {
			$auth = $storage->getAuthMechanism();
			if ($auth instanceof KerberosSsoSession) {
				$save = $save | self::SAVE_SESSION;
			}
			if ($auth instanceof KerberosSsoDatabase) {
				$save = $save | self::SAVE_DB;
			}
		}
		return $save;
	}
}
