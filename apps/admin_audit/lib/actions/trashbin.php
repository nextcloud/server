<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
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


namespace OCA\Admin_Audit\Actions;


use OCP\ILogger;
use OCP\IUserSession;

class Trashbin extends Action {

	/** @var IUserSession */
	private $userSession;

	/**
	 * Trashbin constructor.
	 *
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 */
	public function __construct(ILogger $logger, IUserSession $userSession) {
		parent::__construct($logger);
		$this->userSession = $userSession;
	}

	public function delete($params) {
		$this->log('File "%s" deleted from trash bin by "%s"',
			[
				'path' => $params['path'],
				'user' => $this->userSession->getUser()->getUID()
			],
			[
				'path', 'user'
			]
		);
	}

	public function restore($params) {
		$this->log('File "%s" restored from trash bin by "%s"',
			[
				'path' => $params['filePath'],
				'user' => $this->userSession->getUser()->getUID()
			],
			[
				'path', 'user'
			]
		);
	}

}
