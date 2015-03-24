<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Versions\Command;

use OC\Command\FileAccess;
use OCA\Files_Versions\Storage;
use OCP\Command\ICommand;
use OCP\IUser;

class Expire implements ICommand {
	use FileAccess;

	/**
	 * @var string
	 */
	private $fileName;

	/**
	 * @var int|null
	 */
	private $versionsSize;

	/**
	 * @var int
	 */
	private $neededSpace = 0;

	/**
	 * @var int
	 */
	private $user;

	/**
	 * @param IUser $user
	 * @param string $fileName
	 * @param int|null $versionsSize
	 * @param int $neededSpace
	 */
	function __construct(IUser $user, $fileName, $versionsSize = null, $neededSpace = 0) {
		$this->user = $user;
		$this->fileName = $fileName;
		$this->versionsSize = $versionsSize;
		$this->neededSpace = $neededSpace;
	}


	public function handle() {
		$this->setupFS($this->user);
		Storage::expire($this->fileName, $this->versionsSize, $this->neededSpace);
	}
}
