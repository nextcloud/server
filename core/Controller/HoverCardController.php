<?php

declare(strict_types=1);
/**
 * @copyright 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OC\Core\Controller;

use OC\Contacts\ContactsMenu\Manager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Share\IShare;

class HoverCardController extends \OCP\AppFramework\OCSController {
	private Manager $manager;
	private IUserSession $userSession;

	public function __construct(IRequest $request, IUserSession $userSession, Manager $manager) {
		parent::__construct('core', $request);
		$this->userSession = $userSession;
		$this->manager = $manager;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getUser(string $userId): DataResponse {
		$contact = $this->manager->findOne($this->userSession->getUser(), IShare::TYPE_USER, $userId);

		if (!$contact) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$data = $this->entryToArray($contact);

		$actions = $data['actions'];
		if ($data['topAction']) {
			array_unshift($actions, $data['topAction']);
		}

		return new DataResponse([
			'userId' => $userId,
			'displayName' => $contact->getFullName(),
			'actions' => $actions,
		]);
	}

	protected function entryToArray(IEntry $entry): array {
		return json_decode(json_encode($entry), true);
	}
}
