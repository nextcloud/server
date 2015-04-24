<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Trashbin\Command;

use OC\Command\FileAccess;
use OCA\Files_Trashbin\Trashbin;
use OCP\Command\ICommand;

class Expire implements ICommand {
	use FileAccess;

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var int
	 */
	private $trashBinSize;

	/**
	 * @param string $user
	 * @param int $trashBinSize
	 */
	function __construct($user, $trashBinSize) {
		$this->user = $user;
		$this->trashBinSize = $trashBinSize;
	}

	public function handle() {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($this->user);
		Trashbin::expire($this->trashBinSize, $this->user);
		\OC_Util::tearDownFS();
	}
}
