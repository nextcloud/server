<?php
declare(strict_types=1);
/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\DAV\Controller;

use OCA\DAV\Db\DirectMapper;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserManager;

class DirectController extends OCSController {

	/** @var IRootFolder */
	private $rootFolder;

	/** @var string */
	private $userId;

	/** @var DirectMapper */
	private $mapper;


	public function __construct(string $appName,
								IRequest $request,
								IRootFolder $rootFolder,
								string $userId,
								DirectMapper $mapper) {
		parent::__construct($appName, $request);

		$this->rootFolder = $rootFolder;
		$this->userId = $userId;
		$this->mapper = $mapper;
	}

	public function getUrl(int $fileId) {

	}
}
