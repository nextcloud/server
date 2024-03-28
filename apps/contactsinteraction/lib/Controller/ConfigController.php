<?php
/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\ContactsInteraction\Controller;

use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;

class ConfigController extends Controller {

	public function __construct(string $appName, IRequest $request, private IConfig $config, private RecentContactMapper $recentContactMapper, private ?string $userId) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 */
	public function disable(): Response {
		if (!$this->userId) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$this->config->setUserValue($this->userId, $this->appName, 'generateContactsInteraction', 'no');

		$this->recentContactMapper->cleanForUser($this->userId);

		return new DataResponse([]);
	}
}
