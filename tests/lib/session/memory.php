<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Session;

class Memory extends Session {
	protected function setUp() {
		parent::setUp();
		$this->instance = new \OC\Session\Memory(uniqid());
	}
}
