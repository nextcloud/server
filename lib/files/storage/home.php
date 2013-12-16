<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

/**
 * Specialized version of Local storage for home directory usage
 */
class Home extends Local {

	/**
	 * @var string $user
	 */
	protected $user;

	public function __construct($arguments) {
		$this->user = $arguments['user'];
		$datadir = $arguments['datadir'];

		parent::__construct(array('datadir' => $datadir));
	}

	public function getId() {
		return 'home::' . $this->user;
	}
	
	/**
	 * get the owner of a path
	 *
	 * @param string $path The path to get the owner
	 * @return string uid or false
	 */
	public function getOwner($path) {
		return $this->user;
	}
}
