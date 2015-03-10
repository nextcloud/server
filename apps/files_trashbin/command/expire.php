<?php
/**
 * ownCloud - trash bin
 *
 * @author Robin Appelman
 * @copyright 2015 Robin Appelman icewind@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
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
	}
}
