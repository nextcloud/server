<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Memcache;

class APCuTest extends Cache {
	protected function setUp() {
		parent::setUp();

		if(!\OC\Memcache\APCu::isAvailable()) {
			$this->markTestSkipped('The APCu extension is not available.');
			return;
		}
		$this->instance=new \OC\Memcache\APCu($this->getUniqueID());
	}
}
