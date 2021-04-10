<?php

/**
 * Class BadClass - calling static methods on blacklisted classes is not allowed
 */
class BadClass {
	public function foo() {
		OC_App::isEnabled('bar');
	}
}
