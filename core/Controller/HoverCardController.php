<?php

declare(strict_types=1);
/**
 * @copyright 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Share\IShare;

/**
 * @psalm-import-type CoreContactsAction from ResponseDefinitions
 */
class HoverCardController extends \OCP\AppFramework\OCSController {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Manager $manager,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get the user details for a hovercard
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, array{userId: string, displayName: string, actions: CoreContactsAction[]}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: User details returned
	 * 404: User not found
	 */
	public function getUser(string $userId): DataResponse {
		$contact = $this->manager->findOne($this->userSession->getUser(), IShare::TYPE_USER, $userId);

		if (!$contact) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$data = $contact->jsonSerialize();

		$actions = $data['actions'];
		if ($data['topAction']) {
			array_unshift($actions, $data['topAction']);
		}

		/** @var CoreContactsAction[] $actions */
		return new DataResponse([
			'userId' => $userId,
			'displayName' => $contact->getFullName(),
			'actions' => $actions,
		]);
	}
}
