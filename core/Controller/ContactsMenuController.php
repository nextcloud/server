<?php
/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OC\Core\Controller;

use Exception;
use OC\Contacts\ContactsMenu\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class ContactsMenuController extends Controller {
	private Manager $manager;
	private IUserSession $userSession;

	public function __construct(IRequest $request, IUserSession $userSession, Manager $manager) {
		parent::__construct('core', $request);
		$this->userSession = $userSession;
		$this->manager = $manager;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return \JsonSerializable[]
	 * @throws Exception
	 */
	public function index(?string $filter = null): array {
		return $this->manager->getEntries($this->userSession->getUser(), $filter);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse|\JsonSerializable
	 * @throws Exception
	 */
	public function findOne(int $shareType, string $shareWith) {
		$contact = $this->manager->findOne($this->userSession->getUser(), $shareType, $shareWith);

		if ($contact) {
			return $contact;
		}
		return new JSONResponse([], Http::STATUS_NOT_FOUND);
	}
}
