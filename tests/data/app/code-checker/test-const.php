<?php

/**
 * Class BadClass - accessing consts on blacklisted classes is not allowed
 */
class BadClass {
	public function foo() {
		$bar = \OC_API::ADMIN_AUTH;
	}
}
