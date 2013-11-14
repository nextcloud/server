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
	 * @var string
	 */
	protected $id;

	/**
	 * @var \OC\User\User $user
	 */
	protected $user;

	public function __construct($arguments) {
		$this->user = $arguments['user'];
		$datadir = $this->user->getHome();
		if (isset($arguments['legacy']) && $arguments['legacy']) {
			// legacy home id (<= 5.0.12)
			$this->id = 'local::' . $datadir . '/';
		}
		else {
		    $this->id = 'home::' . $this->user->getUID();
		}

		parent::__construct(array('datadir' => $datadir));
	}

	public function getId() {
		return $this->id;
	}

	public function getCache($path = '') {
		if (!isset($this->cache)) {
			$this->cache = new \OC\Files\Cache\HomeCache($this);
		}
		return $this->cache;
	}
}
